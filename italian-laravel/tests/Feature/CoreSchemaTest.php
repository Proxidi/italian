<?php

namespace Tests\Feature;

use App\Models\Acceso;
use App\Models\Plaza;
use App\Models\User;
use App\Models\Usuario;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CoreSchemaTest extends TestCase
{
    private const CONNECTION = 'core_schema_tests';

    private bool $migrated = false;

    protected function setUp(): void
    {
        parent::setUp();

        // Opt-in MySQL uses only the explicitly disposable test schema. Never
        // inherit DB_URL, DB_DATABASE, credentials or connections from .env.
        $mysql = getenv('CORE_SCHEMA_MYSQL') === '1';
        $connection = $mysql ? [
            'driver' => 'mysql',
            'host' => getenv('CORE_SCHEMA_MYSQL_HOST') ?: '127.0.0.1',
            'port' => getenv('CORE_SCHEMA_MYSQL_PORT') ?: '3306',
            'database' => 'italian_stage01_tests',
            'username' => getenv('CORE_SCHEMA_MYSQL_USERNAME') ?: 'root',
            'password' => getenv('CORE_SCHEMA_MYSQL_PASSWORD') ?: '',
            'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '', 'strict' => true,
        ] : [
            'driver' => 'sqlite', 'database' => ':memory:',
            'prefix' => '', 'foreign_key_constraints' => true,
        ];
        config([
            'database.default' => self::CONNECTION,
            'database.connections.'.self::CONNECTION => $connection,
            'cache.default' => 'array', 'session.driver' => 'array',
            'permission.cache.store' => 'array',
        ]);
        DB::purge(self::CONNECTION);
        $this->app->forgetInstance(PermissionRegistrar::class);

        if ($mysql) {
            $this->assertSame('italian_stage01_tests', DB::selectOne('SELECT DATABASE() AS db')->db);
        }
        $this->artisan('migrate', ['--database' => self::CONNECTION, '--force' => true])->assertExitCode(0);
        $this->migrated = true;
    }

    protected function tearDown(): void
    {
        try {
            if ($this->migrated) {
                // Roll back only this test connection; also verify reverse FK order.
                $this->artisan('migrate:rollback', ['--database' => self::CONNECTION, '--force' => true])->assertExitCode(0);
                $this->assertFalse(Schema::hasTable('usuarios'));
                $this->assertFalse(Schema::hasTable('plazas'));
                $this->assertFalse(Schema::hasTable('bitacora_accesos'));
            }
        } finally {
            DB::purge(self::CONNECTION);
            parent::tearDown();
        }
    }

    public function test_complete_schema_is_new_empty_and_has_compatible_foreign_keys_and_indexes(): void
    {
        $expected = [
            'plazas', 'usuarios', 'bitacora_accesos', 'password_reset_tokens', 'sessions',
            'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs',
            'roles', 'permissions', 'model_has_roles', 'model_has_permissions', 'role_has_permissions',
            'migrations',
        ];
        $this->assertEqualsCanonicalizing($expected, Schema::getTableListing(schemaQualified: false));
        foreach (array_diff($expected, ['migrations']) as $table) {
            $this->assertSame(0, DB::table($table)->count());
        }
        $this->assertSame([
            'id', 'nombre', 'apellido_paterno', 'apellido_materno', 'edad', 'sexo',
            'email', 'usuario', 'password', 'plaza_id', 'remember_token', 'created_at', 'updated_at',
        ], Schema::getColumnListing('usuarios'));
        $this->assertSame(['id', 'nombre', 'acronimo'], Schema::getColumnListing('plazas'));
        $this->assertSame(['id', 'fecha', 'hora', 'usuario_id', 'plaza_id'], Schema::getColumnListing('bitacora_accesos'));

        $columns = collect(Schema::getColumns('usuarios'))->keyBy('name');
        $this->assertTrue($columns['created_at']['nullable']);
        $this->assertTrue($columns['updated_at']['nullable']);
        $this->assertTrue($columns['remember_token']['nullable']);
        foreach (['usuarios' => ['plaza_id' => 'plazas'], 'bitacora_accesos' => ['usuario_id' => 'usuarios', 'plaza_id' => 'plazas']] as $table => $relations) {
            foreach ($relations as $column => $parent) {
                $fk = collect(Schema::getForeignKeys($table))->firstWhere('columns', [$column]);
                $this->assertNotNull($fk);
                $this->assertSame($parent, $fk['foreign_table']);
                $this->assertSame('restrict', strtolower($fk['on_delete']));
            }
        }
        foreach (['email', 'usuario'] as $column) {
            $index = collect(Schema::getIndexes('usuarios'))->firstWhere('columns', [$column]);
            $this->assertTrue($index['unique']);
        }
        $this->assertNotNull(collect(Schema::getIndexes('bitacora_accesos'))->firstWhere('columns', ['fecha', 'hora']));

        if (DB::getDriverName() === 'mysql') {
            $types = DB::select("SELECT TABLE_NAME AS table_name, COLUMN_NAME AS column_name, COLUMN_TYPE AS column_type, CHARACTER_MAXIMUM_LENGTH AS max_length FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name IN ('plazas', 'usuarios', 'bitacora_accesos')");
            $types = collect($types)->keyBy(fn ($row) => $row->table_name.'.'.$row->column_name);
            foreach (['plazas.id', 'usuarios.id', 'usuarios.plaza_id', 'bitacora_accesos.usuario_id', 'bitacora_accesos.plaza_id'] as $key) {
                $this->assertSame('bigint unsigned', $types[$key]->column_type);
            }
            foreach (['nombre' => 25, 'apellido_paterno' => 25, 'apellido_materno' => 25, 'sexo' => 1, 'email' => 75, 'usuario' => 50, 'password' => 255] as $column => $length) {
                $this->assertSame($length, (int) $types['usuarios.'.$column]->max_length);
            }
        }
    }

    public function test_models_preserve_identifiers_literal_dates_and_unknown_historical_timestamps(): void
    {
        [$plaza, $user, $access] = $this->fixtures();
        $this->assertSame(71, $user->id);
        $this->assertSame(sha1('synthetic-password'), $user->getAuthPassword());
        $this->assertNull($user->created_at);
        $this->assertNull($user->updated_at);
        $this->assertNull($user->remember_token);
        $this->assertTrue($user->plaza->is($plaza));
        $this->assertTrue($user->accesos->sole()->is($access));
        $this->assertTrue($plaza->usuarios->sole()->is($user));
        $this->assertTrue($plaza->accesos->sole()->is($access));
        $this->assertTrue($access->usuario->is($user));
        $this->assertTrue($access->plaza->is($plaza));
        $this->assertSame('2020-05-03', $access->fecha);
        $this->assertSame('23:59:58', $access->hora);
        $this->assertArrayNotHasKey('password', $user->toArray());
        $this->assertArrayNotHasKey('remember_token', $user->toArray());
        $this->assertFalse($user->isFillable('id'));
        $this->assertFalse($user->isFillable('password'));

        // Equal names/acronyms are allowed: there is no evidence for uniqueness.
        Plaza::create(['nombre' => $plaza->nombre, 'acronimo' => $plaza->acronimo]);
        $this->assertSame(2, Plaza::count());
    }

    public function test_new_spatie_alias_does_not_change_the_active_legacy_auth_model(): void
    {
        [, $user] = $this->fixtures();
        $role = Role::create(['name' => 'superadmin', 'guard_name' => 'web']);
        $permission = Permission::create(['name' => 'synthetic.core.view', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);
        $this->assertSame('usuario', $user->getMorphClass());
        $this->assertDatabaseHas('model_has_roles', ['model_type' => 'usuario', 'model_id' => $user->id, 'role_id' => $role->id]);
        $this->assertTrue($user->fresh()->hasRole('superadmin', 'web'));
        $this->assertTrue($user->fresh()->can('synthetic.core.view'));
        $this->assertFalse($user->fresh()->can('synthetic.not-granted'));
        $direct = Permission::create(['name' => 'synthetic.core.direct', 'guard_name' => 'web']);
        $user->givePermissionTo($direct);
        $this->assertDatabaseHas('model_has_permissions', ['model_type' => 'usuario', 'model_id' => $user->id]);
        $this->assertTrue($user->fresh()->can('synthetic.core.direct'));
        $this->assertSame(User::class, (new User)->getMorphClass());
        $this->assertSame('tbl_usuarios', (new User)->getTable());
        $this->assertSame(User::class, config('auth.providers.users.model'));
        $this->assertSame('mysql', config('database.connections.legacy.driver'));

        // Spatie's inverse relation resolves the active guard provider. Simulate
        // the future switch only inside this test, never in application config.
        config(['auth.providers.users.model' => Usuario::class]);
        $this->assertTrue($role->users()->whereKey($user->id)->exists());
    }

    #[DataProvider('constraintCases')]
    public function test_invalid_references_duplicates_and_historical_deletions_are_rejected(string $case): void
    {
        [$plaza, $user, $access] = $this->fixtures();
        if ($case === 'delete_historical_plaza') {
            $other = Plaza::create(['nombre' => 'Nueva plaza sintética', 'acronimo' => 'NUE']);
            $user->update(['plaza_id' => $other->id]);
        }
        $this->expectException(QueryException::class);
        match ($case) {
            'user_without_plaza' => $user->forceFill(['plaza_id' => 9999])->save(),
            'access_without_user' => $access->update(['usuario_id' => 9999]),
            'access_without_plaza' => $access->update(['plaza_id' => 9999]),
            'delete_used_user' => $user->delete(),
            'delete_used_plaza', 'delete_historical_plaza' => $plaza->delete(),
            'duplicate_email' => DB::table('usuarios')->insert([...$user->getAttributes(), 'id' => 72, 'usuario' => 'different']),
            'duplicate_username' => DB::table('usuarios')->insert([...$user->getAttributes(), 'id' => 72, 'email' => 'different@example.test']),
        };
    }

    public static function constraintCases(): array
    {
        return array_map(fn ($case) => [$case], [
            'user_without_plaza', 'access_without_user', 'access_without_plaza',
            'delete_used_user', 'delete_used_plaza', 'delete_historical_plaza', 'duplicate_email', 'duplicate_username',
        ]);
    }

    private function fixtures(): array
    {
        DB::table('plazas')->insert(['id' => 7, 'nombre' => 'Plaza sintética', 'acronimo' => 'PRU']);
        DB::table('usuarios')->insert([
            'id' => 71, 'nombre' => 'María', 'apellido_paterno' => 'Muñoz', 'apellido_materno' => 'Pérez',
            'edad' => 30, 'sexo' => 'M', 'email' => 'synthetic@example.test', 'usuario' => 'synthetic',
            'password' => sha1('synthetic-password'), 'plaza_id' => 7,
        ]);
        DB::table('bitacora_accesos')->insert(['id' => 90, 'fecha' => '2020-05-03', 'hora' => '23:59:58', 'usuario_id' => 71, 'plaza_id' => 7]);

        return [Plaza::findOrFail(7), Usuario::findOrFail(71), Acceso::findOrFail(90)];
    }
}
