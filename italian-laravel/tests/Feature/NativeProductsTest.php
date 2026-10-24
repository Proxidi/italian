<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\Product;
use App\Models\ProductIngredient;
use App\Queries\CostOfSale;
use App\Rules\ExactProductDecimal;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CoreFunctionalTestCase;
use Tests\Support\ProductFixtures;

class NativeProductsTest extends CoreFunctionalTestCase
{
    use ProductFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedProductRows();
    }

    public function test_native_end_to_end_http_fetch_writes_and_relationships_without_legacy(): void
    {
        $this->actingAs($this->nativeUser('root'), 'web');
        foreach (['products.index', 'products.units.index', 'products.ingredients.index', 'products.catalog.index', 'products.cost-of-sale.index'] as $route) {
            $this->get(route($route))->assertOk();
        }
        foreach (['products.units.index', 'products.ingredients.index', 'products.catalog.index', 'products.cost-of-sale.index'] as $route) {
            $this->get(route($route, ['q' => 'sintético']), ['X-Listing-Fragment' => 'listing'])->assertOk()->assertHeader('X-Listing-Fragment', 'listing');
        }
        $this->get(route('products.formulas.index', ['product' => 91]))->assertOk()->assertSee('Gramos');
        $this->put(route('products.units.update', 7), ['nombre' => 'Gramos editados'])->assertRedirect();
        $this->postJson(route('products.catalog.store'), ['nombre' => 'Nuevo sintético', 'clave' => 'SYN-2', 'familia_id' => 9])->assertOk();
        $new = Product::where('clave', 'SYN-2')->firstOrFail();
        $this->postJson(route('products.formulas.store', ['product' => $new->id]), ['insumo_id' => 71, 'unit_id' => 7, 'cantidad' => '0.000000123456789123456789'])->assertOk();
        if ($this->mysql) {
            $this->assertSame('0.000000123456789123456789', $new->formulas()->first()->cantidad);
        } else {
            // SQLite NUMERIC affinity uses binary floats; exact storage is tested on MySQL.
            $this->assertEqualsWithDelta(0.000000123456789123456789, (float) $new->formulas()->first()->cantidad, 1e-20);
        }
        $this->postJson(route('products.formulas.store', ['product' => $new->id]), ['insumo_id' => 71, 'unit_id' => 7, 'cantidad' => '2'])->assertUnprocessable();
        $this->putJson(route('products.ingredients.conversions.update', ['ingredient' => 71, 'conversion' => 82]), ['bodega' => '*,50', 'pedido' => '/,2'])->assertOk();
        $this->putJson(route('products.cost-of-sale.update', ['ingredient' => 71]), ['cost' => '136.88'])->assertOk();
        $this->assertSame(ExactProductDecimal::normalize('136.88'), Ingredient::findOrFail(71)->costo_venta);
        $this->assertSame(71, ProductIngredient::findOrFail(112)->ingredient->id);
        $this->assertSame(91, ProductIngredient::findOrFail(112)->product->id);
        $this->assertSame(82, Ingredient::findOrFail(71)->conversions->first()->id);
        foreach (['products.units.export', 'products.ingredients.export', 'products.catalog.export'] as $route) {
            foreach (['xlsx', 'pdf'] as $format) {
                $response = $this->get(route($route, ['format' => $format]))->assertOk();
                if ($format === 'xlsx') {
                    unlink($response->baseResponse->getFile()->getPathname());
                }
            }
        }
    }

    public function test_remittances_use_native_history_without_legacy_or_order_writes(): void
    {
        $this->actingAs($this->nativeUser('root'), 'web');
        $this->get(route('products.cost-of-sale.index', ['mode' => 'remittances']))->assertOk()
            ->assertDontSee('pendiente de migrar')->assertDontSee('$0.000')->assertDontSee('data-record-row', false);
        $this->assertCount(0, app(CostOfSale::class)->query(['mode' => 'remittances'])->get());
        $this->putJson(route('products.cost-of-sale.update', ['ingredient' => 71]), ['mode' => 'remittances', 'cost' => '1'])->assertForbidden();
    }

    public static function roles(): array
    {
        return array_map(fn ($role) => [$role], ['root', 'superadmin', 'admin', 'submanager', 'socio']);
    }

    #[DataProvider('roles')]
    public function test_original_permissions_remain_effective(string $role): void
    {
        $this->actingAs($this->nativeUser($role), 'web');
        foreach (['products.units.index', 'products.ingredients.index', 'products.catalog.index', 'products.cost-of-sale.index'] as $route) {
            $this->get(route($route))->assertStatus($role === 'root' ? 200 : 403);
        }
    }

    public function test_invalid_decimal_relations_duplicate_lines_and_delete_protection(): void
    {
        $this->actingAs($this->nativeUser('root'), 'web');
        $this->putJson(route('products.cost-of-sale.update', 71), ['cost' => '1e42'])->assertUnprocessable();
        $this->putJson(route('products.cost-of-sale.update', 71), ['cost' => '1e999999999999'])->assertUnprocessable();
        $this->putJson(route('products.cost-of-sale.update', 71), ['cost' => '0.0000000000000000000000001'])->assertUnprocessable();
        $this->putJson(route('products.formulas.update', ['product' => 91, 'formula' => 112]), ['unit_id' => 999, 'cantidad' => '1'])->assertUnprocessable();
        DB::table('productos_insumos')->insert(['id' => 113, 'producto_id' => 91, 'insumo_id' => 71, 'cantidad' => '0']);
        $this->putJson(route('products.formulas.update', ['product' => 91, 'formula' => 112]), ['unit_id' => 7, 'cantidad' => '1'])->assertUnprocessable();
        $this->delete(route('products.catalog.index').'/91')->assertNotFound();
        $this->assertDatabaseCount('productos_insumos', 2);
    }

    public function test_foreign_keys_reject_orphans_and_referenced_deletions(): void
    {
        try {
            DB::table('productos_insumos')->insert(['id' => 999, 'producto_id' => 91, 'insumo_id' => 999, 'cantidad' => '1']);
            $this->fail('Se aceptó una referencia huérfana');
        } catch (QueryException) {
            $this->assertDatabaseCount('productos_insumos', 1);
        }
        try {
            DB::table('medidas')->where('id', 7)->delete();
            $this->fail('Se borró una medida referenciada');
        } catch (QueryException) {
            $this->assertDatabaseHas('medidas', ['id' => 7]);
        }
    }
}
