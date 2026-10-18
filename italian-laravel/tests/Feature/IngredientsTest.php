<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\IngredientConversion;
use App\Models\User;
use Barryvdh\DomPDF\PDF;
use Database\Seeders\IngredientsPermissionsSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class IngredientsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // A new, explicit in-memory connection, before the first DB operation. Never run migrations/imports.
        config([
            'database.default' => 'ingredients_tests',
            'database.connections.ingredients_tests' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true],
            'cache.default' => 'array', 'session.driver' => 'array', 'permission.cache.store' => 'array',
            'excel.temporary_files.local_path' => sys_get_temp_dir(),
        ]);
        DB::purge('ingredients_tests');
        $this->app->forgetInstance(PermissionRegistrar::class);
        $this->withoutVite();
        foreach (['tbl_plazas', 'tbl_unidades', 'tbl_categorias_insumos'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->increments('id');
                $table->string('Nombre', 50);
            });
        }
        Schema::create('tbl_usuarios', function (Blueprint $table) {
            $table->increments('id');
            $table->string('Nombre');
            $table->string('Usuario');
            $table->string('Sexo');
            $table->integer('plaza_id');
            $table->integer('rol_id');
        });
        Schema::create('tbl_insumos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('Nombre', 100);
            $table->string('clave_pedido', 45);
            foreach (['categorias_insumos_id' => 'tbl_categorias_insumos', 'unidad_id' => 'tbl_unidades', 'unidad_final' => 'tbl_unidades', 'unidad_pedido' => 'tbl_unidades'] as $column => $target) {
                $table->integer($column);
                $table->foreign($column)->references('id')->on($target);
            }
            $table->boolean('inv_produccion')->default(true);
            $table->boolean('inv_fisico')->default(true);
            $table->boolean('pedido')->default(true);
            $table->boolean('Pedido_Pasteleria');
            $table->integer('categoria_pedido_id')->default(1);
            $table->integer('categoria_quebranto_id')->default(1);
            $table->boolean('Analisis_CV');
            $table->double('Costo_Venta');
            $table->integer('Orden_Captura_Inventario')->default(0);
        });
        Schema::create('tbl_insumos_inventario', function (Blueprint $table) {
            $table->increments('id');
            $table->string('Nombre', 150);
            $table->integer('insumo_id');
            $table->foreign('insumo_id')->references('id')->on('tbl_insumos');
            foreach (['Bodega' => 200, 'Piso' => 200, 'Total' => 50, 'DLC' => 50, 'Pedido' => 50, 'CVI' => 20, 'CVG' => 20] as $column => $size) {
                $table->string($column, $size)->default('*,1');
            }
        });
        // Minimal dependent records prove conversion edits never reset inventory, remesas, orders or history.
        foreach (['tbl_inventario_productos', 'tbl_captura_inventario', 'tbl_insumos_gastos', 'tbl_pedidos', 'tbl_historial_completo_if'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->increments('id');
                $table->integer('insumo_id');
                $table->double('Cantidad');
                $table->string('Fecha');
            });
        }
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
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
            $table->primary(['role_id', 'permission_id']);
        });
        DB::table('tbl_plazas')->insert(['id' => 1, 'Nombre' => 'Plaza sintética']);
        DB::table('tbl_categorias_insumos')->insert(['id' => 1, 'Nombre' => 'Categoría de prueba']);
        DB::table('tbl_unidades')->insert([['id' => 1, 'Nombre' => 'Gramos'], ['id' => 5, 'Nombre' => 'Kilogramos'], ['id' => 6, 'Nombre' => 'Paquetes']]);
        foreach (['root', 'superadmin', 'admin', 'submanager', 'socio'] as $role) {
            Role::findOrCreate($role, 'web');
        }
        $this->seed(IngredientsPermissionsSeeder::class);
    }

    protected function tearDown(): void
    {
        IngredientConversion::flushEventListeners();
        DB::purge('ingredients_tests');
        parent::tearDown();
    }

    protected function actor(string $role = 'root'): User
    {
        $id = DB::table('tbl_usuarios')->insertGetId(['Nombre' => 'Gerente sintético', 'Usuario' => 'prueba', 'Sexo' => 'H', 'plaza_id' => 1, 'rol_id' => 3]);
        $actor = User::findOrFail($id);
        $actor->assignRole($role);

        return $actor;
    }

    protected function ingredient(string $name = 'Café de prueba', array $overrides = []): Ingredient
    {
        $id = DB::table('tbl_insumos')->insertGetId([
            'Nombre' => $name, 'clave_pedido' => 'CAF', 'categorias_insumos_id' => 1, 'unidad_id' => 1, 'unidad_final' => 5, 'unidad_pedido' => 6,
            'Pedido_Pasteleria' => 0, 'Analisis_CV' => 1, 'Costo_Venta' => 155.25, ...$overrides,
        ]);

        return Ingredient::findOrFail($id);
    }

    protected function conversion(Ingredient $ingredient, array $overrides = []): IngredientConversion
    {
        $id = DB::table('tbl_insumos_inventario')->insertGetId([
            'Nombre' => $ingredient->Nombre, 'insumo_id' => $ingredient->id, 'Bodega' => '*,25', 'Piso' => '-,615,/,1000',
            'Total' => '/,25', 'DLC' => '/,1000', 'Pedido' => '*,150', 'CVI' => '*,2', 'CVG' => '/,3', ...$overrides,
        ]);

        return IngredientConversion::findOrFail($id);
    }

    protected function updateUrl(Ingredient $ingredient, IngredientConversion $conversion): string
    {
        return route('products.ingredients.conversions.update', [$ingredient, $conversion]);
    }

    protected function snapshot(): array
    {
        return array_map(fn ($table) => DB::table($table)->orderBy('id')->get()->toJson(), [
            'tbl_insumos', 'tbl_insumos_inventario', 'tbl_inventario_productos', 'tbl_captura_inventario',
            'tbl_insumos_gastos', 'tbl_pedidos', 'tbl_historial_completo_if', 'tbl_unidades', 'tbl_categorias_insumos',
        ]);
    }

    public function test_guest_cannot_read_download_or_edit(): void
    {
        $ingredient = $this->ingredient();
        $conversion = $this->conversion($ingredient);
        $before = $this->snapshot();
        foreach (['products.ingredients.index' => [], 'products.ingredients.export' => [], 'products.ingredients.conversions' => [$ingredient]] as $route => $params) {
            $this->get(route($route, $params))->assertRedirect(route('login'));
        }
        $this->put($this->updateUrl($ingredient, $conversion), ['bodega' => '*,1', 'pedido' => '*,1'])->assertRedirect(route('login'));
        $this->assertSame($before, $this->snapshot());
    }

    public static function deniedRoles(): array
    {
        return [['superadmin'], ['admin'], ['submanager'], ['socio']];
    }

    #[DataProvider('deniedRoles')]
    public function test_only_provisioned_permissions_authorize_access(string $role): void
    {
        $ingredient = $this->ingredient();
        $conversion = $this->conversion($ingredient);
        $before = $this->snapshot();
        $this->actingAs($this->actor($role), 'web');
        $this->get(route('products.ingredients.index'))->assertForbidden();
        $this->get(route('products.ingredients.export'))->assertForbidden();
        $this->get(route('products.ingredients.conversions', $ingredient))->assertForbidden();
        $this->put($this->updateUrl($ingredient, $conversion), ['bodega' => '*,1', 'pedido' => '*,1'])->assertForbidden();
        $this->get(route('inicio'))->assertOk()->assertDontSee('href="'.route('products.ingredients.index').'"', false);
        $this->assertSame($before, $this->snapshot());
    }

    public function test_panel_and_write_permissions_are_independent_and_read_is_also_required(): void
    {
        $ingredient = $this->ingredient();
        $conversion = $this->conversion($ingredient);
        $this->actingAs($this->actor(), 'web');
        $this->get(route('products.ingredients.index'))->assertOk()->assertDontSee('Volver al Panel de Productos');
        $this->get(route('products.index'))->assertForbidden();
        Role::findByName('root', 'web')->revokePermissionTo('products.ingredients.conversions.update');
        $this->get(route('products.ingredients.conversions', $ingredient))->assertOk()->assertDontSee('data-edit-conversion', false);
        $this->put($this->updateUrl($ingredient, $conversion), ['bodega' => '*,1', 'pedido' => '*,1'])->assertForbidden();
        Role::findByName('root', 'web')->givePermissionTo('products.ingredients.conversions.update')->revokePermissionTo('products.ingredients.view');
        $this->get(route('products.ingredients.index'))->assertForbidden();
        $this->put($this->updateUrl($ingredient, $conversion), ['bodega' => '*,1', 'pedido' => '*,1'])->assertForbidden();
        $this->get(route('inicio'))->assertOk()->assertDontSee('href="'.route('products.ingredients.index').'"', false);
    }

    public function test_granted_panel_and_sidebar_link_to_ingredients_without_activating_other_modules(): void
    {
        Permission::findOrCreate('products.view', 'web');
        Role::findByName('root', 'web')->givePermissionTo('products.view');
        $this->actingAs($this->actor(), 'web');
        $this->get(route('inicio'))->assertOk()->assertSee(route('products.ingredients.index'), false);
        $this->get(route('products.index'))->assertOk()->assertSee('href="'.route('products.ingredients.index').'"', false)->assertSee('Pendiente de migración');
        Role::findByName('root', 'web')->revokePermissionTo('products.ingredients.view');
        $this->get(route('products.index'))->assertOk()->assertDontSee('href="'.route('products.ingredients.index').'"', false);
    }

    public function test_list_uses_correct_units_global_scope_id_order_and_shared_components_without_writes(): void
    {
        $first = $this->ingredient('Zeta "Café"', ['inv_produccion' => 0, 'inv_fisico' => 0, 'pedido' => 0]);
        $second = $this->ingredient('Alfa <script>alert(1)</script>');
        $before = $this->snapshot();
        $response = $this->actingAs($this->actor(), 'web')->get(route('products.ingredients.index'))->assertOk()
            ->assertSee('Agregar Insumo')->assertSee('Alta pendiente de definición.')->assertSee('data-copy-title="Datos Insumos"', false)
            ->assertSee('ui-list__toolbar')->assertSee('ui-list__pagination')->assertSee('Alfa &lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('Eliminar')->assertDontSee('name="nombre"', false);
        $records = $response->viewData('records');
        $this->assertSame([$first->id, $second->id], $records->pluck('id')->all());
        $this->assertSame('Gramos', $records->first()->initial_unit_name);
        $this->assertSame('Kilogramos', $records->first()->final_unit_name);
        $this->assertSame('Paquetes', $records->first()->order_unit_name);
        $this->assertSame($before, $this->snapshot());
        $this->assertSame('Categoría de prueba', $first->category->Nombre);
        $this->assertSame('Gramos', $first->initialUnit->Nombre);
        $this->assertSame('Kilogramos', $first->finalUnit->Nombre);
        $this->assertSame('Paquetes', $first->orderUnit->Nombre);
    }

    public function test_search_sort_pagination_and_validation_are_bounded_and_literal(): void
    {
        for ($i = 0; $i < 302; $i++) {
            $this->ingredient(sprintf('Insumo %03d', $i));
        }
        $literal = $this->ingredient('Té 10%_!');
        $this->actingAs($this->actor(), 'web');
        $first = $this->get(route('products.ingredients.index', ['q' => 'Insumo']))->assertOk()->viewData('records');
        $this->assertSame(302, $first->total());
        $this->assertCount(300, $first);
        $second = $this->get(route('products.ingredients.index', ['q' => 'Insumo', 'page' => 2]))->assertOk()->viewData('records');
        $this->assertSame(['Insumo 300', 'Insumo 301'], $second->pluck('Nombre')->all());
        $response = $this->get(route('products.ingredients.index', ['q' => '%_!']))->assertOk();
        $this->assertSame([$literal->id], $response->viewData('records')->pluck('id')->all());
        foreach (['id', 'Nombre', 'clave_pedido', 'initial_unit_name', 'final_unit_name', 'order_unit_name'] as $sort) {
            $records = $this->get(route('products.ingredients.index', ['sort' => $sort, 'direction' => 'desc']))->assertOk()->viewData('records');
            $this->assertCount(300, $records);
            if ($sort === 'Nombre') {
                $this->assertSame('Té 10%_!', $records->first()->Nombre);
            }
        }
        $this->assertSame(303, $this->get(route('products.ingredients.index', ['q' => 'Gramos']))->viewData('records')->total());
        $this->get(route('products.ingredients.index', ['sort' => 'DROP TABLE', 'direction' => 'drop', 'q' => str_repeat('a', 101), 'page' => 0]))->assertSessionHasErrors(['sort', 'direction', 'q', 'page']);
        $this->get(route('products.ingredients.export', ['format' => 'exe']))->assertSessionHasErrors('format');
    }

    public function test_conversion_update_changes_only_two_fields_and_preserves_every_related_record(): void
    {
        $ingredient = $this->ingredient();
        $conversion = $this->conversion($ingredient);
        $other = $this->conversion($this->ingredient('Otro insumo'));
        foreach (['tbl_inventario_productos', 'tbl_captura_inventario', 'tbl_insumos_gastos', 'tbl_pedidos', 'tbl_historial_completo_if'] as $name) {
            DB::table($name)->insert(['insumo_id' => $ingredient->id, 'Cantidad' => 12.5, 'Fecha' => '2026-10-01']);
        }
        $before = $this->snapshot();
        $original = $conversion->getAttributes();
        $this->actingAs($this->actor(), 'web')->get(route('products.ingredients.conversions', $ingredient))->assertOk()->assertSee('Configurando Insumo')->assertSee('data-edit-conversion', false);
        $this->put($this->updateUrl($ingredient, $conversion), [
            'bodega' => '-,615,/,1000,+,1', 'pedido' => '*,150', 'Nombre' => 'Ignorar', 'Piso' => '*,999', 'insumo_id' => $other->insumo_id, 'id' => $other->id,
            'q' => 'Café', 'sort' => 'Nombre', 'direction' => 'desc', 'page' => 2,
        ])->assertRedirect(route('products.ingredients.index', ['q' => 'Café', 'sort' => 'Nombre', 'direction' => 'desc', 'page' => 2]))->assertSessionHas('ingredient-success', 'Insumo actualizado!!');
        $this->assertSame([...$original, 'Bodega' => '-,615,/,1000,+,1', 'Pedido' => '*,150'], $conversion->fresh()->getAttributes());
        $after = $this->snapshot();
        unset($before[1], $after[1]);
        $this->assertSame($before, $after);
        $this->assertSame('*,25', $other->fresh()->Bodega);
        $this->withCookie(config('session.cookie'), session()->getId())->get(route('products.ingredients.index'))->assertOk()
            ->assertSee('data-feedback-dialog', false)->assertSee('data-duration="1500"', false)->assertSee('ui-feedback-dialog--corner', false)->assertDontSee('data-redirect-url', false);
    }

    public static function invalidConversions(): array
    {
        return [
            ['', '*,1', 'bodega'], [['array'], '*,1', 'bodega'], ['*,1', '', 'pedido'], ['*,1', ['array'], 'pedido'],
            ['*,1,', '*,1', 'bodega'], ['*,1*,2', '*,1', 'bodega'], ['*,1,,+,2', '*,1', 'bodega'],
            ['*,1,+,2,-,3,*,4', '*,1', 'bodega'], ['*,1', '*,1,*,2', 'pedido'],
            ['/,0', '*,1', 'bodega'], ['*,1', '/,00.000', 'pedido'], ['*,1', '*,0.5 ', null],
            ['*,NaN', '*,1', 'bodega'], ['*,1e4', '*,1', 'bodega'], ['*,1;phpinfo()', '*,1', 'bodega'],
            ['*,-2', '*,1', 'bodega'], ['*,1', '*,'.str_repeat('9', 49), 'pedido'], ['*,'.str_repeat('9', 199), '*,1', 'bodega'],
        ];
    }

    public static function compatibleConversions(): array
    {
        return [
            ['*,25', '*,150'], ['/,1000', '/,1000'], ['-,615,/,1000', '+,0.5'],
            ['*,2,+,5,-,1', '-,2'], ['*,'.str_repeat('1', 198), '*,'.str_repeat('1', 48)],
        ];
    }

    #[DataProvider('compatibleConversions')]
    public function test_compatible_operations_and_schema_lengths_are_preserved_as_strings(string $bodega, string $pedido): void
    {
        $ingredient = $this->ingredient();
        $conversion = $this->conversion($ingredient);
        $this->actingAs($this->actor(), 'web')->put($this->updateUrl($ingredient, $conversion), ['bodega' => $bodega, 'pedido' => $pedido])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($bodega, $conversion->fresh()->Bodega);
        $this->assertSame($pedido, $conversion->fresh()->Pedido);
        $this->assertSame('-,615,/,1000', $conversion->fresh()->Piso);
    }

    #[DataProvider('invalidConversions')]
    public function test_invalid_conversion_is_rejected_without_partial_update(mixed $bodega, mixed $pedido, ?string $error): void
    {
        $ingredient = $this->ingredient();
        $conversion = $this->conversion($ingredient);
        $before = $this->snapshot();
        $response = $this->actingAs($this->actor(), 'web')->from(route('products.ingredients.conversions', $ingredient))
            ->put($this->updateUrl($ingredient, $conversion), ['bodega' => $bodega, 'pedido' => $pedido, '_conversion_id' => $conversion->id]);
        if ($error === null) {
            // Laravel's normal trimming is intentionally retained.
            $response->assertSessionHasNoErrors();
            $this->assertSame('*,0.5', $conversion->fresh()->Pedido);
        } else {
            $response->assertSessionHasErrors($error)->assertSessionMissing('ingredient-success');
            $this->assertSame($before, $this->snapshot());
            $this->withCookie(config('session.cookie'), session()->getId())->get(route('products.ingredients.conversions', $ingredient))
                ->assertOk()->assertSee('data-reopen', false)->assertDontSee('data-feedback-dialog', false);
        }
    }

    public function test_duplicate_and_missing_conversions_never_generate_arbitrary_or_default_rows(): void
    {
        $ingredient = $this->ingredient();
        $this->actingAs($this->actor(), 'web');
        $this->get(route('products.ingredients.conversions', $ingredient))->assertOk()->assertSee('no tiene operaciones configuradas')->assertDontSee('data-conversion-edit', false);
        $first = $this->conversion($ingredient);
        $second = $this->conversion($ingredient, ['Bodega' => '*,3']);
        $before = $this->snapshot();
        $this->get(route('products.ingredients.conversions', $ingredient))->assertOk()->assertSee('Existen varias conversiones')->assertDontSee('data-edit-conversion', false);
        $this->put($this->updateUrl($ingredient, $first), ['bodega' => '*,1', 'pedido' => '*,1'])->assertSessionHasErrors('conversion')->assertSessionMissing('ingredient-success');
        $this->assertSame($before, $this->snapshot());
        $second->delete(); // Only isolated fixtures: simulate legacy deleting a conversion after a form was opened.
        $before = $this->snapshot();
        $this->put($this->updateUrl($ingredient, $second), ['bodega' => '*,1', 'pedido' => '*,1'])->assertNotFound();
        $this->assertSame($before, $this->snapshot());
    }

    public function test_unknown_and_cross_ingredient_ids_and_unsupported_writes_are_rejected(): void
    {
        $ingredient = $this->ingredient();
        $other = $this->ingredient('Otro');
        $conversion = $this->conversion($other);
        $before = $this->snapshot();
        $this->actingAs($this->actor(), 'web');
        $this->get('/Insumos/999/conversiones')->assertNotFound();
        $this->put($this->updateUrl($ingredient, $conversion), ['bodega' => '*,1', 'pedido' => '*,1'])->assertNotFound();
        $this->put('/Insumos/999/conversiones/999', ['bodega' => '*,1', 'pedido' => '*,1'])->assertNotFound();
        $this->post('/Insumos', ['nombre' => 'No crear'])->assertStatus(405);
        $this->post('/Insumos/saveDontExist', ['nombre' => 'No crear'])->assertNotFound();
        $this->post('/Insumos/save', ['nombre' => 'No crear'])->assertNotFound();
        $this->put('/Insumos/'.$ingredient->id, ['Nombre' => 'No editar'])->assertNotFound();
        $this->delete($this->updateUrl($other, $conversion))->assertStatus(405);
        $this->delete('/Insumos/'.$ingredient->id)->assertNotFound();
        $this->assertSame($before, $this->snapshot());
    }

    public static function catalogColumns(): array
    {
        return array_map(fn ($column) => [$column], ['unidad_id', 'unidad_final', 'unidad_pedido', 'categorias_insumos_id']);
    }

    #[DataProvider('catalogColumns')]
    public function test_orphaned_catalogs_are_excluded_and_cannot_be_edited(string $column): void
    {
        $valid = $this->ingredient('Válido');
        $invalid = $this->ingredient('Relación inexistente');
        $conversion = $this->conversion($invalid);
        // Simulate inconsistent old data without weakening the application's connection settings.
        Schema::disableForeignKeyConstraints();
        DB::table('tbl_insumos')->where('id', $invalid->id)->update([$column => 999]);
        Schema::enableForeignKeyConstraints();
        $before = $this->snapshot();
        $this->actingAs($this->actor(), 'web');
        $rows = $this->get(route('products.ingredients.index'))->assertOk()->viewData('records');
        $this->assertSame([$valid->id], $rows->pluck('id')->all());
        $this->get(route('products.ingredients.conversions', $invalid))->assertNotFound();
        $this->put($this->updateUrl($invalid, $conversion), ['bodega' => '*,1', 'pedido' => '*,1'])->assertNotFound();
        $this->assertSame($before, $this->snapshot());
    }

    public function test_failed_save_rolls_back_and_does_not_flash_success(): void
    {
        $ingredient = $this->ingredient();
        $conversion = $this->conversion($ingredient);
        $before = $this->snapshot();
        IngredientConversion::updated(function (): void {
            throw new \RuntimeException('Synthetic failure after UPDATE');
        });
        $this->actingAs($this->actor(), 'web')->put($this->updateUrl($ingredient, $conversion), ['bodega' => '*,3', 'pedido' => '/,2'])
            ->assertStatus(500)->assertSessionMissing('ingredient-success');
        $this->assertSame($before, $this->snapshot());
    }

    public function test_real_spreadsheet_exports_full_filtered_sorted_scope_and_safe_text(): void
    {
        for ($i = 0; $i < 505; $i++) {
            $this->ingredient(sprintf('Export %03d', $i));
        }
        $this->ingredient('=1+1');
        $this->ingredient('Fuera');
        $before = $this->snapshot();
        $this->actingAs($this->actor(), 'web');
        $response = $this->get(route('products.ingredients.export', ['format' => 'xlsx', 'q' => 'Export', 'sort' => 'Nombre', 'direction' => 'desc', 'page' => 2]))->assertOk()->assertDownload('insumos.xlsx');
        $workbook = IOFactory::load($response->baseResponse->getFile()->getPathname());
        $sheet = $workbook->getActiveSheet();
        $this->assertSame('Datos Insumos', $sheet->getCell('A1')->getValue());
        $this->assertSame(508, $sheet->getHighestRow());
        $this->assertSame('F', $sheet->getHighestColumn());
        $this->assertSame('Export 504', $sheet->getCell('B4')->getValue());
        $this->assertSame('Export 000', $sheet->getCell('B508')->getValue());
        $this->assertSame('U Fin', $sheet->getCell('E3')->getValue());
        $this->assertSame('Kilogramos', $sheet->getCell('E4')->getValue());
        $this->assertSame('Paquetes', $sheet->getCell('F4')->getValue());
        $workbook->disconnectWorksheets();
        $response = $this->get(route('products.ingredients.export', ['q' => '=1+1']))->assertOk();
        $workbook = IOFactory::load($response->baseResponse->getFile()->getPathname());
        $this->assertSame('=1+1', $workbook->getActiveSheet()->getCell('B4')->getValue());
        $this->assertSame('s', $workbook->getActiveSheet()->getCell('B4')->getDataType());
        $workbook->disconnectWorksheets();
        $this->assertSame($before, $this->snapshot());
    }

    public function test_pdf_is_real_escaped_authorized_and_uses_correct_units(): void
    {
        $this->ingredient('PDF <script>alert(1)</script>');
        $this->ingredient('No exportar');
        $wrapper = new class(app('dompdf'), app('config'), app('files'), app('view')) extends PDF
        {
            public ?\DOMDocument $sourceDom = null;

            public function render(): void
            {
                $this->sourceDom = clone $this->getDomPDF()->getDom();
                parent::render();
            }
        };
        $this->app->instance('dompdf.wrapper', $wrapper);
        $before = $this->snapshot();
        $response = $this->actingAs($this->actor(), 'web')->get(route('products.ingredients.export', ['format' => 'pdf', 'q' => 'PDF', 'page' => 2]))->assertOk()->assertDownload('insumos.pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertSame(0, $wrapper->sourceDom->getElementsByTagName('script')->length);
        $this->assertSame(6, $wrapper->sourceDom->getElementsByTagName('td')->length);
        $this->assertStringContainsString('Kilogramos', $wrapper->sourceDom->textContent);
        $this->assertStringContainsString('Paquetes', $wrapper->sourceDom->textContent);
        $this->assertStringNotContainsString('No exportar', $wrapper->sourceDom->textContent);
        $options = $wrapper->getDomPDF()->getOptions();
        $this->assertFalse($options->getIsRemoteEnabled());
        $this->assertFalse($options->getIsPhpEnabled());
        $this->assertFalse($options->getIsJavascriptEnabled());
        $this->assertSame($before, $this->snapshot());
    }

    public function test_seeder_preserves_unrelated_and_direct_permissions_and_is_repeatable(): void
    {
        Permission::findOrCreate('products.units.view', 'web');
        Role::findByName('admin', 'web')->givePermissionTo('products.units.view');
        $actor = $this->actor('admin');
        $actor->givePermissionTo('products.ingredients.view');
        $this->seed(IngredientsPermissionsSeeder::class);
        $this->seed(IngredientsPermissionsSeeder::class);
        $this->assertSame(3, Permission::count());
        $this->assertTrue(Role::findByName('admin', 'web')->hasPermissionTo('products.units.view'));
        $this->assertTrue($actor->fresh()->hasDirectPermission('products.ingredients.view'));
        $this->assertFalse(Role::findByName('admin', 'web')->hasPermissionTo('products.ingredients.view'));
        $this->actingAs($actor->fresh(), 'web')->get(route('products.ingredients.index'))->assertOk();
    }
}
