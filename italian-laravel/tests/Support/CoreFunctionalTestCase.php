<?php

namespace Tests\Support;

use App\Support\CorePermissions;
use Carbon\Carbon;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

abstract class CoreFunctionalTestCase extends TestCase
{
    use NativeCoreFixtures;

    protected bool $mysql = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mysql = getenv('CORE_FUNCTIONAL_MYSQL') === '1';
        $connection = $this->mysql ? [
            'driver' => 'mysql', 'host' => getenv('CORE_FUNCTIONAL_MYSQL_HOST') ?: '127.0.0.1',
            'port' => getenv('CORE_FUNCTIONAL_MYSQL_PORT') ?: '3306',
            'database' => 'italian_stage03_tests', 'username' => getenv('CORE_FUNCTIONAL_MYSQL_USERNAME') ?: 'root',
            'password' => getenv('CORE_FUNCTIONAL_MYSQL_PASSWORD') ?: '',
            'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'prefix' => '', 'strict' => true,
        ] : ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true];
        config([
            'database.default' => 'core_functional_tests', 'database.connections.core_functional_tests' => $connection,
            'database.connections.legacy' => ['driver' => 'mysql', 'host' => '127.0.0.1', 'port' => 1, 'database' => 'forbidden_legacy', 'username' => 'forbidden', 'password' => '', 'prefix' => ''],
            'cache.default' => 'array', 'permission.cache.store' => 'array', 'session.driver' => 'array',
            'hashing.driver' => 'argon2id', 'hashing.argon.memory' => 1024, 'hashing.argon.time' => 1,
            'hashing.argon.threads' => 1, 'hashing.bcrypt.rounds' => 4,
            'excel.temporary_files.local_path' => sys_get_temp_dir(),
        ]);
        DB::purge('core_functional_tests');
        DB::purge('legacy');
        $this->app->forgetInstance(PermissionRegistrar::class);
        if ($this->mysql) {
            $this->assertSame('italian_stage03_tests', DB::selectOne('SELECT DATABASE() AS db')->db);
        }
        $this->artisan('migrate', ['--database' => 'core_functional_tests', '--force' => true])->assertExitCode(0);
        // Synthetic authorization fixtures; never run the installation bootstrap.
        foreach (CorePermissions::ROLES as $id => $role) {
            DB::table('roles')->insert(['id' => $id, 'name' => $role['name'], 'guard_name' => 'web']);
        }
        foreach (CorePermissions::names() as $name) {
            $id = DB::table('permissions')->insertGetId(['name' => $name, 'guard_name' => 'web']);
            foreach (CorePermissions::roleNamesFor($name) as $role) {
                $roleId = DB::table('roles')->where('name', $role)->value('id');
                DB::table('role_has_permissions')->insert(['role_id' => $roleId, 'permission_id' => $id]);
            }
        }
        $this->withoutVite();
        $this->travelTo(Carbon::parse('2026-10-23 00:30:00', 'UTC'));
        DB::listen(function (QueryExecuted $event): void {
            $this->assertNotSame('legacy', $event->connectionName);
            $this->assertDoesNotMatchRegularExpression('/\btbl_/', $event->sql);
        });
    }

    protected function tearDown(): void
    {
        try {
            $this->travelBack();
            if ($this->mysql) {
                $this->artisan('migrate:rollback', ['--database' => 'core_functional_tests', '--force' => true])->assertExitCode(0);
            }
        } finally {
            DB::purge('core_functional_tests');
            DB::purge('legacy');
            parent::tearDown();
        }
    }
}
