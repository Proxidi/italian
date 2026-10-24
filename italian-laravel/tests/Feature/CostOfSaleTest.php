<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\Usuario;
use App\Queries\CostOfSale;
use Database\Seeders\CostOfSalePermissionsSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\NativeCoreFixtures;
use Tests\TestCase;

class CostOfSaleTest extends TestCase
{
    use NativeCoreFixtures;

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
        foreach (['medidas', 'categorias_insumos'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->increments('id');
                $table->string('nombre', 50);
            });
        }
        $this->createNativeCoreSchema();
        Schema::create('insumos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre', 100);
            $table->string('clave_pedido', 45);
            foreach (['categorias_insumos_id' => 'categorias_insumos', 'unidad_id' => 'medidas', 'unidad_final' => 'medidas', 'unidad_pedido' => 'medidas'] as $column => $target) {
                $table->integer($column);
                $table->foreign($column)->references('id')->on($target);
            }
            $table->boolean('inv_produccion')->default(true);
            $table->boolean('inv_fisico')->default(true);
            $table->boolean('pedido')->default(true);
            $table->boolean('pedido_pasteleria');
            $table->integer('categoria_pedido_id')->default(1);
            $table->integer('categoria_quebranto_id')->default(1);
            $table->boolean('analisis_cv');
            $table->double('costo_venta');
            $table->integer('orden_captura_inventario')->default(0);
        });
        (require database_path('migrations/2026_10_24_000003_create_remittance_cost_history_tables.php'))->up();
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
        DB::table('plazas')->insert(['id' => 1, 'nombre' => 'Plaza sintética', 'acronimo' => 'PRU']);
        DB::table('familias_gastos')->insert(['id' => 1, 'clave' => 'GDF', 'nombre' => 'Fabricación']);
        DB::table('grupos_gastos')->insert(['id' => 1, 'familia_id' => 1, 'nombre' => 'Abarrotes']);
        DB::table('grupos_captura_gastos')->insert(['id' => 1, 'nombre' => 'Managers']);
        DB::table('formas_pago')->insert(['id' => 1, 'nombre' => 'Efectivo']);
        DB::table('categorias_insumos')->insert(['id' => 1, 'nombre' => 'Categoría de prueba']);
        DB::table('medidas')->insert([['id' => 1, 'nombre' => 'Gramos'], ['id' => 5, 'nombre' => 'Kilogramos'], ['id' => 6, 'nombre' => 'Paquetes']]);
        foreach (['root', 'superadmin', 'admin', 'submanager', 'socio'] as $role) {
            Role::findOrCreate($role, 'web');
        }
        $this->seed(CostOfSalePermissionsSeeder::class);
    }

    protected function actor(string $role = 'root'): Usuario
    {
        return $this->nativeUser($role);
    }

    protected function ingredient(string $name = 'Café de prueba', array $overrides = []): Ingredient
    {
        $id = DB::table('insumos')->insertGetId([
            'nombre' => $name, 'clave_pedido' => 'CAF', 'categorias_insumos_id' => 1, 'unidad_id' => 1, 'unidad_final' => 5, 'unidad_pedido' => 6,
            'pedido_pasteleria' => 0, 'analisis_cv' => 1, 'costo_venta' => 155.25, ...$overrides,
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
        if (! Usuario::whereKey(500)->exists()) {
            $this->nativeUser(attributes: ['id' => 500]);
        }
        $item = DB::table('conceptos_gastos')->insertGetId(['insumo_id' => $ingredient->id, 'unidad_id' => 1, 'grupo_id' => 1, 'grupo_captura_id' => 1, 'nombre' => 'Concepto', 'stock_minimo' => 0, 'stock_maximo' => 0, 'bandera_stocks' => 0]);
        DB::table('gastos')->insert(['usuario_id' => 500, 'plaza_id' => $plaza, 'concepto_gasto_id' => $item, 'forma_pago_id' => 1, 'importe' => $amount, 'cantidad' => $quantity, 'fecha' => $date, 'estado' => $state, 'modificacion' => 0, 'nueva_cantidad' => 0, 'antigua_cantidad' => 0, 'notas' => '', 'archivos' => '', 'folio' => '', 'corte' => 0]);
    }

    public function test_orders_use_stored_cost_and_correct_flag_groups_without_formulas(): void
    {
        $this->ingredient('Pedido café');
        $this->ingredient('Pastelería', ['pedido' => 0, 'pedido_pasteleria' => 1, 'costo_venta' => 13.456]);
        $this->ingredient('Excluido', ['analisis_cv' => 0]);
        $this->ingredient('Remesas', ['pedido' => 0]);
        $this->actingAs($this->actor());
        $this->get(route('products.cost-of-sale.index'))->assertOk()->assertSee('Pedido café')->assertSee('Pastelería')
            ->assertSee('$155.25')->assertSee('$13.46')->assertDontSee('Excluido')->assertDontSee('data-record-row><td>4', false);
        $this->assertSame(2, app(CostOfSale::class)->query([])->count());
    }

    public function test_remittances_use_all_states_and_inclusive_week_and_plaza(): void
    {
        DB::table('plazas')->insert(['id' => 2, 'nombre' => 'Otra plaza', 'acronimo' => 'OTR']);
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
        $this->assertEquals(38, $rows->where('id', $item->id)->where('plaza_id', 1)->first()->Cost);
        $this->actingAs($this->actor())->get(route('products.cost-of-sale.index', ['mode' => 'remittances', 'date' => '2026-10-20', 'plaza' => 1]))
            ->assertOk()->assertDontSee('pendiente de migrar')->assertSee('2026-10-19')->assertSee('2026-10-25')->assertDontSee('data-edit-cost', false)->assertSee('$38.000');
    }

    public function test_zero_denominator_with_amount_is_explicitly_not_calculable(): void
    {
        $item = $this->ingredient('Inconsistente', ['pedido' => 0]);
        $this->expense($item, 20, 0);
        $this->actingAs($this->actor())->get(route('products.cost-of-sale.index', ['mode' => 'remittances', 'date' => '2026-10-20']))
            ->assertOk()->assertSee('No calculable (cantidad total cero)')->assertDontSee('$0.000');
        $this->assertNull(app(CostOfSale::class)->query(['mode' => 'remittances', 'date' => '2026-10-20'])->first()->Cost);
    }

    public function test_iso_week_uses_the_date_year_correctly_at_year_boundary(): void
    {
        $this->assertSame(['2020-12-28', '2021-01-03'], app(CostOfSale::class)->week('2021-01-01'));
        $this->assertSame(['2025-12-29', '2026-01-04'], app(CostOfSale::class)->week('2025-12-31'));
    }

    public function test_remittance_filters_preserve_http_and_fragment_interfaces(): void
    {
        DB::table('plazas')->insert(['id' => 2, 'nombre' => 'Otra', 'acronimo' => 'OTR']);
        $a = $this->ingredient('Café A', ['pedido' => 0]);
        $b = $this->ingredient('Café B', ['pedido' => 0]);
        $this->ingredient('Té', ['pedido' => 0]);
        $this->expense($a, 10, 3);
        $this->expense($b, 50, 2);
        $this->actingAs($this->actor());
        $params = ['mode' => 'remittances', 'date' => '2026-10-20', 'plaza' => 1, 'q' => 'Café', 'sort' => 'Cost', 'direction' => 'desc'];
        $full = $this->get(route('products.cost-of-sale.index', $params))->assertOk()->assertSee('$25.000');
        $partial = $this->withHeader('X-Listing-Fragment', 'listing')->get(route('products.cost-of-sale.index', $params))->assertOk()
            ->assertHeader('X-Listing-Fragment', 'listing')->assertDontSee('<html', false)->assertDontSee('app-sidebar', false)
            ->assertSee('$25.000')->assertSee('name="plaza"', false)->assertSee('value="Café"', false)->assertDontSee('data-record-row><td>3', false);
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
        $this->ingredient('pedido', ['costo_venta' => 1234.5678]);
        $item = $this->ingredient('Remesa', ['pedido' => 0]);
        $this->expense($item, 12345.678, 10);
        $this->actingAs($this->actor())->get(route('products.cost-of-sale.index', ['q' => '$1,234.57']))
            ->assertOk()->assertSee('data-record-row', false)->assertSee('$1,234.57');
        $this->get(route('products.cost-of-sale.index', ['mode' => 'remittances', 'date' => '2026-10-20', 'q' => '$1,234.568']))
            ->assertOk()->assertSee('data-record-row', false)->assertSee('$1,234.568</td>', false)->assertDontSee('pendiente de migrar');
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
        $this->assertEquals(155.25, $item->fresh()->costo_venta);
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
        $expenses = DB::table('gastos')->get()->toJson();
        $this->actingAs($this->actor());
        $this->get(route('products.cost-of-sale.edit', $item))->assertOk()->assertSee('data-cost-form', false)->assertSee('name="_token"', false)->assertSee('name="_method" value="PUT"', false);
        $this->put(route('products.cost-of-sale.update', $item), ['cost' => 1.234567, 'q' => 'Café', 'direction' => 'desc'])->assertRedirect(route('products.cost-of-sale.index', ['q' => 'Café', 'direction' => 'desc']));
        $after = $item->fresh()->getAttributes();
        $this->assertEquals(1.234567, $after['costo_venta']);
        unset($before['costo_venta'], $after['costo_venta']);
        $this->assertSame($before, $after);
        $this->assertSame($expenses, DB::table('gastos')->get()->toJson());
        $this->putJson(route('products.cost-of-sale.update', $item), ['cost' => 0])->assertOk()->assertJsonPath('message', 'Costo actualizado.');
        $this->assertEquals(0, $item->fresh()->costo_venta);
    }

    public function test_finite_negative_cost_is_numeric_in_legacy_and_is_not_silently_restricted(): void
    {
        $item = $this->ingredient();
        $this->actingAs($this->actor())->putJson(route('products.cost-of-sale.update', $item), ['cost' => -0.125])->assertOk();
        $this->assertEquals(-0.125, $item->fresh()->costo_venta);
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
        $this->assertEquals(155.25, $item->fresh()->costo_venta);
    }

    public function test_non_order_and_changed_eligibility_and_missing_ids_cannot_be_edited(): void
    {
        $item = $this->ingredient('Remesa', ['pedido' => 0]);
        $this->actingAs($this->actor())->get(route('products.cost-of-sale.edit', $item))->assertNotFound();
        $this->putJson(route('products.cost-of-sale.update', $item), ['cost' => 3])->assertNotFound();
        $item = $this->ingredient('pedido', ['analisis_cv' => 0]);
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
