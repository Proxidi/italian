<?php

namespace Tests\Feature;

use App\Services\Legacy\ImportSafetyException;
use App\Services\Legacy\ProductsImporter;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\ProductFixtures;
use Tests\TestCase;

class ProductsImportTest extends TestCase
{
    use ProductFixtures;

    private bool $prepared = false;

    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('CORE_IMPORT_MYSQL') !== '1') {
            $this->markTestSkipped('Opt-in MySQL 8.4: usa únicamente las dos bases descartables de etapa 2.');
        }
        $common = ['driver' => 'mysql', 'host' => getenv('CORE_IMPORT_MYSQL_HOST') ?: '127.0.0.1', 'port' => '3306', 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'prefix' => '', 'strict' => true];
        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => [...$common, 'database' => 'italian_stage02_target_tests', 'username' => 'stage02_app', 'password' => 'stage02-test-only'],
            'database.connections.legacy' => [...$common, 'database' => 'italian_stage02_source_tests', 'username' => 'stage02_reader', 'password' => 'stage02-test-only'],
            'database.connections.product_fixtures' => [...$common, 'database' => 'italian_stage02_source_tests', 'username' => 'root', 'password' => ''],
            'legacy_import.allow_test_databases' => true,
            'cache.default' => 'array', 'permission.cache.store' => 'array',
        ]);
        foreach (['mysql', 'legacy', 'product_fixtures'] as $name) {
            DB::purge($name);
        }
        $this->assertSame('italian_stage02_target_tests', DB::selectOne('SELECT DATABASE() AS db')->db);
        $this->assertSame('italian_stage02_source_tests', DB::connection('product_fixtures')->selectOne('SELECT DATABASE() AS db')->db);
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->prepared = true;
        $data = $this->productRows();
        foreach (ProductsImporter::MAP as $source => [$target, $map]) {
            Schema::connection('product_fixtures')->create($source, function (Blueprint $table) use ($map): void {
                $table->charset('latin1');
                $table->collation('latin1_swedish_ci');
                $table->increments('id');
                foreach ($map as $legacy => $native) {
                    if ($legacy !== 'id') {
                        if ($legacy === 'Cantidad') {
                            $table->float($legacy);
                        } elseif ($legacy === 'Costo_Venta') {
                            $table->double($legacy);
                        } else {
                            $table->string($legacy)->nullable();
                        }
                    }
                }
            });
            foreach ($data[$target] as $row) {
                $raw = [];
                foreach ($map as $legacy => $native) {
                    $raw[$legacy] = $row[$native];
                }
                DB::connection('product_fixtures')->table($source)->insert($raw);
            }
        }
    }

    protected function tearDown(): void
    {
        if ($this->prepared) {
            foreach (array_reverse(array_keys(ProductsImporter::MAP)) as $source) {
                Schema::connection('product_fixtures')->dropIfExists($source);
            }
            $this->artisan('migrate:rollback', ['--force' => true])->assertExitCode(0);
        }
        foreach (['mysql', 'legacy', 'product_fixtures'] as $name) {
            DB::purge($name);
        }
        parent::tearDown();
    }

    public function test_readonly_validation_import_precision_ids_sequences_and_idempotence(): void
    {
        DB::connection('product_fixtures')->statement('ALTER TABLE tbl_productos AUTO_INCREMENT=300');
        $queries = [];
        DB::listen(function ($event) use (&$queries): void {
            $queries[] = [$event->connectionName, $event->sql];
        });
        $this->artisan('legacy:validar-productos')->assertExitCode(0);
        $this->artisan('legacy:importar-productos', ['--dry-run' => true])->assertExitCode(0);
        foreach ($queries as [$connection, $sql]) {
            $this->assertMatchesRegularExpression('/^(select|show)\b/i', $sql);
        }
        $this->assertDatabaseCount('inicializaciones_sistema', 0);
        $this->artisan('legacy:importar-productos', ['--lote' => 1])->assertExitCode(0);
        $this->assertDatabaseHas('productos', ['id' => 91, 'clave' => 'SYN-1']);
        $this->assertSame('7.700000000000000000000000', DB::table('productos_insumos')->value('cantidad'));
        $this->assertSame('153.120000000000000000000000', DB::table('insumos')->value('costo_venta'));
        $this->artisan('legacy:importar-productos')->assertExitCode(0);
        $this->assertDatabaseCount('productos_insumos', 1);
        $ddl = (array) DB::selectOne('SHOW CREATE TABLE productos');
        $this->assertStringContainsString('AUTO_INCREMENT=300', $ddl['Create Table']);
        foreach ($queries as [$connection, $sql]) {
            if ($connection === 'legacy') {
                $this->assertMatchesRegularExpression('/^(select|show)\b/i', $sql);
            }
        }
    }

    public function test_conflicts_never_overwrite_native_changes_or_write_partial_catalogs(): void
    {
        $this->artisan('legacy:importar-productos')->assertExitCode(0);
        DB::table('insumos')->update(['costo_venta' => '155.25']);
        $this->artisan('legacy:importar-productos')->assertExitCode(1);
        $this->assertDatabaseHas('insumos', ['costo_venta' => '155.25']);
    }

    public function test_orphans_and_collation_duplicates_block_every_write(): void
    {
        DB::connection('product_fixtures')->table('tbl_productos_insumos')->update(['insumo_id' => 999]);
        $this->artisan('legacy:importar-productos')->assertExitCode(1);
        $this->assertDatabaseCount('medidas', 0);
        DB::connection('product_fixtures')->table('tbl_productos_insumos')->update(['insumo_id' => 71]);
        DB::connection('product_fixtures')->table('tbl_productos')->insert(['id' => 92, 'Nombre' => 'AMERICANO SINTÉTICO', 'Clave' => 'SYN-2', 'familia_id' => 9, 'Estado' => 1]);
        $this->artisan('legacy:importar-productos')->assertExitCode(1);
        $this->assertDatabaseCount('medidas', 0);
    }

    public function test_historical_duplicates_are_preserved_by_id_not_merged(): void
    {
        DB::connection('product_fixtures')->table('tbl_productos_insumos')->insert(['id' => 113, 'producto_id' => 91, 'insumo_id' => 71, 'Cantidad' => 0]);
        $this->artisan('legacy:importar-productos')->assertExitCode(0);
        $this->assertDatabaseCount('productos_insumos', 2);
        $this->assertDatabaseHas('productos_insumos', ['id' => 113, 'cantidad' => 0]);
    }

    public function test_interruption_rolls_back_catalog_and_reservation_allows_retry(): void
    {
        DB::connection()->beforeExecuting(function ($query): void {
            if (str_starts_with(strtolower($query), 'insert into') && str_contains($query, 'productos_insumos')) {
                throw new ImportSafetyException('Interrupción sintética');
            }
        });
        $this->artisan('legacy:importar-productos')->assertExitCode(1);
        $this->assertDatabaseCount('productos', 0);
        $this->assertDatabaseCount('inicializaciones_sistema', 1);
        DB::purge('mysql');
        $this->artisan('legacy:importar-productos')->assertExitCode(0);
        $this->assertDatabaseCount('productos_insumos', 1);
    }

    public function test_consumed_ids_and_nonrepresentable_decimals_are_rejected(): void
    {
        DB::statement('ALTER TABLE medidas AUTO_INCREMENT=80');
        $this->artisan('legacy:importar-productos')->assertExitCode(1);
        $this->assertDatabaseCount('medidas', 0);
        DB::statement('ALTER TABLE medidas AUTO_INCREMENT=1');
        DB::connection('product_fixtures')->table('tbl_insumos')->update(['Costo_Venta' => 1e42]);
        $this->artisan('legacy:importar-productos')->assertExitCode(1);
        $this->assertDatabaseCount('insumos', 0);
    }

    public function test_changed_reserved_source_is_not_silently_reimported(): void
    {
        $this->artisan('legacy:importar-productos')->assertExitCode(0);
        DB::connection('product_fixtures')->table('tbl_insumos')->update(['Costo_Venta' => '155.25']);
        $this->artisan('legacy:importar-productos')->assertExitCode(1);
        $this->assertDatabaseHas('insumos', ['costo_venta' => '153.12']);
    }

    public function test_double_value_is_converted_in_sql_without_php_float_truncation(): void
    {
        DB::connection('product_fixtures')->statement('UPDATE tbl_insumos SET Costo_Venta = 0.10000000000000002');
        $this->artisan('legacy:importar-productos')->assertExitCode(0);
        $this->assertSame('0.100000000000000020000000', DB::table('insumos')->value('costo_venta'));
    }

    public function test_source_changed_during_destination_insert_rolls_back_every_catalog(): void
    {
        DB::connection()->beforeExecuting(function ($sql): void {
            if (str_starts_with(strtolower($sql), 'insert into `productos_insumos`')) {
                DB::connection('product_fixtures')->table('tbl_insumos')->update(['Costo_Venta' => '200']);
            }
        });
        $this->artisan('legacy:importar-productos')->assertExitCode(1);
        $this->assertDatabaseCount('medidas', 0);
        $this->assertDatabaseCount('productos_insumos', 0);
    }
}
