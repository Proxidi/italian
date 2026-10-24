<?php

namespace Tests\Feature;

use App\Services\Legacy\ConfigurationImporter;
use App\Services\Legacy\ImportConnections;
use App\Services\Legacy\ImportSafetyException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ConfigurationImportTest extends TestCase
{
    private bool $mysql = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mysql = getenv('CORE_IMPORT_MYSQL') === '1';
        $common = ['driver' => 'mysql', 'host' => getenv('CORE_IMPORT_MYSQL_HOST') ?: '127.0.0.1', 'port' => '3306', 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'prefix' => '', 'strict' => true];
        $sqlite = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''];
        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => $this->mysql ? [...$common, 'database' => 'italian_stage02_target_tests', 'username' => 'stage02_app', 'password' => 'stage02-test-only'] : $sqlite,
            'database.connections.legacy' => $this->mysql ? [...$common, 'database' => 'italian_stage02_source_tests', 'username' => 'stage02_reader', 'password' => 'stage02-test-only'] : $sqlite,
            'database.connections.configuration_source' => $this->mysql ? [...$common, 'database' => 'italian_stage02_source_tests', 'username' => 'root', 'password' => ''] : $sqlite,
            'legacy_import.allow_test_databases' => true,
            'cache.default' => 'array', 'permission.cache.store' => 'array',
        ]);
        foreach (['mysql', 'legacy', 'configuration_source'] as $name) {
            DB::purge($name);
        }
        if ($this->mysql) {
            $this->assertSame('italian_stage02_target_tests', DB::connection('mysql')->selectOne('SELECT DATABASE() AS db')->db);
            $this->assertSame('italian_stage02_source_tests', DB::connection('configuration_source')->selectOne('SELECT DATABASE() AS db')->db);
        } else {
            // Only the MySQL variant tests grants/effective database checks.
            DB::connection('legacy')->setPdo(DB::connection('configuration_source')->getPdo());
            $this->mock(ImportConnections::class)->shouldReceive('check')->andReturn([DB::connection('legacy'), DB::connection('mysql')]);
        }
        (require database_path('migrations/2026_10_24_000001_create_configuration_tables.php'))->up();
        foreach (ConfigurationImporter::MAP as $map) {
            Schema::connection('configuration_source')->create($map['source'], function (Blueprint $table) use ($map): void {
                $table->integer('id');
                $table->string($map['value'])->nullable();
                $table->string('Descripcion')->nullable();
            });
            DB::connection('configuration_source')->table($map['source'])->insert(['id' => 71, $map['value'] => $map['value'] === 'Fecha' ? '2019-01-01' : '7', 'Descripcion' => 'Configuración café']);
        }
    }

    protected function tearDown(): void
    {
        (require database_path('migrations/2026_10_24_000001_create_configuration_tables.php'))->down();
        foreach (ConfigurationImporter::MAP as $map) {
            Schema::connection('configuration_source')->dropIfExists($map['source']);
        }
        foreach (['mysql', 'legacy', 'configuration_source'] as $name) {
            DB::purge($name);
        }
        parent::tearDown();
    }

    public function test_dry_run_idempotence_conflicts_and_source_preservation(): void
    {
        $source = DB::connection('legacy')->table('tbl_notificaciones')->get()->toJson();
        $queries = [];
        DB::listen(function ($event) use (&$queries): void {
            $queries[] = $event->sql;
        });
        $this->artisan('legacy:validar-configuracion')->assertExitCode(0);
        $this->artisan('legacy:importar-configuracion', ['--dry-run' => true])->assertExitCode(0);
        foreach ($queries as $sql) {
            $this->assertMatchesRegularExpression('/^(select|show)\b/i', $sql);
        }
        $this->assertDatabaseCount('configuracion_corte_dlc', 0);
        $this->artisan('legacy:importar-configuracion')->assertExitCode(0);
        $this->artisan('legacy:importar-configuracion')->assertExitCode(0);
        $this->assertDatabaseCount('configuracion_corte_dlc', 1);
        $this->assertDatabaseHas('configuracion_corte_dlc', ['id' => 1, 'numero_dias' => 7, 'descripcion' => 'Configuración café']);
        DB::table('configuracion_corte_dlc')->update(['numero_dias' => 12]);
        $this->artisan('legacy:importar-configuracion')->assertExitCode(1);
        $this->assertDatabaseHas('configuracion_corte_dlc', ['numero_dias' => 12]);
        $this->assertSame($source, DB::connection('legacy')->table('tbl_notificaciones')->get()->toJson());
    }

    public static function invalidValues(): array
    {
        return array_map(fn ($value) => ['tbl_rango_archivos', 'Numero_Dias', $value], [null, '0', '128', '2.5']);
    }

    #[DataProvider('invalidValues')]
    public function test_invalid_values_reject_both_sections(string $table, string $field, mixed $value): void
    {
        DB::connection('configuration_source')->table($table)->update([$field => $value]);
        $this->artisan('legacy:importar-configuracion')->assertExitCode(1);
        $this->assertDatabaseCount('configuracion_corte_dlc', 0);
        $this->assertDatabaseCount('configuracion_notificaciones', 0);
    }

    public function test_empty_multiple_invalid_dates_and_partial_destination(): void
    {
        $source = DB::connection('configuration_source');
        foreach (['0000-00-00', '2025-02-29', '01/01/2019', null] as $date) {
            $source->table('tbl_notificaciones')->update(['Fecha' => $date]);
            $this->artisan('legacy:importar-configuracion')->assertExitCode(1);
            $this->assertDatabaseCount('configuracion_corte_dlc', 0);
        }
        $source->table('tbl_notificaciones')->delete();
        $this->artisan('legacy:importar-configuracion')->assertExitCode(1);
        $source->table('tbl_notificaciones')->insert([
            ['id' => 1, 'Fecha' => '2019-01-01', 'Descripcion' => 'Configuración café'],
            ['id' => 2, 'Fecha' => '2019-01-01', 'Descripcion' => 'Ambigua'],
        ]);
        $this->artisan('legacy:importar-configuracion')->assertExitCode(1);
        $source->table('tbl_notificaciones')->where('id', 2)->delete();
        DB::table('configuracion_corte_dlc')->insert(['id' => 1, 'numero_dias' => 7, 'descripcion' => 'Configuración café']);
        $this->artisan('legacy:importar-configuracion')->assertExitCode(0);
        $this->assertDatabaseCount('configuracion_notificaciones', 1);
    }

    public function test_interruption_rolls_back_first_insert_and_allows_retry(): void
    {
        $target = DB::connection('mysql');
        $target->beforeExecuting(function ($query): void {
            if (str_starts_with(strtolower($query), 'insert into') && str_contains($query, 'configuracion_notificaciones')) {
                throw new ImportSafetyException('Interrupción sintética');
            }
        });
        $this->artisan('legacy:importar-configuracion')->assertExitCode(1);
        $this->assertDatabaseCount('configuracion_corte_dlc', 0);
        // A new connection/process has no injected interruption.
        if ($this->mysql) {
            DB::purge('mysql');
            $this->artisan('legacy:importar-configuracion')->assertExitCode(0);
        }
    }

    public function test_ambiguous_destination_and_invalid_descriptions_are_rejected(): void
    {
        DB::connection('configuration_source')->table('tbl_rango_archivos')->update(['Descripcion' => str_repeat('x', 51)]);
        $this->artisan('legacy:importar-configuracion')->assertExitCode(1);
        $this->assertDatabaseCount('configuracion_corte_dlc', 0);
        DB::connection('configuration_source')->table('tbl_rango_archivos')->update(['Descripcion' => 'Configuración café']);
        DB::table('configuracion_corte_dlc')->insert([
            ['id' => 1, 'numero_dias' => 7, 'descripcion' => 'Configuración café'],
            ['id' => 2, 'numero_dias' => 7, 'descripcion' => 'Ambigua'],
        ]);
        $this->artisan('legacy:importar-configuracion')->assertExitCode(1);
        $this->assertDatabaseCount('configuracion_notificaciones', 0);
    }
}
