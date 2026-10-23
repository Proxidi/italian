<?php

namespace Tests\Feature;

use App\Models\MeasurementUnit;
use App\Models\Usuario;
use Database\Seeders\ProductsPermissionsSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\NativeCoreFixtures;
use Tests\TestCase;

class ProductsUnitsTest extends TestCase
{
    use NativeCoreFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        // Never migrate, seed or connect to the application's real database.
        config([
            'database.default' => 'units_tests',
            'database.connections.units_tests' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true],
            'cache.default' => 'array', 'session.driver' => 'array', 'permission.cache.store' => 'array',
            'excel.temporary_files.local_path' => sys_get_temp_dir(),
        ]);
        DB::purge('units_tests');
        $this->app->forgetInstance(PermissionRegistrar::class);
        $this->withoutVite();
        Schema::create('tbl_plazas', function (Blueprint $table) {
            $table->increments('id');
            $table->string('Nombre');
        });
        $this->createNativeCoreSchema();
        Schema::create('tbl_unidades', function (Blueprint $table) {
            $table->increments('id');
            $table->string('Nombre', 50);
        });
        foreach (['roles', 'permissions'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
                $table->unique(['name', 'guard_name']);
            });
        }
        foreach (['model_has_roles' => 'role_id', 'model_has_permissions' => 'permission_id'] as $name => $column) {
            Schema::create($name, function (Blueprint $table) use ($column) {
                $table->unsignedBigInteger($column);
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->primary([$column, 'model_type', 'model_id']);
            });
        }
        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });
        DB::table('tbl_plazas')->insert(['id' => 1, 'Nombre' => 'Plaza de prueba']);
        DB::table('tbl_unidades')->insert([['id' => 1, 'Nombre' => 'Gramos'], ['id' => 2, 'Nombre' => 'Mililitros'], ['id' => 13, 'Nombre' => 'No Asignado']]);
        foreach (['superadmin', 'admin', 'root', 'submanager', 'socio'] as $role) {
            Role::findOrCreate($role, 'web');
        }
        $this->seed(ProductsPermissionsSeeder::class);
    }

    protected function tearDown(): void
    {
        DB::purge('units_tests');
        parent::tearDown();
    }

    protected function actor(string $role = 'root'): Usuario
    {
        return $this->nativeUser($role);
    }

    public static function deniedRoles(): array
    {
        return array_map(fn ($role) => [$role], ['superadmin', 'admin', 'submanager', 'socio']);
    }

    public function test_guests_are_redirected_without_writes(): void
    {
        foreach (['products.index', 'products.units.index', 'products.units.export'] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
        $this->post(route('products.units.store'), ['nombre' => 'Caja'])->assertRedirect(route('login'));
        $this->put(route('products.units.update', 1), ['nombre' => 'Nuevo'])->assertRedirect(route('login'));
        $this->assertSame('Gramos', MeasurementUnit::find(1)->Nombre);
        $this->assertSame(3, MeasurementUnit::count());
    }

    #[DataProvider('deniedRoles')]
    public function test_other_roles_cannot_access_or_write(string $role): void
    {
        $this->actingAs($this->actor($role), 'web');
        foreach (['products.index', 'products.units.index', 'products.units.export'] as $route) {
            $this->get(route($route))->assertForbidden();
        }
        $this->post(route('products.units.store'), ['nombre' => 'Caja'])->assertForbidden();
        $this->put(route('products.units.update', 1), ['nombre' => 'Nuevo'])->assertForbidden();
        $this->assertSame(3, MeasurementUnit::count());
        $this->assertSame('Gramos', MeasurementUnit::find(1)->Nombre);
    }

    public function test_manager_panel_and_units_are_live_but_other_modules_are_inert(): void
    {
        $this->actingAs($this->actor(), 'web');
        $this->get(route('inicio'))->assertOk()->assertSee(route('products.index'), false)->assertSee(route('products.units.index'), false);
        $this->get(route('products.index'))->assertOk()->assertSee('Panel de Productos')->assertSee('Gr,Kg,Lts')
            ->assertSee(route('products.units.index'), false)->assertDontSee('href="'.route('products.cost-of-sale.index').'"', false)->assertDontSee('href="http://localhost/Insumos', false);
        $this->get(route('products.units.index'))->assertOk()->assertSeeInOrder(['Gramos', 'Mililitros', 'No Asignado'])
            ->assertSee('Agregar Unidad')->assertSee('Editar Unidad')->assertSee('name="_token"', false)->assertDontSee('Eliminar');
    }

    public function test_missing_permissions_hide_navigation_and_deny_routes(): void
    {
        Role::findByName('root', 'web')->revokePermissionTo(ProductsPermissionsSeeder::PERMISSIONS);
        $this->actingAs($this->actor(), 'web');
        $this->get(route('inicio'))->assertOk()->assertDontSee(route('products.index'), false)->assertDontSee(route('products.units.index'), false);
        $this->get(route('products.index'))->assertForbidden();
        $this->get(route('products.units.index'))->assertForbidden();
    }

    public function test_read_and_write_and_panel_permissions_are_independent(): void
    {
        $root = Role::findByName('root', 'web');
        $root->revokePermissionTo(['products.view', 'products.units.create', 'products.units.update']);
        $this->actingAs($this->actor(), 'web');
        $this->get(route('products.units.index'))->assertOk()->assertDontSee('data-new-unit', false)->assertDontSee('data-edit-unit', false);
        $this->get(route('products.index'))->assertForbidden();
        $this->post(route('products.units.store'), ['nombre' => 'Caja'])->assertForbidden();
        $this->put(route('products.units.update', 1), ['nombre' => 'Nuevo'])->assertForbidden();
        $root->givePermissionTo(['products.units.create', 'products.units.update']);
        $root->revokePermissionTo('products.units.view');
        $this->actingAs($this->actor(), 'web');
        $this->post(route('products.units.store'), ['nombre' => 'Caja'])->assertForbidden();
        $this->put(route('products.units.update', 1), ['nombre' => 'Nuevo'])->assertForbidden();
    }

    public function test_create_accepts_accents_quotes_and_writes_only_name(): void
    {
        $this->actingAs($this->actor(), 'web')->post(route('products.units.store'), ['nombre' => '  Porción "Café"  ', 'id' => 1, 'unidad_id' => 2, 'Estado' => 0])
            ->assertRedirect(route('products.units.index'))->assertSessionHas('unit-success');
        $this->assertSame(4, MeasurementUnit::count());
        $this->assertSame('Porción "Café"', MeasurementUnit::latest('id')->first()->Nombre);
        $this->assertSame('Gramos', MeasurementUnit::find(1)->Nombre);
    }

    public function test_duplicate_names_are_allowed_like_legacy_schema(): void
    {
        $this->actingAs($this->actor(), 'web')->post(route('products.units.store'), ['nombre' => 'Gramos'])->assertSessionHasNoErrors();
        $this->assertSame(2, MeasurementUnit::where('Nombre', 'Gramos')->count());
    }

    public function test_unit_update_uses_the_legacy_timed_animation(): void
    {
        $this->actingAs($this->actor(), 'web')->put(route('products.units.update', 1), ['nombre' => 'Gramos editados'])
            ->assertRedirect(route('products.units.index'))->assertSessionHas('unit-updated', true);
        $this->withCookie(config('session.cookie'), session()->getId())->get(route('products.units.index'))
            ->assertOk()->assertSee('Unidad actualizada correctamente!!')->assertSee('data-feedback-dialog', false)
            ->assertSee('data-duration="1000"', false)->assertSee('ui-feedback-dialog--corner', false)->assertDontSee('data-redirect-url', false);
    }

    public function test_new_unit_uses_static_feedback_without_inventing_a_legacy_animation(): void
    {
        $this->actingAs($this->actor(), 'web')->post(route('products.units.store'), ['nombre' => 'Unidad nueva'])
            ->assertRedirect(route('products.units.index'))->assertSessionMissing('unit-updated');
        $this->withCookie(config('session.cookie'), session()->getId())->get(route('products.units.index'))
            ->assertOk()->assertSee('Unidad registrada correctamente!!')->assertSee('data-feedback', false)
            ->assertDontSee('data-feedback-dialog', false)->assertDontSee('data-duration', false);
    }

    public static function badNames(): array
    {
        return [[''], ['   '], [str_repeat('á', 51)], [['array']]];
    }

    #[DataProvider('badNames')]
    public function test_invalid_names_neither_create_nor_update(mixed $name): void
    {
        $this->actingAs($this->actor(), 'web');
        $this->from(route('products.units.index'))->post(route('products.units.store'), ['nombre' => $name, '_operation' => 'create'])
            ->assertSessionHasErrors('nombre')->assertSessionMissing('unit-success');
        $this->from(route('products.units.index'))->put(route('products.units.update', 1), ['nombre' => $name, '_operation' => 'update', '_unit_id' => 1])
            ->assertSessionHasErrors('nombre')->assertSessionMissing('unit-success');
        $this->withCookie(config('session.cookie'), session()->getId())->get(route('products.units.index'))->assertOk()->assertSee('data-reopen', false);
        $this->assertSame(3, MeasurementUnit::count());
        $this->assertSame('Gramos', MeasurementUnit::find(1)->Nombre);
    }

    public function test_rename_used_unit_preserves_references_quantities_and_other_units(): void
    {
        $tables = ['tbl_insumos', 'tbl_insumos_gastos', 'tbl_captura_inventario', 'tbl_historial_completo_if'];
        foreach (['tbl_insumos' => ['unidad_id', 'unidad_pedido', 'unidad_final'], 'tbl_insumos_gastos' => ['unidad_id'], 'tbl_captura_inventario' => ['Unidad_Bodega', 'Unidad_Piso', 'Unidad_Total'], 'tbl_historial_completo_if' => ['Unidad_Bodega', 'Unidad_Piso', 'Unidad_Total']] as $table => $columns) {
            Schema::create($table, function (Blueprint $schema) use ($columns, $table) {
                $schema->increments('id');
                $schema->float('Cantidad');
                foreach ($columns as $column) {
                    $schema->unsignedInteger($column);
                    if ($table !== 'tbl_historial_completo_if') {
                        $schema->foreign($column)->references('id')->on('tbl_unidades');
                    }
                }
            });
            DB::table($table)->insert(['id' => 1, 'Cantidad' => 2.5, ...array_fill_keys($columns, 1)]);
        }
        $before = array_map(fn ($table) => DB::table($table)->get()->toJson(), $tables);
        $this->actingAs($this->actor(), 'web')->put(route('products.units.update', 1), ['nombre' => str_repeat('á', 50), 'id' => 2])
            ->assertRedirect(route('products.units.index'))->assertSessionHas('unit-success');
        $this->assertSame(str_repeat('á', 50), MeasurementUnit::find(1)->Nombre);
        $this->assertSame('Mililitros', MeasurementUnit::find(2)->Nombre);
        $after = array_map(fn ($table) => DB::table($table)->get()->toJson(), $tables);
        $this->assertSame($before, $after);
        $this->assertSame(3, MeasurementUnit::count());
    }

    public function test_unknown_id_is_not_success_and_no_delete_or_legacy_write_routes_exist(): void
    {
        $this->actingAs($this->actor(), 'web');
        $this->put(route('products.units.update', 999), ['nombre' => 'Nuevo'])->assertNotFound()->assertSessionMissing('unit-success');
        $this->delete(route('products.units.update', 1))->assertStatus(405);
        $this->post('/Productos/actualizarUnidad', ['id_editar' => 1, 'nombre_editar' => 'Otro'])->assertNotFound();
        $this->get('/Unidades')->assertNotFound();
        $this->assertSame('Gramos', MeasurementUnit::find(1)->Nombre);
    }

    public function test_literal_search_order_pagination_and_escaped_names(): void
    {
        MeasurementUnit::create(['Nombre' => 'Caja 10%_!']);
        MeasurementUnit::create(['Nombre' => '<script>alert(1)</script>']);
        $this->actingAs($this->actor(), 'web');
        $this->get(route('products.units.index', ['q' => '%_!']))->assertOk()->assertSee('Caja 10%_!')->assertDontSee('data-name="Gramos"', false);
        $this->get(route('products.units.index', ['direction' => 'desc']))->assertOk()->assertSeeInOrder(['No Asignado', 'Mililitros', 'Gramos']);
        $this->get(route('products.units.index'))->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->get(route('products.units.index', ['direction' => 'DROP', 'page' => 0, 'q' => str_repeat('x', 101)]))->assertSessionHasErrors(['direction', 'page', 'q']);
        $this->get(route('products.units.export', ['format' => 'exe']))->assertSessionHasErrors('format');
    }

    public function test_exports_contain_all_filtered_sorted_rows_and_protect_formulas(): void
    {
        for ($i = 0; $i < 55; $i++) {
            MeasurementUnit::create(['Nombre' => sprintf('Caja %02d', $i)]);
        }
        MeasurementUnit::create(['Nombre' => '=1+1']);
        $this->actingAs($this->actor(), 'web');
        $this->get(route('products.units.index', ['q' => 'Caja']))->assertOk()->assertSee('al 50 de un total de 55');
        $response = $this->get(route('products.units.export', ['format' => 'xlsx', 'q' => 'Caja', 'direction' => 'desc', 'page' => 2]))->assertOk();
        $file = $response->baseResponse->getFile()->getPathname();
        $workbook = IOFactory::load($file);
        $sheet = $workbook->getActiveSheet();
        $this->assertSame('Unidades Insumos', $sheet->getCell('A1')->getValue());
        $this->assertSame(58, $sheet->getHighestRow());
        $this->assertSame('Caja 54', $sheet->getCell('A4')->getValue());
        $this->assertSame('Caja 00', $sheet->getCell('A58')->getValue());
        $workbook->disconnectWorksheets();
        $response = $this->get(route('products.units.export', ['q' => '=1+1']))->assertOk();
        $workbook = IOFactory::load($response->baseResponse->getFile()->getPathname());
        $this->assertSame('s', $workbook->getActiveSheet()->getCell('A4')->getDataType());
        $this->assertSame('=1+1', $workbook->getActiveSheet()->getCell('A4')->getValue());
        $workbook->disconnectWorksheets();
        $pdf = $this->get(route('products.units.export', ['format' => 'pdf', 'q' => 'Gramos']))->assertOk();
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
    }

    public function test_seeder_is_repeatable_and_preserves_unrelated_and_direct_permissions(): void
    {
        Permission::findOrCreate('other.view', 'web');
        Role::findByName('admin', 'web')->givePermissionTo(['other.view', 'products.view']);
        $actor = $this->actor('admin');
        $actor->givePermissionTo('products.units.view');
        $this->seed(ProductsPermissionsSeeder::class);
        $this->seed(ProductsPermissionsSeeder::class);
        $this->assertSame(5, Permission::count());
        $this->assertTrue(Role::findByName('admin', 'web')->hasPermissionTo('other.view'));
        $this->assertTrue(Role::findByName('admin', 'web')->hasPermissionTo('products.view'));
        $this->assertTrue($actor->fresh()->hasDirectPermission('products.units.view'));
        $this->actingAs($actor->fresh(), 'web')->get(route('products.units.index'))->assertOk();
    }

    public function test_units_fragment_preserves_search_sort_and_pagination_and_http_form(): void
    {
        for ($i = 0; $i < 52; $i++) {
            MeasurementUnit::create(['Nombre' => sprintf('Unidad %03d', $i)]);
        }
        $this->actingAs($this->actor());
        $url = route('products.units.index', ['q' => 'Unidad', 'direction' => 'desc', 'page' => 2]);
        $full = $this->get($url)->assertOk()->assertSee('<html', false);
        $partial = $this->withHeader('X-Listing-Fragment', 'listing')->get($url)->assertOk()
            ->assertHeader('X-Listing-Fragment', 'listing')->assertDontSee('<html', false)->assertSeeInOrder(['Unidad 001', 'Unidad 000'])
            ->assertSee('data-edit-unit', false)->assertSee('q=Unidad', false)->assertSee('direction=desc', false)->assertSee('method="GET"', false);
        $this->assertSame(2, substr_count($partial->getContent(), 'data-record-row'));
        $this->assertStringContainsString($partial->getContent(), $full->getContent());
        Role::findByName('root', 'web')->revokePermissionTo('products.units.view');
        $this->get($url)->assertForbidden();
    }
}
