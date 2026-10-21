<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\Product;
use App\Models\ProductIngredient;
use App\Models\User;
use Database\Seeders\ProductCatalogPermissionsSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'catalog_tests',
            'database.connections.catalog_tests' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true],
            'cache.default' => 'array', 'session.driver' => 'array', 'permission.cache.store' => 'array',
            'excel.temporary_files.local_path' => sys_get_temp_dir(),
        ]);
        DB::purge('catalog_tests');
        $this->app->forgetInstance(PermissionRegistrar::class);
        $this->withoutVite();
        foreach (['tbl_familia_productos', 'tbl_plazas', 'tbl_unidades', 'tbl_categorias_insumos'] as $table) {
            Schema::create($table, function (Blueprint $table) {
                $table->increments('id');
                $table->string('Nombre', 50);
            });
            DB::table($table)->insert(['id' => 1, 'Nombre' => 'Catálogo de prueba']);
        }
        DB::table('tbl_familia_productos')->insert(['id' => 2, 'Nombre' => 'Fríos']);
        DB::table('tbl_unidades')->insert(['id' => 2, 'Nombre' => 'Kilogramos']);
        Schema::create('tbl_productos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('Nombre', 100)->unique();
            $table->string('Clave', 50)->unique();
            $table->integer('familia_id');
            $table->foreign('familia_id')->references('id')->on('tbl_familia_productos');
            $table->tinyInteger('Estado')->default(1);
        });
        Schema::create('tbl_insumos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('Nombre');
            $table->integer('unidad_id')->nullable();
            $table->integer('unidad_final')->nullable();
            $table->integer('categorias_insumos_id')->nullable();
            $table->boolean('inv_produccion')->default(true);
        });
        Schema::create('tbl_productos_insumos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('producto_id');
            $table->foreign('producto_id')->references('id')->on('tbl_productos');
            $table->integer('insumo_id');
            $table->foreign('insumo_id')->references('id')->on('tbl_insumos');
            $table->float('Cantidad');
        });
        // Snapshot records demonstrate that formula writes never rewrite historical consumption.
        Schema::create('tbl_platillos_vendidos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('Clave');
            $table->foreign('Clave')->references('Clave')->on('tbl_productos');
            $table->integer('insumo_id');
            $table->float('Unidad');
            $table->float('Total');
        });
        Schema::create('tbl_usuarios', function (Blueprint $table) {
            $table->increments('id');
            $table->string('Nombre');
            $table->string('Usuario');
            $table->string('Sexo');
            $table->integer('plaza_id');
            $table->integer('rol_id');
        });
        foreach (['roles', 'permissions'] as $table) {
            Schema::create($table, function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
                $table->unique(['name', 'guard_name']);
            });
        }
        foreach (['model_has_roles' => 'role_id', 'model_has_permissions' => 'permission_id'] as $table => $column) {
            Schema::create($table, function (Blueprint $table) use ($column) {
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
        foreach (['root', 'superadmin', 'admin', 'submanager', 'socio'] as $role) {
            Role::findOrCreate($role, 'web');
        }
        $this->seed(ProductCatalogPermissionsSeeder::class);
    }

    protected function tearDown(): void
    {
        Product::flushEventListeners();
        ProductIngredient::flushEventListeners();
        DB::purge('catalog_tests');
        parent::tearDown();
    }

    protected function actor(string $role = 'root'): User
    {
        $id = DB::table('tbl_usuarios')->insertGetId(['Nombre' => 'Gerente de prueba', 'Usuario' => 'prueba', 'Sexo' => 'H', 'plaza_id' => 1, 'rol_id' => 3]);
        $actor = User::findOrFail($id);
        $actor->assignRole($role);

        return $actor;
    }

    protected function product(array $overrides = []): Product
    {
        $id = DB::table('tbl_productos')->count() + 1;

        return Product::create(['Nombre' => 'Producto '.$id, 'Clave' => 'P'.$id, 'familia_id' => 1, 'Estado' => 1, ...$overrides]);
    }

    protected function ingredient(array $overrides = []): Ingredient
    {
        $id = DB::table('tbl_insumos')->insertGetId(['Nombre' => 'Insumo "Café" <b>', 'unidad_id' => 1, 'unidad_final' => 2, 'categorias_insumos_id' => 1, 'inv_produccion' => 1, ...$overrides]);

        return Ingredient::findOrFail($id);
    }

    protected function line(Product $product, Ingredient $ingredient, float $quantity = 7.7): ProductIngredient
    {
        return $product->formulas()->create(['insumo_id' => $ingredient->id, 'Cantidad' => $quantity]);
    }

    protected function snapshot(): array
    {
        return array_map(fn ($table) => DB::table($table)->orderBy('id')->get()->toJson(), ['tbl_productos', 'tbl_productos_insumos', 'tbl_insumos', 'tbl_platillos_vendidos']);
    }

    public function test_guests_cannot_read_export_or_write(): void
    {
        $product = $this->product();
        $line = $this->line($product, $this->ingredient());
        foreach (['products.catalog.index' => [], 'products.catalog.export' => [], 'products.catalog.create' => [], 'products.formulas.index' => [$product], 'products.formulas.edit' => [$product, $line]] as $name => $parameters) {
            $this->get(route($name, $parameters))->assertRedirect(route('login'));
        }
        $before = $this->snapshot();
        $this->postJson(route('products.catalog.store'), [])->assertUnauthorized();
        $this->postJson(route('products.formulas.store', $product), [])->assertUnauthorized();
        $this->putJson(route('products.formulas.update', [$product, $line]), [])->assertUnauthorized();
        $this->assertSame($before, $this->snapshot());
    }

    public static function deniedRoles(): array
    {
        return [['superadmin'], ['admin'], ['submanager'], ['socio']];
    }

    #[DataProvider('deniedRoles')]
    public function test_only_provisioned_roles_can_use_catalog_and_formulas(string $role): void
    {
        $product = $this->product();
        $line = $this->line($product, $this->ingredient());
        $this->actingAs($this->actor($role), 'web');
        $this->get(route('products.catalog.index'))->assertForbidden();
        $this->get(route('products.catalog.export'))->assertForbidden();
        $this->get(route('products.formulas.index', $product))->assertForbidden();
        $this->postJson(route('products.catalog.store'), [])->assertForbidden();
        $this->postJson(route('products.formulas.store', $product), [])->assertForbidden();
        $this->putJson(route('products.formulas.update', [$product, $line]), [])->assertForbidden();
    }

    public function test_permissions_are_independent_and_seeder_preserves_unrelated_and_direct_grants(): void
    {
        $actor = $this->actor('admin');
        $other = Permission::findOrCreate('another-module.view', 'web');
        $actor->givePermissionTo(['products.catalog.view', 'products.formulas.view']);
        Role::findByName('admin', 'web')->givePermissionTo($other);
        $this->seed(ProductCatalogPermissionsSeeder::class);
        $this->seed(ProductCatalogPermissionsSeeder::class);
        $this->assertTrue($actor->fresh()->can('another-module.view'));
        $this->actingAs($actor, 'web')->get(route('products.catalog.index'))->assertOk();
        $product = $this->product();
        $this->get(route('products.formulas.index', $product))->assertOk()->assertDontSee('data-add-formula', false);
        $this->postJson(route('products.catalog.store'), [])->assertForbidden();
        $this->postJson(route('products.formulas.store', $product), [])->assertForbidden();
        $actor->revokePermissionTo('products.catalog.view');
        $this->get(route('products.formulas.index', $product))->assertForbidden();
    }

    public function test_list_defaults_family_filters_literal_search_and_inactive_rows(): void
    {
        $first = $this->product(['Nombre' => '<b>Café_%!</b>', 'Estado' => 0]);
        $second = $this->product(['Nombre' => 'Frappé', 'familia_id' => 2]);
        $this->actingAs($this->actor(), 'web');
        $screen = $this->get(route('products.catalog.index'))->assertOk()->assertSee('&lt;b&gt;Café_%!&lt;/b&gt;', false)->assertSee('catalog-product-inactive');
        $this->assertSame([$first->id, $second->id], $screen->viewData('records')->pluck('id')->all());
        $filtered = $this->get(route('products.catalog.index', ['family' => 2]))->assertOk();
        $this->assertSame([$second->id], $filtered->viewData('records')->pluck('id')->all());
        $literal = $this->get(route('products.catalog.index', ['q' => '_%!']))->assertOk();
        $this->assertSame([$first->id], $literal->viewData('records')->pluck('id')->all());
        $this->get(route('products.catalog.index', ['q' => 'Ausente']))->assertSee('No se encontraron registros.');
        $this->get(route('products.catalog.index', ['sort' => 'Nombre; DROP TABLE tbl_productos']))->assertSessionHasErrors('sort');
        $this->get(route('products.catalog.index', ['family' => 999]))->assertSessionHasErrors('family');
    }

    public function test_all_sorts_are_closed_and_pagination_matches_80_rows(): void
    {
        for ($i = 0; $i < 83; $i++) {
            $this->product();
        }
        $this->actingAs($this->actor(), 'web');
        foreach (['id', 'Clave', 'Nombre', 'Familia'] as $sort) {
            foreach (['asc', 'desc'] as $direction) {
                $response = $this->get(route('products.catalog.index', compact('sort', 'direction')))->assertOk();
                $this->assertCount(80, $response->viewData('records'));
            }
        }
        $page = $this->get(route('products.catalog.index', ['page' => 2]))->assertOk()->viewData('records');
        $this->assertSame([81, 82, 83], $page->pluck('id')->all());
    }

    public function test_product_create_preserves_fields_and_redirect_fallback(): void
    {
        $this->actingAs($this->actor(), 'web');
        $this->post(route('products.catalog.store'), ['nombre' => 'Café Águila', 'clave' => '001', 'familia_id' => 2])->assertRedirect(route('products.catalog.index'))->assertSessionHas('catalog-success');
        $this->assertDatabaseHas('tbl_productos', ['Nombre' => 'Café Águila', 'Clave' => '001', 'familia_id' => 2, 'Estado' => 1]);
        $this->assertDatabaseCount('tbl_productos_insumos', 0);
        $this->assertDatabaseCount('tbl_platillos_vendidos', 0);
        $this->postJson(route('products.catalog.store'), ['nombre' => 'Nuevo', 'clave' => 'N', 'familia_id' => 1])->assertOk()->assertJsonPath('message', 'Producto registrado correctamente!!');
    }

    public static function badProducts(): array
    {
        return [[[]], [['nombre' => '']], [['nombre' => str_repeat('a', 101)]], [['clave' => str_repeat('a', 51)]], [['familia_id' => 999]], [['Estado' => 0]]];
    }

    #[DataProvider('badProducts')]
    public function test_invalid_product_creation_never_writes(array $overrides): void
    {
        $this->actingAs($this->actor(), 'web');
        $data = $overrides === [] ? [] : ['nombre' => 'Nuevo', 'clave' => 'N', 'familia_id' => 1, ...$overrides];
        $this->postJson(route('products.catalog.store'), $data)->assertUnprocessable();
        $this->assertDatabaseCount('tbl_productos', 0);
    }

    public function test_name_and_code_duplicates_are_independently_rejected(): void
    {
        $product = $this->product();
        $this->actingAs($this->actor(), 'web');
        $this->postJson(route('products.catalog.store'), ['nombre' => $product->Nombre, 'clave' => 'OTRA', 'familia_id' => 1])->assertUnprocessable()->assertJsonValidationErrors('nombre');
        $this->postJson(route('products.catalog.store'), ['nombre' => 'Otro', 'clave' => $product->Clave, 'familia_id' => 1])->assertUnprocessable()->assertJsonValidationErrors('clave');
        $this->assertDatabaseCount('tbl_productos', 1);
    }

    public function test_formula_uses_initial_unit_and_existing_picker_contract(): void
    {
        $product = $this->product();
        $ingredient = $this->ingredient();
        $this->line($product, $ingredient);
        $this->ingredient(['Nombre' => 'Sin producción', 'inv_produccion' => 0]);
        $this->ingredient(['Nombre' => 'Sin categoría', 'categorias_insumos_id' => 999]);
        $this->ingredient(['Nombre' => 'Sin unidad final', 'unidad_final' => 999]);
        $this->actingAs($this->actor(), 'web');
        $response = $this->get(route('products.formulas.index', $product))->assertOk()->assertSee('Insumo &quot;Café&quot; &lt;b&gt;', false);
        $this->assertSame([$ingredient->id], $response->viewData('ingredients')->pluck('id')->all());
        $line = $response->viewData('lines')->first();
        $this->assertSame('Catálogo de prueba', $line->ingredient->initialUnit->Nombre);
        $this->assertNotSame($line->ingredient->unidad_id, $line->ingredient->unidad_final);
        $this->assertTrue($line->editable);
    }

    public function test_formula_create_update_zero_and_history_unchanged(): void
    {
        $product = $this->product();
        $ingredient = $this->ingredient();
        DB::table('tbl_platillos_vendidos')->insert(['Clave' => $product->Clave, 'insumo_id' => $ingredient->id, 'Unidad' => 7.7, 'Total' => 77]);
        $history = DB::table('tbl_platillos_vendidos')->get()->toJson();
        $this->actingAs($this->actor(), 'web');
        $this->postJson(route('products.formulas.store', $product), ['insumo_id' => $ingredient->id, 'cantidad' => 7.7, 'unit_id' => 1])->assertOk()->assertJsonPath('message', 'Fórmula registrada correctamente!!');
        $line = $product->formulas()->firstOrFail();
        $this->putJson(route('products.formulas.update', [$product, $line]), ['cantidad' => 0, 'unit_id' => 1])->assertOk();
        $this->assertEquals(0, $line->fresh()->Cantidad);
        $this->assertSame($ingredient->id, $line->fresh()->insumo_id);
        $this->assertSame($history, DB::table('tbl_platillos_vendidos')->get()->toJson());
        $this->assertDatabaseHas('tbl_productos', ['id' => $product->id, 'Estado' => 1]);
    }

    public static function invalidQuantities(): array
    {
        return [[null], [''], ['texto'], [-1], ['INF'], ['NaN'], ['1e309'], ['3.5e38'], ['1e-99'], [[]]];
    }

    #[DataProvider('invalidQuantities')]
    public function test_invalid_quantities_are_rejected_for_both_writes(mixed $quantity): void
    {
        $product = $this->product();
        $ingredient = $this->ingredient();
        $line = $this->line($product, $ingredient);
        $before = $this->snapshot();
        $this->actingAs($this->actor(), 'web');
        $this->postJson(route('products.formulas.store', $product), ['cantidad' => $quantity, 'insumo_id' => $ingredient->id, 'unit_id' => 1])->assertUnprocessable()->assertJsonValidationErrors('cantidad');
        $this->putJson(route('products.formulas.update', [$product, $line]), ['cantidad' => $quantity, 'unit_id' => 1])->assertUnprocessable()->assertJsonValidationErrors('cantidad');
        $this->assertSame($before, $this->snapshot());
    }

    public function test_duplicate_formula_pairs_are_visible_but_never_edited_or_silently_merged(): void
    {
        $product = $this->product();
        $ingredient = $this->ingredient();
        $line = $this->line($product, $ingredient);
        $this->actingAs($this->actor(), 'web');
        $this->postJson(route('products.formulas.store', $product), ['cantidad' => 1, 'insumo_id' => $ingredient->id, 'unit_id' => 1])->assertUnprocessable();
        $this->line($product, $ingredient, 3);
        $before = $this->snapshot();
        $this->get(route('products.formulas.index', $product))->assertOk()->assertSee('Relación inconsistente')->assertDontSee('data-edit-formula=', false);
        $this->putJson(route('products.formulas.update', [$product, $line]), ['cantidad' => 1, 'unit_id' => 1])->assertUnprocessable();
        $this->get(route('products.formulas.edit', [$product, $line]))->assertStatus(409);
        $this->assertSame($before, $this->snapshot());
    }

    public function test_units_cannot_be_substituted_and_changed_units_reject_stale_forms(): void
    {
        $product = $this->product();
        $ingredient = $this->ingredient();
        $line = $this->line($product, $ingredient);
        $this->actingAs($this->actor(), 'web');
        $url = route('products.formulas.update', [$product, $line]);
        $this->putJson($url, ['cantidad' => 2, 'unit_id' => 2])->assertUnprocessable()->assertJsonValidationErrors('unit_id');
        $this->putJson($url, ['cantidad' => 2, 'unit_id' => 1, 'insumo_id' => 2, 'producto_id' => 2, 'unidad_id' => 2])->assertUnprocessable()->assertJsonValidationErrors(['insumo_id', 'producto_id', 'unidad_id']);
        DB::table('tbl_insumos')->where('id', $ingredient->id)->update(['unidad_id' => 2]);
        $this->putJson($url, ['cantidad' => 2, 'unit_id' => 1])->assertUnprocessable();
        $this->assertEquals(7.7, $line->fresh()->Cantidad);
    }

    public function test_missing_and_ineligible_relations_fail_without_writes(): void
    {
        $product = $this->product();
        $ingredient = $this->ingredient(['inv_produccion' => 0]);
        $this->actingAs($this->actor(), 'web');
        $this->postJson(route('products.formulas.store', $product), ['cantidad' => 1, 'insumo_id' => $ingredient->id, 'unit_id' => 1])->assertUnprocessable();
        $this->postJson(route('products.formulas.store', $product), ['cantidad' => 1, 'insumo_id' => 999, 'unit_id' => 1])->assertUnprocessable();
        $this->get('/Productos/999/formulas')->assertNotFound();
        $this->assertDatabaseCount('tbl_productos_insumos', 0);
        $line = $this->line($product, $ingredient);
        $other = $this->product();
        $this->putJson(route('products.formulas.update', [$other, $line]), ['cantidad' => 1, 'unit_id' => 1])->assertNotFound();
        $this->putJson(route('products.formulas.update', [$product, 999]), ['cantidad' => 1, 'unit_id' => 1])->assertNotFound();
        DB::table('tbl_insumos')->where('id', $ingredient->id)->update(['unidad_id' => 999]);
        $this->get(route('products.formulas.index', $product))->assertOk()->assertSee('Unidad inexistente');
        $this->putJson(route('products.formulas.update', [$product, $line]), ['cantidad' => 1, 'unit_id' => 1])->assertUnprocessable();
    }

    public function test_html_fallback_preserves_redirects_values_and_validation_errors(): void
    {
        $product = $this->product();
        $ingredient = $this->ingredient();
        $this->actingAs($this->actor(), 'web');
        $this->get(route('products.catalog.create'))->assertOk()->assertSee('data-reopen', false);
        $this->post(route('products.formulas.store', $product), ['insumo_id' => $ingredient->id, 'cantidad' => 0.125, 'unit_id' => 1])->assertRedirect(route('products.formulas.index', $product));
        $line = $product->formulas()->firstOrFail();
        $this->get(route('products.formulas.edit', [$product, $line]))->assertOk()->assertSee('value="0.125"', false)->assertSee('<noscript>', false);
        $this->from(route('products.formulas.edit', [$product, $line]))->put(route('products.formulas.update', [$product, $line]), ['cantidad' => -1, 'unit_id' => 1])->assertSessionHasErrors('cantidad');
        $this->assertEquals(0.125, $line->fresh()->Cantidad);
    }

    public function test_failed_formula_write_rolls_back(): void
    {
        $product = $this->product();
        $line = $this->line($product, $this->ingredient());
        ProductIngredient::updated(function (): void {
            throw new \RuntimeException('Synthetic failure');
        });
        $this->actingAs($this->actor(), 'web')->putJson(route('products.formulas.update', [$product, $line]), ['cantidad' => 2, 'unit_id' => 1])->assertServerError();
        $this->assertEquals(7.7, $line->fresh()->Cantidad);
    }

    public function test_exports_include_all_filtered_rows_in_order_without_actions_or_executable_formulas(): void
    {
        for ($i = 0; $i < 505; $i++) {
            $this->product(['Nombre' => 'Export '.str_pad((string) $i, 3, '0', STR_PAD_LEFT), 'familia_id' => 2]);
        }
        $this->product(['Nombre' => '=1+1', 'familia_id' => 2]);
        $this->product(['Nombre' => 'Excluir']);
        $this->actingAs($this->actor(), 'web');
        $response = $this->get(route('products.catalog.export', ['format' => 'xlsx', 'family' => 2, 'sort' => 'Nombre', 'direction' => 'desc', 'page' => 2]))->assertOk()->assertDownload('productos.xlsx');
        $book = IOFactory::load($response->baseResponse->getFile()->getPathname());
        $sheet = $book->getActiveSheet();
        $this->assertSame('Datos', $sheet->getCell('A1')->getValue());
        $this->assertSame(['#', 'Clave', 'Nombre', 'Familia'], $sheet->rangeToArray('A3:D3')[0]);
        $this->assertSame(509, $sheet->getHighestRow());
        $this->assertSame('Export 504', $sheet->getCell('C4')->getValue());
        $this->assertSame('=1+1', $sheet->getCell('C509')->getValue());
        $this->assertSame('s', $sheet->getCell('C509')->getDataType());
        $book->disconnectWorksheets();
        $pdf = $this->get(route('products.catalog.export', ['format' => 'pdf', 'q' => '=1+1']))->assertOk()->assertDownload('productos.pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
    }

    public function test_general_edit_state_and_deletion_endpoints_remain_unavailable(): void
    {
        $product = $this->product();
        $line = $this->line($product, $this->ingredient());
        $before = $this->snapshot();
        $this->actingAs($this->actor(), 'web');
        $this->delete(route('products.formulas.update', [$product, $line]))->assertStatus(405);
        $this->delete('/Productos/'.$product->id)->assertNotFound();
        $this->put('/Productos/'.$product->id, ['Nombre' => 'Cambio'])->assertNotFound();
        $this->post('/Productos/activarProducto')->assertNotFound();
        $this->post('/Formulas/delete')->assertNotFound();
        $this->assertSame($before, $this->snapshot());
    }

    public function test_each_write_permission_is_required_even_when_the_catalog_is_readable(): void
    {
        $product = $this->product();
        $line = $this->line($product, $this->ingredient());
        $actor = $this->actor();
        $root = Role::findByName('root', 'web');
        $root->revokePermissionTo(['products.catalog.create', 'products.formulas.create', 'products.formulas.update']);
        $before = $this->snapshot();
        $this->actingAs($actor, 'web')->get(route('products.catalog.index'))->assertOk()->assertDontSee('data-open-product', false);
        $this->get(route('products.formulas.index', $product))->assertOk()->assertDontSee('data-edit-formula ', false);
        $this->postJson(route('products.catalog.store'), [])->assertForbidden();
        $this->postJson(route('products.formulas.store', $product), [])->assertForbidden();
        $this->putJson(route('products.formulas.update', [$product, $line]), [])->assertForbidden();
        $this->assertSame($before, $this->snapshot());
    }

    public function test_orphaned_ingredient_is_visible_and_blocked_and_orphaned_product_is_unwritable(): void
    {
        $product = $this->product();
        $ingredient = $this->ingredient();
        $line = $this->line($product, $ingredient);
        Schema::withoutForeignKeyConstraints(fn () => DB::table('tbl_insumos')->where('id', $ingredient->id)->delete());
        $this->actingAs($this->actor(), 'web')->get(route('products.formulas.index', $product))->assertOk()->assertSee('Insumo inexistente');
        $this->putJson(route('products.formulas.update', [$product, $line]), ['cantidad' => 1, 'unit_id' => 1])->assertNotFound();
        Schema::withoutForeignKeyConstraints(fn () => DB::table('tbl_productos')->where('id', $product->id)->update(['familia_id' => 999]));
        $this->get(route('products.formulas.index', $product))->assertNotFound();
    }

    public function test_existing_nonproduction_formula_can_be_edited_without_enabling_new_associations(): void
    {
        $product = $this->product();
        $ingredient = $this->ingredient(['inv_produccion' => 0]);
        $line = $this->line($product, $ingredient);
        $this->actingAs($this->actor(), 'web')->putJson(route('products.formulas.update', [$product, $line]), ['cantidad' => 2, 'unit_id' => 1])->assertOk();
        $this->assertEquals(2, $line->fresh()->Cantidad);
        $this->assertSame(0, $ingredient->fresh()->inv_produccion);
    }

    public function test_product_creation_failure_rolls_back(): void
    {
        Product::created(function (): void {
            throw new \RuntimeException('Synthetic product failure');
        });
        $this->actingAs($this->actor(), 'web')->postJson(route('products.catalog.store'), ['nombre' => 'Nuevo', 'clave' => 'N', 'familia_id' => 1])->assertServerError();
        $this->assertDatabaseCount('tbl_productos', 0);
    }

    public function test_listing_fragment_preserves_family_search_sort_and_pagination_with_http_fallback(): void
    {
        for ($i = 0; $i < 82; $i++) {
            $this->product(['Nombre' => sprintf('Café %03d', $i)]);
        }
        $this->product(['Nombre' => 'Otro', 'familia_id' => 2]);
        $params = ['family' => 1, 'q' => 'Café', 'sort' => 'Nombre', 'direction' => 'desc', 'page' => 2];
        $url = route('products.catalog.index', $params);
        $this->actingAs($this->actor());
        $full = $this->get($url)->assertOk()->assertSee('<html', false)->assertSeeInOrder(['Café 001', 'Café 000']);
        $partial = $this->withHeader('X-Listing-Fragment', 'listing')->get($url)->assertOk()
            ->assertHeader('X-Listing-Fragment', 'listing')->assertDontSee('<html', false)->assertDontSee('app-sidebar', false)
            ->assertSee('data-async-list', false)->assertSee('family=1', false)->assertSee('sort=Nombre', false)->assertSee('q=Caf', false)
            ->assertSee('method="GET"', false)->assertSeeInOrder(['Café 001', 'Café 000']);
        $this->assertSame(2, substr_count($partial->getContent(), 'data-record-row'));
        $this->assertStringContainsString($partial->getContent(), $full->getContent());
        Role::findByName('root', 'web')->revokePermissionTo('products.catalog.view');
        $this->get($url)->assertForbidden()->assertHeaderMissing('X-Listing-Fragment');
    }
}
