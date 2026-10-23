<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\Legacy\ImportConnections;
use App\Services\Legacy\ImportSafetyException;
use App\Support\CorePermissions;
use Database\Seeders\ProductsPermissionsSeeder;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LegacyCoreImportTest extends TestCase
{
    private bool $migrated = false;

    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('CORE_IMPORT_MYSQL') !== '1') {
            $this->markTestSkipped('Opt-in: CORE_IMPORT_MYSQL=1; requiere dos bases MySQL 8.4 descartables y cuentas separadas.');
        }
        $common = [
            'driver' => 'mysql', 'host' => getenv('CORE_IMPORT_MYSQL_HOST') ?: '127.0.0.1',
            'port' => getenv('CORE_IMPORT_MYSQL_PORT') ?: '3306',
            'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'prefix' => '', 'strict' => true,
        ];
        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => [...$common, 'database' => 'italian_stage02_target_tests', 'username' => 'stage02_app', 'password' => 'stage02-test-only'],
            'database.connections.legacy' => [...$common, 'database' => 'italian_stage02_source_tests', 'username' => 'stage02_reader', 'password' => 'stage02-test-only'],
            'database.connections.stage02_fixtures' => [...$common, 'database' => 'italian_stage02_source_tests', 'username' => getenv('CORE_IMPORT_MYSQL_ADMIN') ?: 'root', 'password' => getenv('CORE_IMPORT_MYSQL_ADMIN_PASSWORD') ?: ''],
            'legacy_import.allow_test_databases' => true,
            'cache.default' => 'array', 'session.driver' => 'array', 'permission.cache.store' => 'array',
        ]);
        foreach (['mysql', 'legacy', 'stage02_fixtures'] as $name) {
            DB::purge($name);
        }
        $this->assertSame('italian_stage02_target_tests', DB::connection('mysql')->selectOne('SELECT DATABASE() AS db')->db);
        $this->assertSame('italian_stage02_source_tests', DB::connection('stage02_fixtures')->selectOne('SELECT DATABASE() AS db')->db);
        $this->artisan('migrate', ['--database' => 'mysql', '--force' => true])->assertExitCode(0);
        $this->migrated = true;
        $this->app->forgetInstance(PermissionRegistrar::class);
        $schema = Schema::connection('stage02_fixtures');
        $schema->create('tbl_plazas', function (Blueprint $table): void {
            $table->charset('latin1');
            $table->collation('latin1_swedish_ci');
            $table->increments('id');
            $table->string('Nombre', 50);
            $table->string('Acronimo', 20);
        });
        $schema->create('tbl_roles', function (Blueprint $table): void {
            $table->charset('latin1');
            $table->collation('latin1_swedish_ci');
            $table->increments('id');
            $table->string('Nombre', 50);
        });
        $schema->create('tbl_usuarios', function (Blueprint $table): void {
            $table->charset('latin1');
            $table->collation('latin1_swedish_ci');
            $table->increments('id');
            foreach (['Nombre', 'Apepat', 'Apemat', 'Sexo', 'Email', 'Usuario', 'Password', 'Hash', 'sha1_id'] as $column) {
                $table->string($column);
            }
            $table->integer('Edad');
            $table->unsignedInteger('plaza_id');
            $table->unsignedInteger('rol_id');
        });
        $schema->create('tbl_bitacora_accesos', function (Blueprint $table): void {
            $table->increments('id');
            $table->date('Fecha');
            $table->time('Hora');
            $table->unsignedInteger('usuario_id');
            $table->unsignedInteger('plaza_id');
        });
        $this->source('tbl_plazas')->insert([
            ['id' => 3, 'Nombre' => 'Café Muñoz', 'Acronimo' => 'CM'],
            ['id' => 7, 'Nombre' => 'Plaza histórica', 'Acronimo' => 'PH'],
        ]);
        foreach (CorePermissions::ROLES as $id => $role) {
            $this->source('tbl_roles')->insert(['id' => $id, 'Nombre' => $role['label']]);
        }
        $this->source('tbl_usuarios')->insert($this->user());
        $this->source('tbl_bitacora_accesos')->insert(['id' => 90, 'Fecha' => '2020-05-03', 'Hora' => '23:59:58', 'usuario_id' => 71, 'plaza_id' => 7]);
    }

    protected function tearDown(): void
    {
        try {
            if ($this->migrated) {
                foreach (['tbl_bitacora_accesos', 'tbl_usuarios', 'tbl_roles', 'tbl_plazas'] as $table) {
                    Schema::connection('stage02_fixtures')->dropIfExists($table);
                }
                $this->artisan('migrate:rollback', ['--database' => 'mysql', '--force' => true])->assertExitCode(0);
            }
        } finally {
            foreach (['mysql', 'legacy', 'stage02_fixtures'] as $connection) {
                DB::purge($connection);
            }
            parent::tearDown();
        }
    }

    public function test_read_only_prevalidation_and_every_dry_run_leave_all_tables_untouched(): void
    {
        $before = $this->snapshot('mysql');
        $legacy = $this->snapshot('legacy');
        $queries = [];
        DB::listen(function (QueryExecuted $event) use (&$queries): void {
            $queries[] = $event->sql;
        });
        config(['cache.default' => 'database', 'permission.cache.store' => 'database']);
        $status = Artisan::call('legacy:validar-nucleo', ['--lote' => 1]);
        $this->assertSame(0, $status, Artisan::output());
        $this->artisan('legacy:inicializar-permisos', ['--dry-run' => true])->assertExitCode(0);
        $this->artisan('legacy:importar-plazas', ['--dry-run' => true])->assertExitCode(0);
        // Individual dry-runs require dependencies to exist in the destination.
        $this->artisan('legacy:importar-usuarios', ['--dry-run' => true])->assertExitCode(1);
        $this->artisan('legacy:importar-accesos', ['--dry-run' => true])->assertExitCode(1);
        foreach ($queries as $sql) {
            $this->assertMatchesRegularExpression('/^(select|show)\b/i', $sql);
        }
        $this->assertSame($before, $this->snapshot('mysql'));
        $this->assertSame($legacy, $this->snapshot('legacy'));
    }

    public function test_full_import_is_repeatable_preserves_history_hashes_ids_encoding_and_source(): void
    {
        $legacy = $this->snapshot('legacy');
        $this->importAll();
        $user = DB::table('usuarios')->sole();
        $this->assertSame(71, $user->id);
        $this->assertSame('María', $user->nombre);
        $this->assertSame('Muñoz', $user->apellido_paterno);
        $this->assertSame(sha1('synthetic-only'), $user->password);
        $this->assertNull($user->created_at);
        $this->assertNull($user->updated_at);
        $this->assertNull($user->remember_token);
        $this->assertSame('Café Muñoz', DB::table('plazas')->where('id', 3)->value('nombre'));
        $this->assertEquals(['role_id' => 3, 'model_id' => 71, 'model_type' => 'usuario'], (array) DB::table('model_has_roles')->sole());
        $access = DB::table('bitacora_accesos')->sole();
        $this->assertSame(7, $access->plaza_id);
        $this->assertSame('2020-05-03', $access->fecha);
        $this->assertSame('23:59:58', $access->hora);
        $this->assertSame('Café Muñoz', Usuario::findOrFail(71)->plaza->nombre);
        $this->assertTrue(Usuario::findOrFail(71)->hasRole('root', 'web'));
        $this->assertSame(Usuario::class, config('auth.providers.users.model'));
        $before = $this->snapshot('mysql');
        foreach (['legacy:validar-nucleo', 'legacy:inicializar-permisos', 'legacy:importar-plazas', 'legacy:importar-usuarios', 'legacy:importar-accesos'] as $command) {
            $this->artisan($command)->assertExitCode(0);
        }
        $this->assertSame($before, $this->snapshot('mysql'));
        $this->assertSame($legacy, $this->snapshot('legacy'));
    }

    public function test_bootstrap_preserves_initial_matrix_and_later_grants_revocations_and_direct_permissions(): void
    {
        $this->importAll();
        $this->assertSame(5, DB::table('roles')->count());
        $this->assertSame(22, DB::table('permissions')->count());
        $this->assertSame(23, DB::table('role_has_permissions')->count());
        $this->assertSame(22, DB::table('role_has_permissions')->where('role_id', 3)->count());
        $this->assertSame(1, DB::table('role_has_permissions')->where('role_id', 1)->count());
        foreach ([2, 4, 5] as $id) {
            $this->assertSame(0, DB::table('role_has_permissions')->where('role_id', $id)->count());
        }
        DB::table('role_has_permissions')->where('role_id', 3)->delete();
        $permission = DB::table('permissions')->where('name', 'products.view')->value('id');
        DB::table('role_has_permissions')->insert(['role_id' => 2, 'permission_id' => $permission]);
        DB::table('model_has_permissions')->insert(['model_type' => 'usuario', 'model_id' => 71, 'permission_id' => $permission]);
        $before = $this->snapshot('mysql');
        $this->artisan('legacy:inicializar-permisos')->assertExitCode(0);
        $this->seed(ProductsPermissionsSeeder::class);
        $this->assertSame($before, $this->snapshot('mysql'));
        $this->assertFalse(Usuario::find(71)->can('ungranted.permission'));
    }

    public function test_bootstrap_refuses_existing_catalog_without_marker_and_catalog_conflicts_with_marker(): void
    {
        DB::table('roles')->insert(['id' => 1, 'name' => 'different', 'guard_name' => 'web']);
        $before = $this->snapshot('mysql');
        $this->artisan('legacy:inicializar-permisos')->assertExitCode(1);
        $this->assertSame($before, $this->snapshot('mysql'));
        DB::table('roles')->delete();
        $this->artisan('legacy:inicializar-permisos')->assertExitCode(0);
        DB::table('roles')->where('id', 1)->update(['name' => 'changed']);
        $before = $this->snapshot('mysql');
        $this->artisan('legacy:inicializar-permisos')->assertExitCode(1);
        $this->assertSame($before, $this->snapshot('mysql'));
    }

    public function test_all_five_role_mappings_are_imported_without_a_superadmin_bypass(): void
    {
        foreach ([1, 2, 4, 5] as $id) {
            $this->source('tbl_usuarios')->insert($this->user(['id' => 80 + $id, 'rol_id' => $id, 'Email' => 'role'.$id.'@example.test', 'Usuario' => 'role'.$id]));
        }
        $this->importAll();
        foreach (CorePermissions::ROLES as $id => $role) {
            $user = Usuario::findOrFail($id === 3 ? 71 : 80 + $id);
            $this->assertTrue($user->hasRole($role['name'], 'web'));
            $this->assertSame(1, $user->roles()->count());
        }
        $owner = Usuario::findOrFail(81);
        $this->assertTrue($owner->can('access-log.view'));
        $this->assertFalse($owner->can('users.directory.view'));
        $this->assertFalse($owner->can('products.view'));
    }

    public function test_populated_dry_runs_and_database_cache_never_write_and_global_integrity_detects_extra_orphan_pivots(): void
    {
        $this->importAll();
        config(['cache.default' => 'database', 'permission.cache.store' => 'database']);
        $before = $this->snapshot('mysql');
        $queries = [];
        DB::listen(function (QueryExecuted $event) use (&$queries): void {
            $queries[] = $event->sql;
        });
        foreach (['legacy:inicializar-permisos', 'legacy:importar-plazas', 'legacy:importar-usuarios', 'legacy:importar-accesos'] as $command) {
            $this->artisan($command, ['--dry-run' => true])->assertExitCode(0);
        }
        foreach ($queries as $sql) {
            $this->assertMatchesRegularExpression('/^(select|show)\b/i', $sql);
        }
        $this->assertSame($before, $this->snapshot('mysql'));
        DB::table('model_has_roles')->insert(['model_type' => 'usuario', 'model_id' => 999, 'role_id' => 3]);
        $this->artisan('legacy:validar-nucleo')->assertExitCode(1);
    }

    public function test_unsafe_database_cache_is_rejected_before_bootstrap_writes(): void
    {
        config(['cache.default' => 'database', 'permission.cache.store' => 'default', 'cache.stores.database.connection' => 'legacy']);
        $before = $this->snapshot('mysql');
        $this->artisan('legacy:inicializar-permisos')->assertExitCode(1);
        $this->assertSame($before, $this->snapshot('mysql'));
    }

    public function test_plaza_validation_duplicates_and_missing_destination_dependencies(): void
    {
        $this->source('tbl_plazas')->insert(['id' => 8, 'Nombre' => 'Café Muñoz', 'Acronimo' => 'CM']);
        $this->artisan('legacy:importar-plazas')->assertExitCode(0);
        $this->assertSame(3, DB::table('plazas')->count());
        $this->source('tbl_plazas')->insert(['id' => 9, 'Nombre' => ' ', 'Acronimo' => 'NO']);
        $before = $this->snapshot('mysql');
        $this->artisan('legacy:importar-plazas')->assertExitCode(1);
        $this->assertSame($before, $this->snapshot('mysql'));
        $this->artisan('legacy:importar-usuarios')->assertExitCode(1);
        $this->assertSame(0, DB::table('usuarios')->count());
    }

    #[DataProvider('invalidUserCases')]
    public function test_invalid_users_block_the_entire_dataset_before_writing(string $case): void
    {
        $this->artisan('legacy:inicializar-permisos')->assertExitCode(0);
        $this->artisan('legacy:importar-plazas')->assertExitCode(0);
        match ($case) {
            'orphan_plaza' => $this->source('tbl_usuarios')->update(['plaza_id' => 999]),
            'missing_role' => DB::table('roles')->where('id', 3)->delete(),
            'unknown_role' => $this->source('tbl_usuarios')->update(['rol_id' => 99]),
            'changed_role_label' => $this->source('tbl_roles')->where('id', 3)->update(['Nombre' => 'Ambiguo']),
            'long_name' => $this->source('tbl_usuarios')->update(['Nombre' => str_repeat('a', 26)]),
            'empty_name' => $this->source('tbl_usuarios')->update(['Nombre' => ' ']),
            'invalid_age' => $this->source('tbl_usuarios')->update(['Edad' => 128]),
            'invalid_hash' => $this->source('tbl_usuarios')->update(['Password' => 'not-a-sha1']),
            'duplicate_collation' => $this->source('tbl_usuarios')->insert($this->user(['id' => 72, 'Email' => 'SYNTHÉTIC@example.test', 'Usuario' => 'different'])),
        };
        $before = $this->snapshot('mysql');
        $this->artisan('legacy:importar-usuarios')->assertExitCode(1);
        $this->assertSame($before, $this->snapshot('mysql'));
        $this->assertSame(0, DB::table('usuarios')->count());
        $this->assertSame(0, DB::table('model_has_roles')->count());
    }

    public static function invalidUserCases(): array
    {
        return array_map(fn ($case) => [$case], ['orphan_plaza', 'missing_role', 'unknown_role', 'changed_role_label', 'long_name', 'empty_name', 'invalid_age', 'invalid_hash', 'duplicate_collation']);
    }

    #[DataProvider('conflictCases')]
    public function test_operational_changes_are_reported_and_never_overwritten(string $case): void
    {
        $this->importAll();
        $command = 'legacy:importar-usuarios';
        switch ($case) {
            case 'modern_password':
                DB::table('usuarios')->where('id', 71)->update(['password' => password_hash('modern-synthetic', PASSWORD_BCRYPT)]);
                break;
            case 'changed_role':
                DB::table('model_has_roles')->where('model_id', 71)->update(['role_id' => 2]);
                break;
            case 'multiple_roles':
                DB::table('model_has_roles')->insert(['model_id' => 71, 'model_type' => 'usuario', 'role_id' => 2]);
                break;
            case 'email_unique':
                $this->source('tbl_usuarios')->insert($this->user(['id' => 72, 'Email' => 'other@example.test', 'Usuario' => 'other']));
                DB::table('usuarios')->where('id', 71)->update(['email' => 'OTHER@example.test']);
                break;
            case 'plaza':
                DB::table('plazas')->where('id', 3)->update(['nombre' => 'Modificado']);
                $command = 'legacy:importar-plazas';
                break;
            case 'access':
                DB::table('bitacora_accesos')->where('id', 90)->update(['hora' => '01:02:03']);
                $command = 'legacy:importar-accesos';
                break;
        }
        $before = $this->snapshot('mysql');
        $this->artisan($command)->assertExitCode(1);
        $this->assertSame($before, $this->snapshot('mysql'));
    }

    public static function conflictCases(): array
    {
        return array_map(fn ($case) => [$case], ['modern_password', 'changed_role', 'multiple_roles', 'email_unique', 'plaza', 'access']);
    }

    public function test_user_and_role_are_atomic_when_the_pivot_insert_fails_without_leaking_sensitive_values(): void
    {
        $this->artisan('legacy:inicializar-permisos')->assertExitCode(0);
        $this->artisan('legacy:importar-plazas')->assertExitCode(0);
        DB::listen(function (QueryExecuted $event): void {
            if (str_starts_with($event->sql, 'insert into `model_has_roles`')) {
                throw new \RuntimeException('sensitive synthetic details must never appear');
            }
        });
        $this->artisan('legacy:importar-usuarios')->doesntExpectOutputToContain('sensitive synthetic')->doesntExpectOutputToContain(sha1('synthetic-only'))->assertExitCode(1);
        $this->assertSame(0, DB::table('usuarios')->count());
        $this->assertSame(0, DB::table('model_has_roles')->count());
    }

    public function test_batch_interruption_resumes_safely_and_reserves_source_auto_increment_holes(): void
    {
        $this->importAll();
        for ($id = 91; $id <= 97; $id++) {
            $this->source('tbl_bitacora_accesos')->insert(['id' => $id, 'Fecha' => '2020-05-04', 'Hora' => '00:01:02', 'usuario_id' => 71, 'plaza_id' => 7]);
        }
        DB::connection('stage02_fixtures')->statement('ALTER TABLE tbl_bitacora_accesos AUTO_INCREMENT = 150');
        $inserts = 0;
        $interrupt = true;
        DB::listen(function (QueryExecuted $event) use (&$inserts, &$interrupt): void {
            if ($interrupt && str_starts_with($event->sql, 'insert into `bitacora_accesos`') && ++$inserts === 2) {
                $interrupt = false;
                throw new \RuntimeException('simulated interruption');
            }
        });
        $this->artisan('legacy:importar-accesos', ['--lote' => 2])->assertExitCode(1);
        $this->assertSame(2, DB::table('bitacora_accesos')->count());
        $this->artisan('legacy:importar-accesos', ['--lote' => 2])->assertExitCode(0);
        $this->assertSame(8, DB::table('bitacora_accesos')->count());
        $this->assertEqualsCanonicalizing($this->source('tbl_bitacora_accesos')->pluck('id')->all(), DB::table('bitacora_accesos')->pluck('id')->all());
        $id = DB::table('bitacora_accesos')->insertGetId(['fecha' => '2021-01-01', 'hora' => '00:00:00', 'usuario_id' => 71, 'plaza_id' => 7]);
        $this->assertGreaterThanOrEqual(150, $id);
        $before = $this->snapshot('mysql');
        $this->artisan('legacy:importar-accesos')->assertExitCode(0);
        $this->assertSame($before, $this->snapshot('mysql'));
    }

    #[DataProvider('invalidAccessCases')]
    public function test_orphan_access_and_non_clock_time_are_rejected(string $case): void
    {
        $this->importAll();
        if ($case === 'zero_date') {
            DB::connection('stage02_fixtures')->statement("SET SESSION sql_mode = ''");
        }
        $values = match ($case) {
            'orphan_user' => ['usuario_id' => 999],
            'orphan_plaza' => ['plaza_id' => 999],
            'overflow_time' => ['Hora' => '24:01:02'],
            'negative_time' => ['Hora' => '-00:01:02'],
            'zero_date' => ['Fecha' => '0000-00-00'],
        };
        $this->source('tbl_bitacora_accesos')->where('id', 90)->update($values);
        $before = $this->snapshot('mysql');
        $this->artisan('legacy:importar-accesos', ['--dry-run' => true])->assertExitCode(1);
        $this->assertSame($before, $this->snapshot('mysql'));
    }

    public static function invalidAccessCases(): array
    {
        return array_map(fn ($case) => [$case], ['orphan_user', 'orphan_plaza', 'overflow_time', 'negative_time', 'zero_date']);
    }

    public function test_effective_same_database_unapproved_target_and_shared_credentials_are_rejected(): void
    {
        config(['database.connections.legacy.database' => 'italian_stage02_target_tests']);
        DB::purge('legacy');
        $this->artisan('legacy:validar-nucleo')->expectsOutputToContain('bases y cuentas distintas')->assertExitCode(1);
        config(['database.connections.legacy' => config('database.connections.mysql')]);
        DB::purge('legacy');
        $this->artisan('legacy:validar-nucleo')->assertExitCode(1);
        config(['legacy_import.allow_test_databases' => false]);
        $this->artisan('legacy:inicializar-permisos')->assertExitCode(1);
    }

    public function test_privileged_legacy_account_is_rejected_and_reader_cannot_write(): void
    {
        app(ImportConnections::class)->check();
        try {
            DB::connection('legacy')->table('tbl_plazas')->insert(['Nombre' => 'Forbidden', 'Acronimo' => 'NO']);
            $this->fail('Reader unexpectedly can write');
        } catch (QueryException $exception) {
            $this->assertSame(1142, $exception->errorInfo[1]);
        }
        config(['database.connections.legacy' => config('database.connections.stage02_fixtures')]);
        DB::purge('legacy');
        $this->artisan('legacy:validar-nucleo')->assertExitCode(1);
    }

    #[DataProvider('unsafeGrants')]
    public function test_grant_parser_fails_closed_for_unsafe_or_inherited_permissions(array $grants): void
    {
        $this->expectException(ImportSafetyException::class);
        ImportConnections::assertReadOnlyGrants($grants);
    }

    public static function unsafeGrants(): array
    {
        return [
            [['GRANT ALL PRIVILEGES ON *.* TO `reader`@`%`']],
            [['GRANT SELECT, INSERT ON `italian`.* TO `reader`@`%`']],
            [['GRANT SELECT ON `italian`.* TO `reader`@`%` WITH GRANT OPTION']],
            [['GRANT `inherited`@`%` TO `reader`@`%`']],
            [['GRANT BACKUP_ADMIN ON *.* TO `reader`@`%`']],
            [[]],
        ];
    }

    private function importAll(): void
    {
        foreach (['legacy:inicializar-permisos', 'legacy:importar-plazas', 'legacy:importar-usuarios', 'legacy:importar-accesos'] as $command) {
            $status = Artisan::call($command);
            $this->assertSame(0, $status, $command.' '.Artisan::output());
        }
    }

    private function user(array $overrides = []): array
    {
        return array_replace([
            'id' => 71, 'Nombre' => 'María', 'Apepat' => 'Muñoz', 'Apemat' => 'Pérez', 'Edad' => 30, 'Sexo' => 'M',
            'Email' => 'synthetic@example.test', 'Usuario' => 'synthetic', 'Password' => sha1('synthetic-only'),
            'Hash' => 'unused-id-hash', 'sha1_id' => 'unused-id-hash', 'plaza_id' => 3, 'rol_id' => 3,
        ], $overrides);
    }

    private function source(string $table): Builder
    {
        return DB::connection('stage02_fixtures')->table($table);
    }

    private function snapshot(string $connection): array
    {
        $tables = $connection === 'legacy' ? ['tbl_plazas', 'tbl_roles', 'tbl_usuarios', 'tbl_bitacora_accesos'] : Schema::connection($connection)->getTableListing(schemaQualified: false);
        $snapshot = [];
        foreach ($tables as $table) {
            $rows = DB::connection($connection)->table($table)->get()->map(fn ($row) => json_encode($row))->sort()->values()->all();
            $snapshot[$table] = hash('sha256', json_encode($rows));
        }

        return $snapshot;
    }
}
