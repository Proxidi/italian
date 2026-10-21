<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\User;
use App\Queries\CostOfSale;
use Database\Seeders\CostOfSalePermissionsSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CostOfSaleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // A new, explicit in-memory connection, before the first DB operation. Never run migrations/imports.
        config([
            'database.default' => 'cost_tests',
            'database.connections.cost_tests' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true],
            'cache.default' => 'array', 'session.driver' => 'array', 'permission.cache.store' => 'array',
            'excel.temporary_files.local_path' => sys_get_temp_dir(),
        ]);
        DB::purge('cost_tests');
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
        Schema::create('tbl_insumos_gastos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('insumo_id');
            $table->integer('unidad_id')->default(1);
        });
        Schema::create('tbl_gastos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('plaza_id');
            $table->integer('insumo_gasto_id');
            $table->float('Importe');
            $table->float('Cantidad');
            $table->date('Fecha');
            $table->boolean('Estado')->default(true);
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
        $this->seed(CostOfSalePermissionsSeeder::class);
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

    protected function tearDown(): void
    {
        DB::purge('cost_tests');
        parent::tearDown();
    }

    protected function expense(Ingredient $ingredient, float $amount, float $quantity, string $date = '2026-10-20', int $plaza = 1, int $state = 1): void
    {
        $item = DB::table('tbl_insumos_gastos')->insertGetId(['insumo_id' => $ingredient->id]);
        DB::table('tbl_gastos')->insert(['plaza_id' => $plaza, 'insumo_gasto_id' => $item, 'Importe' => $amount, 'Cantidad' => $quantity, 'Fecha' => $date, 'Estado' => $state]);
    }

    public function test_orders_use_stored_cost_and_correct_flag_groups_without_formulas(): void
    {
        $this->ingredient('Pedido café');
        $this->ingredient('Pastelería', ['pedido' => 0, 'Pedido_Pasteleria' => 1, 'Costo_Venta' => 13.456]);
        $this->ingredient('Excluido', ['Analisis_CV' => 0]);
        $this->ingredient('Remesas', ['pedido' => 0]);
        $this->actingAs($this->actor());
        $this->get(route('products.cost-of-sale.index'))->assertOk()->assertSee('Pedido café')->assertSee('Pastelería')
            ->assertSee('$155.25')->assertSee('$13.46')->assertDontSee('Excluido')->assertDontSee('data-record-row><td>4', false);
        $this->assertSame(2, app(CostOfSale::class)->query([])->count());
    }

    public function test_remittances_are_weighted_by_quantity_across_mappings_and_include_zero_rows_and_all_states(): void
    {
        DB::table('tbl_plazas')->insert(['id' => 2, 'Nombre' => 'Otra plaza']);
        $item = $this->ingredient('Azúcar', ['pedido' => 0]);
        $this->ingredient('Sin compras', ['pedido' => 0]);
        $this->ingredient('Pedido excluido');
        $this->expense($item, 100, 2, '2026-10-19');
        $this->expense($item, 90, 3, '2026-10-25', state: 0);
        $this->expense($item, 999, 1, '2026-10-18');
        $this->expense($item, 999, 1, '2026-10-26');
        $this->expense($item, 800, 2, plaza: 2);
        $query = app(CostOfSale::class);
        $rows = $query->query(['mode' => 'remittances', 'date' => '2026-10-20'])->get();
        $this->assertCount(4, $rows);
        $this->assertEquals(38, $rows->first(fn ($row) => $row->id === $item->id && $row->plaza_id === 1)->Cost);
        $this->assertEquals(400, $rows->first(fn ($row) => $row->id === $item->id && $row->plaza_id === 2)->Cost);
        $this->assertEquals(0, $rows->last()->Cost);
        $this->actingAs($this->actor())->get(route('products.cost-of-sale.index', ['mode' => 'remittances', 'date' => '2026-10-20', 'plaza' => 1]))
            ->assertOk()->assertSee('$38.000')->assertSee('2026-10-19')->assertSee('2026-10-25')->assertDontSee('data-edit-cost', false);
    }

    public function test_zero_denominator_is_not_reported_as_a_valid_zero_cost(): void
    {
        $item = $this->ingredient('Inconsistente', ['pedido' => 0]);
        $this->expense($item, 20, 0);
        $this->actingAs($this->actor())->get(route('products.cost-of-sale.index', ['mode' => 'remittances', 'date' => '2026-10-20']))
            ->assertOk()->assertSee('No calculable (cantidad total cero)');
        $this->assertNull(app(CostOfSale::class)->query(['mode' => 'remittances', 'date' => '2026-10-20'])->first()->Cost);
    }

    public function test_iso_week_uses_the_date_year_correctly_at_year_boundary(): void
    {
        $this->assertSame(['2020-12-28', '2021-01-03'], app(CostOfSale::class)->week('2021-01-01'));
        $this->assertSame(['2025-12-29', '2026-01-04'], app(CostOfSale::class)->week('2025-12-31'));
    }

    public function test_combined_remittance_filters_sort_and_fragment_use_the_same_query_as_http(): void
    {
        DB::table('tbl_plazas')->insert(['id' => 2, 'Nombre' => 'Otra']);
        $a = $this->ingredient('Café A', ['pedido' => 0]);
        $b = $this->ingredient('Café B', ['pedido' => 0]);
        $this->ingredient('Té', ['pedido' => 0]);
        $this->expense($a, 10, 3);
        $this->expense($b, 50, 2);
        $this->actingAs($this->actor());
        $params = ['mode' => 'remittances', 'date' => '2026-10-20', 'plaza' => 1, 'q' => 'Café', 'sort' => 'Cost', 'direction' => 'desc'];
        $full = $this->get(route('products.cost-of-sale.index', $params))->assertOk()->assertSeeInOrder(['Café B', 'Café A']);
        $partial = $this->withHeader('X-Listing-Fragment', 'listing')->get(route('products.cost-of-sale.index', $params))->assertOk()
            ->assertHeader('X-Listing-Fragment', 'listing')->assertDontSee('<html', false)->assertDontSee('app-sidebar', false)
            ->assertSeeInOrder(['Café B', 'Café A'])->assertSee('name="plaza"', false)->assertSee('value="Café"', false)->assertDontSee('data-record-row><td>3', false);
        $this->assertStringContainsString($partial->getContent(), $full->getContent());
    }

    public function test_search_wildcards_are_literal_and_output_is_escaped(): void
    {
        $this->ingredient('100% <script>');
        $this->ingredient('100ABC');
        $this->actingAs($this->actor())->get(route('products.cost-of-sale.index', ['q' => '%']))->assertOk()->assertSee('100% &lt;script&gt;', false)->assertDontSee('100ABC');
    }

    public function test_search_includes_displayed_currency_grouping_and_precision(): void
    {
        $this->ingredient('Pedido', ['Costo_Venta' => 1234.5678]);
        $item = $this->ingredient('Remesa', ['pedido' => 0]);
        $this->expense($item, 12345.678, 10);
        $this->actingAs($this->actor())->get(route('products.cost-of-sale.index', ['q' => '$1,234.57']))
            ->assertOk()->assertSee('data-record-row', false)->assertSee('$1,234.57');
        $this->get(route('products.cost-of-sale.index', ['mode' => 'remittances', 'date' => '2026-10-20', 'q' => '$1,234.568']))
            ->assertOk()->assertSee('data-record-row', false)->assertSee('$1,234.568');
    }

    public function test_legacy_cost_tables_have_neither_export_nor_pagination(): void
    {
        for ($i = 0; $i < 90; $i++) {
            $this->ingredient('Costo '.$i);
        }
        $response = $this->actingAs($this->actor())->get(route('products.cost-of-sale.index'))->assertOk();
        $this->assertSame(90, substr_count($response->getContent(), 'data-record-row'));
        $response->assertDontSee('ui-list__pagination', false)->assertDontSee('data-copy-page', false)->assertDontSee('Descargar listado');
    }

    public static function deniedRoles(): array
    {
        return [['superadmin'], ['admin'], ['submanager'], ['socio']];
    }

    #[DataProvider('deniedRoles')]
    public function test_other_roles_cannot_read_or_write_even_with_fragment_header(string $role): void
    {
        $item = $this->ingredient();
        $this->actingAs($this->actor($role))->withHeader('X-Listing-Fragment', 'listing')->get(route('products.cost-of-sale.index'))->assertForbidden();
        $this->putJson(route('products.cost-of-sale.update', $item), ['cost' => 5])->assertForbidden();
        $this->assertEquals(155.25, $item->fresh()->Costo_Venta);
    }

    public function test_guests_are_not_given_a_fragment_and_cannot_write(): void
    {
        $item = $this->ingredient();
        $this->withHeader('X-Listing-Fragment', 'listing')->get(route('products.cost-of-sale.index'))->assertRedirect(route('login'))->assertHeaderMissing('X-Listing-Fragment');
        $this->putJson(route('products.cost-of-sale.update', $item), ['cost' => 5])->assertUnauthorized();
    }

    public function test_read_permission_does_not_grant_edit_and_navigation_reflects_permissions(): void
    {
        $item = $this->ingredient();
        Role::findByName('root', 'web')->revokePermissionTo('products.cost-of-sale.update');
        $this->actingAs($this->actor())->get(route('products.cost-of-sale.index'))->assertOk()->assertDontSee('data-edit-cost', false);
        $this->get(route('products.cost-of-sale.edit', $item))->assertForbidden();
        $this->putJson(route('products.cost-of-sale.update', $item), ['cost' => 3])->assertForbidden();
        $this->get(route('inicio'))->assertOk()->assertSee(route('products.cost-of-sale.index'), false);
    }

    public function test_only_cost_changes_and_http_fallback_preserves_filters(): void
    {
        $item = $this->ingredient();
        $this->expense($item, 100, 2);
        $before = $item->getAttributes();
        $expenses = DB::table('tbl_gastos')->get()->toJson();
        $this->actingAs($this->actor());
        $this->get(route('products.cost-of-sale.edit', $item))->assertOk()->assertSee('data-cost-form', false)->assertSee('name="_token"', false)->assertSee('name="_method" value="PUT"', false);
        $this->put(route('products.cost-of-sale.update', $item), ['cost' => 1.234567, 'q' => 'Café', 'direction' => 'desc'])->assertRedirect(route('products.cost-of-sale.index', ['q' => 'Café', 'direction' => 'desc']));
        $after = $item->fresh()->getAttributes();
        $this->assertEquals(1.234567, $after['Costo_Venta']);
        unset($before['Costo_Venta'], $after['Costo_Venta']);
        $this->assertSame($before, $after);
        $this->assertSame($expenses, DB::table('tbl_gastos')->get()->toJson());
        $this->putJson(route('products.cost-of-sale.update', $item), ['cost' => 0])->assertOk()->assertJsonPath('message', 'Costo actualizado.');
        $this->assertEquals(0, $item->fresh()->Costo_Venta);
    }

    public function test_finite_negative_cost_is_numeric_in_legacy_and_is_not_silently_restricted(): void
    {
        $item = $this->ingredient();
        $this->actingAs($this->actor())->putJson(route('products.cost-of-sale.update', $item), ['cost' => -0.125])->assertOk();
        $this->assertEquals(-0.125, $item->fresh()->Costo_Venta);
    }

    public static function badCosts(): array
    {
        return [[null], [''], ['not numeric'], ['1e309'], [['1']], ['NaN'], ['INF']];
    }

    #[DataProvider('badCosts')]
    public function test_invalid_costs_do_not_write(mixed $value): void
    {
        $item = $this->ingredient();
        $this->actingAs($this->actor())->putJson(route('products.cost-of-sale.update', $item), ['cost' => $value])->assertUnprocessable()->assertJsonValidationErrors('cost');
        $this->assertEquals(155.25, $item->fresh()->Costo_Venta);
    }

    public function test_non_order_and_changed_eligibility_and_missing_ids_cannot_be_edited(): void
    {
        $item = $this->ingredient('Remesa', ['pedido' => 0]);
        $this->actingAs($this->actor())->get(route('products.cost-of-sale.edit', $item))->assertNotFound();
        $this->putJson(route('products.cost-of-sale.update', $item), ['cost' => 3])->assertNotFound();
        $item = $this->ingredient('Pedido', ['Analisis_CV' => 0]);
        $this->putJson(route('products.cost-of-sale.update', $item), ['cost' => 3])->assertNotFound();
        $this->get(route('products.cost-of-sale.edit', 999))->assertNotFound();
    }

    public static function badFilters(): array
    {
        return array_map(fn ($filters) => [$filters], [['mode' => 'x'], ['sort' => 'Cost; DROP TABLE'], ['direction' => 'bad'], ['plaza' => 999], ['date' => '2026-02-30'], ['q' => ['x']], ['date' => '1000-01-01']]);
    }

    #[DataProvider('badFilters')]
    public function test_invalid_filters_are_rejected(array $filters): void
    {
        $this->actingAs($this->actor())->getJson(route('products.cost-of-sale.index', $filters))->assertUnprocessable();
    }

    public function test_permission_seeder_is_repeatable_and_does_not_grant_other_modules(): void
    {
        $this->seed(CostOfSalePermissionsSeeder::class);
        $this->assertSame(['products.cost-of-sale.update', 'products.cost-of-sale.view'], Role::findByName('root', 'web')->permissions()->orderBy('name')->pluck('name')->all());
        foreach (['superadmin', 'admin', 'submanager', 'socio'] as $role) {
            $this->assertCount(0, Role::findByName($role, 'web')->permissions);
        }
    }
}
