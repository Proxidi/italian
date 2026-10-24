<?php

namespace Tests\Feature;

use App\Models\Gasto;
use App\Models\Usuario;
use App\Queries\CostOfSale;
use App\Support\RemittanceCost;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CoreFunctionalTestCase;
use Tests\Support\ProductFixtures;
use Tests\Support\RemittanceFixtures;

class NativeRemittancesTest extends CoreFunctionalTestCase
{
    use ProductFixtures, RemittanceFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedProductRows();
        $this->nativeUser('root', ['id' => 1]);
        DB::table('insumos')->where('id', 71)->update(['pedido' => 0]);
        $this->seedRemittanceRows();
    }

    public function test_weighted_cost_inclusive_dates_plazas_concepts_and_no_order_writes(): void
    {
        DB::table('plazas')->insert(['id' => 2, 'nombre' => 'Otra plaza', 'acronimo' => 'OTR']);
        $concept = (array) DB::table('conceptos_gastos')->first();
        $concept['id'] = 15;
        $concept['nombre'] = 'Segundo concepto del mismo insumo';
        DB::table('conceptos_gastos')->insert($concept);
        DB::table('gastos')->insert([
            $this->expenseRow(['id' => 101, 'cantidad' => '3', 'importe' => '90', 'fecha' => '2026-10-25', 'estado' => 0, 'concepto_gasto_id' => 15]),
            $this->expenseRow(['id' => 102, 'cantidad' => '1', 'importe' => '999', 'fecha' => '2026-10-18']),
            $this->expenseRow(['id' => 103, 'cantidad' => '1', 'importe' => '999', 'fecha' => '2026-10-26']),
            $this->expenseRow(['id' => 104, 'cantidad' => '2', 'importe' => '800', 'plaza_id' => 2]),
        ]);
        $before = DB::table('insumos')->value('costo_venta');
        $rows = app(CostOfSale::class)->query(['mode' => 'remittances', 'date' => '2026-10-20'])->get();
        $this->assertCount(2, $rows);
        $this->assertEquals(38, $rows->where('plaza_id', 1)->first()->Cost);
        $this->assertEquals(400, $rows->where('plaza_id', 2)->first()->Cost);
        $this->assertSame($before, DB::table('insumos')->value('costo_venta'));
        $this->assertSame(71, Gasto::findOrFail(101)->concepto->insumo->id);
        $this->assertSame(1, Gasto::findOrFail(100)->plaza->id);
        $this->assertSame(1, Gasto::findOrFail(100)->usuario->id);
        $this->assertSame(2, Gasto::findOrFail(100)->concepto->insumo->expenseConcepts()->count());
    }

    public function test_exact_decimals_round_only_for_display_and_do_not_use_float_on_mysql(): void
    {
        DB::table('gastos')->where('id', 100)->update(['importe' => '0.300000000000000003', 'cantidad' => '0.1']);
        $cost = app(CostOfSale::class)->query(['mode' => 'remittances', 'date' => '2026-10-20'])->first()->Cost;
        if ($this->mysql) {
            $this->assertIsString($cost);
            $this->assertStringStartsWith('3.00000000000000003', $cost);
            $this->assertSame('0.300000000000000003000000', Gasto::findOrFail(100)->importe);
        } else {
            $this->assertEqualsWithDelta(3, (float) $cost, 1e-12);
        }
        $this->assertSame('1,234.568', RemittanceCost::format('1234.5675'));
        $this->assertSame('-1.235', RemittanceCost::format('-1.2345'));
        $this->assertSame('9,999,999,999,999,999.001', RemittanceCost::format('9999999999999999.001'));
    }

    public function test_no_purchases_zero_totals_and_zero_denominator_have_verified_distinct_states(): void
    {
        $query = app(CostOfSale::class);
        $filters = ['mode' => 'remittances', 'date' => '2026-11-15'];
        $this->assertEquals(0, $query->query($filters)->first()->Cost);
        DB::table('gastos')->where('id', 100)->update(['cantidad' => '0', 'importe' => '0']);
        $this->assertEquals(0, $query->query(['mode' => 'remittances', 'date' => '2026-10-20'])->first()->Cost);
        DB::table('gastos')->where('id', 100)->update(['importe' => '20']);
        $this->assertNull($query->query(['mode' => 'remittances', 'date' => '2026-10-20'])->first()->Cost);
        $this->actingAs(Usuario::find(1))->get(route('products.cost-of-sale.index', ['mode' => 'remittances', 'date' => '2026-10-20']))
            ->assertOk()->assertSee('No calculable (cantidad total cero)')->assertDontSee('pendiente de migrar');
    }

    public function test_filters_http_ajax_search_sort_and_flag_exclusions(): void
    {
        $this->actingAs(Usuario::find(1));
        $params = ['mode' => 'remittances', 'date' => '2026-10-20', 'plaza' => 1, 'q' => '$50.000', 'sort' => 'Cost', 'direction' => 'desc'];
        $this->get(route('products.cost-of-sale.index', $params))->assertOk()->assertSee('$50.000')->assertSee('2026-10-19')->assertSee('2026-10-25');
        $this->get(route('products.cost-of-sale.index', $params), ['X-Listing-Fragment' => 'listing'])
            ->assertOk()->assertHeader('X-Listing-Fragment', 'listing')->assertDontSee('<html', false)->assertSee('$50.000')->assertDontSee('data-edit-cost', false);
        $this->putJson(route('products.cost-of-sale.update', 71), ['mode' => 'remittances', 'cost' => '1'])->assertForbidden();
        $this->assertEquals(153.12, DB::table('insumos')->value('costo_venta'));
        foreach (['pedido' => 1, 'pedido_pasteleria' => 1, 'analisis_cv' => 0] as $field => $value) {
            DB::table('insumos')->where('id', 71)->update(['pedido' => 0, 'pedido_pasteleria' => 0, 'analisis_cv' => 1, $field => $value]);
            $this->assertCount(0, app(CostOfSale::class)->query(['mode' => 'remittances'])->get());
        }
    }

    public static function roles(): array
    {
        return array_map(fn ($role) => [$role], ['root', 'superadmin', 'admin', 'submanager', 'socio']);
    }

    #[DataProvider('roles')]
    public function test_permissions_are_unchanged_and_no_capture_routes_exist(string $role): void
    {
        $this->actingAs($this->nativeUser($role));
        $this->get(route('products.cost-of-sale.index', ['mode' => 'remittances']))->assertStatus($role === 'root' ? 200 : 403);
        $this->post('/gastos', [])->assertNotFound();
    }

    public function test_foreign_keys_protect_history_and_orphans(): void
    {
        foreach ([fn () => DB::table('gastos')->insert($this->expenseRow(['id' => 999, 'concepto_gasto_id' => 999])), fn () => DB::table('conceptos_gastos')->where('id', 14)->delete(), fn () => DB::table('insumos')->where('id', 71)->delete()] as $operation) {
            try {
                $operation();
                $this->fail('La FK no protegió el historial');
            } catch (QueryException) {
                $this->assertDatabaseCount('gastos', 1);
            }
        }
    }

    public function test_schema_indexes_and_decimal_storage_are_present(): void
    {
        if ($this->mysql) {
            $columns = DB::select("SELECT COLUMN_NAME AS name, DATA_TYPE AS type, NUMERIC_PRECISION AS p, NUMERIC_SCALE AS s FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='gastos'");
            $amount = collect($columns)->firstWhere('name', 'importe');
            $this->assertSame('decimal', $amount->type);
            $this->assertSame(65, (int) $amount->p);
            $this->assertSame(24, (int) $amount->s);
            $indexes = collect(DB::select('SHOW INDEX FROM gastos'))->pluck('Key_name')->all();
        } else {
            $indexes = collect(DB::select("PRAGMA index_list('gastos')"))->pluck('name')->all();
        }
        $this->assertContains('gastos_plaza_periodo_concepto', $indexes);
        $this->assertContains('gastos_periodo_concepto_plaza', $indexes);
    }
}
