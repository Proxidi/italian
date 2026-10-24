<?php

namespace Tests\Feature;

use App\Services\Legacy\ImportSafetyException;
use App\Services\Legacy\RemittancesImporter;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\Support\NativeCoreFixtures;
use Tests\Support\ProductFixtures;
use Tests\Support\RemittanceFixtures;
use Tests\TestCase;

class RemittancesImportTest extends TestCase
{
    use NativeCoreFixtures, ProductFixtures, RemittanceFixtures;

    private bool $prepared = false;

    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('CORE_IMPORT_MYSQL') !== '1') {
            $this->markTestSkipped('Requiere MySQL 8.4 y bases descartables de importación.');
        }
        $common = ['driver' => 'mysql', 'host' => getenv('CORE_IMPORT_MYSQL_HOST') ?: '127.0.0.1', 'port' => '3306', 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'prefix' => '', 'strict' => true];
        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => [...$common, 'database' => 'italian_stage02_target_tests', 'username' => 'stage02_app', 'password' => 'stage02-test-only'],
            'database.connections.legacy' => [...$common, 'database' => 'italian_stage02_source_tests', 'username' => 'stage02_reader', 'password' => 'stage02-test-only'],
            'database.connections.remittance_fixtures' => [...$common, 'database' => 'italian_stage02_source_tests', 'username' => 'root', 'password' => ''],
            'legacy_import.allow_test_databases' => true, 'cache.default' => 'array', 'permission.cache.store' => 'array',
        ]);
        foreach (['mysql', 'legacy', 'remittance_fixtures'] as $name) {
            DB::purge($name);
        }
        $this->assertSame('italian_stage02_target_tests', DB::selectOne('SELECT DATABASE() AS db')->db);
        $this->assertSame('italian_stage02_source_tests', DB::connection('remittance_fixtures')->selectOne('SELECT DATABASE() AS db')->db);
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->prepared = true;
        $this->seedProductRows();
        Role::findOrCreate('root', 'web');
        $this->nativeUser('root', ['id' => 1]);
        foreach (RemittancesImporter::MAP as $source => [$target, $map]) {
            Schema::connection('remittance_fixtures')->create($source, function (Blueprint $table) use ($source, $map): void {
                $table->charset('latin1');
                $table->collation('latin1_swedish_ci');
                if ($source === 'tbl_familia_gastos') {
                    $table->integer('id')->primary();
                } else {
                    $table->increments('id');
                }
                foreach ($map as $legacy => $native) {
                    if ($legacy !== 'id') {
                        if (in_array($native, ['cantidad', 'importe', 'nueva_cantidad', 'antigua_cantidad', 'stock_minimo', 'stock_maximo'])) {
                            $table->float($legacy)->nullable();
                        } else {
                            $table->string($legacy, 400)->nullable();
                        }
                    }
                }
            });
            foreach ($this->remittanceRows()[$target] as $row) {
                $raw = [];
                foreach ($map as $legacy => $native) {
                    $raw[$legacy] = $row[$native];
                }
                DB::connection('remittance_fixtures')->table($source)->insert($raw);
            }
        }
    }

    protected function tearDown(): void
    {
        if ($this->prepared) {
            foreach (array_reverse(array_keys(RemittancesImporter::MAP)) as $source) {
                Schema::connection('remittance_fixtures')->dropIfExists($source);
            }
            $this->artisan('migrate:rollback', ['--force' => true])->assertExitCode(0);
        }
        foreach (['mysql', 'legacy', 'remittance_fixtures'] as $name) {
            DB::purge($name);
        }
        parent::tearDown();
    }

    public function test_dry_run_is_select_show_only_then_import_preserves_ids_relations_dates_and_sequences(): void
    {
        DB::connection('remittance_fixtures')->statement('ALTER TABLE tbl_gastos AUTO_INCREMENT=900');
        $queries = [];
        DB::listen(function ($event) use (&$queries): void {
            $queries[] = [$event->connectionName, $event->sql];
        });
        $this->artisan('legacy:validar-remesas')->assertExitCode(0);
        $this->artisan('legacy:importar-remesas', ['--dry-run' => true])->assertExitCode(0);
        foreach ($queries as [$_, $sql]) {
            $this->assertMatchesRegularExpression('/^(select|show)\b/i', $sql);
        }
        $this->assertDatabaseCount('inicializaciones_sistema', 0);
        $this->assertDatabaseCount('gastos', 0);
        $this->artisan('legacy:importar-remesas', ['--lote' => 1])->assertExitCode(0);
        $this->assertDatabaseHas('gastos', ['id' => 100, 'concepto_gasto_id' => 14, 'usuario_id' => 1, 'plaza_id' => 1, 'fecha' => '2026-10-19', 'archivos' => 'comprobante.jpg,']);
        $this->assertSame('100.000000000000000000000000', DB::table('gastos')->value('importe'));
        $this->assertSame('1.250000000000000000000000', DB::table('conceptos_gastos')->value('stock_minimo'));
        $this->assertStringContainsString('AUTO_INCREMENT=900', ((array) DB::selectOne('SHOW CREATE TABLE gastos'))['Create Table']);
        $this->artisan('legacy:importar-remesas')->assertExitCode(0);
        $this->artisan('legacy:validar-remesas')->assertExitCode(0);
        $this->assertDatabaseCount('gastos', 1);
        foreach ($queries as [$connection, $sql]) {
            if ($connection === 'legacy') {
                $this->assertMatchesRegularExpression('/^(select|show)\b/i', $sql);
            }
        }
    }

    public function test_conflicts_preserve_native_history_and_never_overwrite_products(): void
    {
        $this->artisan('legacy:importar-remesas')->assertExitCode(0);
        DB::table('gastos')->where('id', 100)->update(['importe' => '120']);
        $this->artisan('legacy:importar-remesas')->assertExitCode(1);
        $this->assertDatabaseHas('gastos', ['id' => 100, 'importe' => '120']);
        $this->assertDatabaseHas('insumos', ['id' => 71, 'costo_venta' => '153.12']);
        $this->assertDatabaseCount('productos_insumos', 1);
        $this->assertDatabaseCount('model_has_roles', 1);
    }

    public static function invalidRows(): array
    {
        return [
            ['tbl_gastos', 'usuario_id', 999], ['tbl_gastos', 'plaza_id', 999],
            ['tbl_gastos', 'insumo_gasto_id', 999], ['tbl_gastos', 'unidad_pago_id', 999],
            ['tbl_insumos_gastos', 'insumo_id', 999], ['tbl_insumos_gastos', 'unidad_id', 999],
            ['tbl_insumos_gastos', 'grupo_id', 999], ['tbl_insumos_gastos', 'grupo_rol_id', 999],
            ['tbl_grupo_gastos', 'familia_id', 999], ['tbl_gastos', 'Importe', null],
            ['tbl_gastos', 'Cantidad', '1e-30'], ['tbl_gastos', 'Fecha', '2026-02-30'],
            ['tbl_gastos', 'Fecha', '0000-00-00'], ['tbl_gastos', 'Notas', str_repeat('x', 301)],
        ];
    }

    #[DataProvider('invalidRows')]
    public function test_all_sets_are_prevalidated_before_any_marker_or_data_write(string $table, string $field, mixed $value): void
    {
        DB::connection('remittance_fixtures')->table($table)->update([$field => $value]);
        $this->artisan('legacy:importar-remesas')->assertExitCode(1);
        $this->assertDatabaseCount('familias_gastos', 0);
        $this->assertDatabaseCount('gastos', 0);
        $this->assertDatabaseCount('inicializaciones_sistema', 0);
    }

    public function test_collation_duplicates_and_destination_unique_conflicts_are_rejected(): void
    {
        DB::connection('remittance_fixtures')->table('tbl_grupos_roles')->insert(['id' => 6, 'Nombre' => 'MÁNAGERS']);
        $this->artisan('legacy:importar-remesas')->assertExitCode(1);
        $this->assertDatabaseCount('familias_gastos', 0);
        DB::connection('remittance_fixtures')->table('tbl_grupos_roles')->where('id', 6)->delete();
        DB::table('grupos_captura_gastos')->insert(['id' => 1, 'nombre' => 'MANAGERS']);
        $this->artisan('legacy:importar-remesas')->assertExitCode(1);
        $this->assertDatabaseCount('familias_gastos', 0);
    }

    public function test_multiple_concepts_per_ingredient_are_preserved_not_deduplicated(): void
    {
        $concept = (array) DB::connection('remittance_fixtures')->table('tbl_insumos_gastos')->first();
        $concept['id'] = 15;
        DB::connection('remittance_fixtures')->table('tbl_insumos_gastos')->insert($concept);
        $this->artisan('legacy:importar-remesas')->assertExitCode(0);
        $this->assertDatabaseCount('conceptos_gastos', 2);
        $this->assertSame(2, DB::table('conceptos_gastos')->where('insumo_id', 71)->count());
    }

    public function test_interruption_rolls_back_all_tables_and_reserved_ids_allow_retry(): void
    {
        DB::connection()->beforeExecuting(function ($sql): void {
            if (str_starts_with(strtolower($sql), 'insert into `gastos`')) {
                throw new ImportSafetyException('Fallo sintético');
            }
        });
        $this->artisan('legacy:importar-remesas')->assertExitCode(1);
        $this->assertDatabaseCount('familias_gastos', 0);
        $this->assertDatabaseCount('inicializaciones_sistema', 1);
        DB::purge('mysql');
        $this->artisan('legacy:importar-remesas')->assertExitCode(0);
        $this->assertDatabaseCount('gastos', 1);
    }

    public function test_changed_source_consumed_ids_and_source_changes_during_import_are_rejected(): void
    {
        DB::statement('ALTER TABLE gastos AUTO_INCREMENT=900');
        $this->artisan('legacy:importar-remesas')->assertExitCode(1);
        DB::statement('ALTER TABLE gastos AUTO_INCREMENT=1');
        DB::connection()->beforeExecuting(function ($sql): void {
            if (str_starts_with(strtolower($sql), 'insert into `gastos`')) {
                DB::connection('remittance_fixtures')->table('tbl_gastos')->update(['Importe' => '120']);
            }
        });
        // The source row has already been read at INSERT time. A final source digest
        // must detect this change before the destination transaction commits.
        $this->artisan('legacy:importar-remesas')->assertExitCode(1);
        $this->assertDatabaseCount('gastos', 0);
        DB::purge('mysql');
        $this->artisan('legacy:importar-remesas')->assertExitCode(1);
    }

    public function test_source_account_is_readonly_and_bad_connections_are_rejected(): void
    {
        try {
            DB::connection('legacy')->table('tbl_gastos')->update(['Importe' => '5']);
            $this->fail('La cuenta de origen permitió escribir');
        } catch (QueryException) {
            $this->assertTrue(true);
        }
        config(['database.connections.legacy.username' => 'root', 'database.connections.legacy.password' => '']);
        DB::purge('legacy');
        $this->artisan('legacy:validar-remesas')->assertExitCode(1);
        config(['database.connections.legacy.database' => 'italian_stage02_target_tests']);
        DB::purge('legacy');
        $this->artisan('legacy:validar-remesas')->assertExitCode(1);
    }

    public function test_missing_select_permission_fails_without_destination_writes(): void
    {
        $admin = DB::connection('remittance_fixtures');
        $admin->statement("REVOKE SELECT ON italian_stage02_source_tests.* FROM 'stage02_reader'@'%'");
        try {
            DB::purge('legacy');
            $this->artisan('legacy:validar-remesas')->assertExitCode(1);
            $this->assertDatabaseCount('inicializaciones_sistema', 0);
        } finally {
            $admin->statement("GRANT SELECT ON italian_stage02_source_tests.* TO 'stage02_reader'@'%'");
        }
    }

    public function test_multiple_plazas_periods_and_decimal_history_are_not_reinterpreted(): void
    {
        DB::table('plazas')->insert(['id' => 2, 'nombre' => 'Otra plaza', 'acronimo' => 'OTR']);
        $source = DB::connection('remittance_fixtures');
        $source->table('tbl_gastos')->update(['Cantidad' => '0.125', 'Importe' => '0.31', 'Notas' => 'Revisión histórica', 'Nueva_Cantidad' => '0.3', 'Antigua_Cantidad' => '0.00012345']);
        $row = (array) $source->table('tbl_gastos')->first();
        $row['id'] = 101;
        $row['plaza_id'] = 2;
        $row['Fecha'] = '2026-10-26';
        $row['Estado'] = 0;
        $source->table('tbl_gastos')->insert($row);
        $this->artisan('legacy:importar-remesas', ['--lote' => 1])->assertExitCode(0);
        $this->assertSame('0.310000000000000000000000', DB::table('gastos')->where('id', 100)->value('importe'));
        $this->assertDatabaseHas('gastos', ['id' => 101, 'plaza_id' => 2, 'fecha' => '2026-10-26', 'estado' => 0, 'notas' => 'Revisión histórica']);
        $this->artisan('legacy:validar-remesas')->expectsOutputToContain('agregados_verificados')->assertExitCode(0);
        $this->assertSame(2, DB::table('gastos')->count());
    }

    public function test_sequence_recovery_after_rows_commit_never_reimports_or_reduces_ids(): void
    {
        DB::connection('remittance_fixtures')->statement('ALTER TABLE tbl_gastos AUTO_INCREMENT=900');
        DB::connection()->beforeExecuting(function ($sql): void {
            if (str_starts_with($sql, 'ALTER TABLE `gastos`')) {
                throw new ImportSafetyException('Fallo sintético de reserva final');
            }
        });
        $this->artisan('legacy:importar-remesas')->assertExitCode(1);
        $this->assertDatabaseCount('gastos', 1);
        DB::purge('mysql');
        $this->artisan('legacy:importar-remesas')->assertExitCode(0);
        $this->assertDatabaseCount('gastos', 1);
        $this->assertStringContainsString('AUTO_INCREMENT=900', ((array) DB::selectOne('SHOW CREATE TABLE gastos'))['Create Table']);
    }

    public function test_empty_source_never_invents_defaults_and_partial_source_is_rejected(): void
    {
        DB::connection('remittance_fixtures')->table('tbl_insumos_gastos')->delete();
        $this->artisan('legacy:importar-remesas')->assertExitCode(1);
        $this->assertDatabaseCount('familias_gastos', 0);
        foreach (array_reverse(array_keys(RemittancesImporter::MAP)) as $table) {
            DB::connection('remittance_fixtures')->table($table)->delete();
        }
        $this->artisan('legacy:validar-remesas')->assertExitCode(0);
        $this->artisan('legacy:importar-remesas')->assertExitCode(0);
        foreach (RemittancesImporter::MAP as [$table]) {
            $this->assertDatabaseCount($table, 0);
        }
    }
}
