<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\UsersReadPermissionsSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UsersReadTest extends TestCase
{
    private const CONNECTION = 'users_read_tests';

    protected function setUp(): void
    {
        parent::setUp();

        /*
         * Estas pruebas nunca usan la conexión MySQL compartida con
         * CodeIgniter. Crean un esquema mínimo en SQLite en memoria.
         */
        config([
            'database.default' => self::CONNECTION,

            'database.connections.'.self::CONNECTION => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],

            'cache.default' => 'array',
            'permission.cache.store' => 'array',
            'session.driver' => 'array',
        ]);

        DB::purge(self::CONNECTION);
        $this->app->forgetInstance(PermissionRegistrar::class);

        $this->withoutVite();

        $this->travelTo(
            Carbon::parse('2026-10-13 12:00:00', config('app.timezone')),
        );

        $this->createSchema();

        DB::table('tbl_plazas')->insert([
            [
                'id' => 1,
                'Nombre' => 'Plaza actual',
                'Acronimo' => 'ACT',
            ],
            [
                'id' => 2,
                'Nombre' => 'Plaza del acceso',
                'Acronimo' => 'ACC',
            ],
        ]);

        $roles = [
            'superadmin' => ['id' => 1, 'label' => 'Dueño'],
            'admin' => ['id' => 2, 'label' => 'Manager'],
            'root' => ['id' => 3, 'label' => 'Gerente'],
            'submanager' => ['id' => 4, 'label' => 'SubManager'],
            'socio' => ['id' => 5, 'label' => 'Socio'],
        ];

        foreach ($roles as $name => $role) {
            DB::table('tbl_roles')->insert([
                'id' => $role['id'],
                'Nombre' => $role['label'],
            ]);

            Role::findOrCreate($name, 'web');
        }

        $this->seed(UsersReadPermissionsSeeder::class);
    }

    protected function tearDown(): void
    {
        try {
            $this->travelBack();
            DB::purge(self::CONNECTION);
        } finally {
            parent::tearDown();
        }
    }

    public function test_guest_cannot_access_screens_or_exports(): void
    {
        foreach ([
                     'usuarios.index',
                     'usuarios.export',
                     'usuarios.accesos',
                     'usuarios.accesos.export',
                 ] as $route) {
            $this->get(route($route))
                ->assertRedirect(route('login'));
        }
    }

    public static function roleAccess(): array
    {
        return [
            'root' => ['root', 200, 200],
            'superadmin' => ['superadmin', 403, 200],
            'admin' => ['admin', 403, 403],
            'submanager' => ['submanager', 403, 403],
            'socio' => ['socio', 403, 403],
        ];
    }

    #[DataProvider('roleAccess')]
    public function test_roles_are_checked_on_screens_and_direct_exports(
        string $role,
        int $directoryStatus,
        int $accessLogStatus,
    ): void {
        $this->actingAs($this->createUser($role), 'web');

        $this->get('/Usuarios')->assertStatus($directoryStatus);

        $this->get('/Usuarios/listaAccesos')
            ->assertStatus($accessLogStatus);

        foreach (['csv', 'print', 'xlsx', 'pdf'] as $format) {
            $this->get(route('usuarios.export', [
                'format' => $format,
            ]))->assertStatus($directoryStatus);

            $this->get(route('usuarios.accesos.export', [
                'format' => $format,
            ]))->assertStatus($accessLogStatus);
        }
    }

    public function test_directory_has_only_five_columns_and_is_global(): void
    {
        $root = $this->createUser('root');
        $other = $this->createUser('admin', plaza: 2);

        $response = $this->actingAs($root, 'web')
            ->get('/Usuarios')
            ->assertOk();

        $records = $response->viewData('records');

        $this->assertSame(2, $records->total());
        $this->assertSame(100, $records->perPage());

        $this->assertSame(
            ['Nombre', 'Edad', 'Email', 'Usuario', 'Plaza'],
            array_keys((array) $records->first()),
        );

        $response
            ->assertSee($other->Nombre)
            ->assertSee('Plaza del acceso')
            ->assertSee('Agregar Usuario')
            ->assertSee('Editar')
            ->assertSee('Eliminar')
            ->assertSee('disabled', false)
            ->assertDontSee('HASH_NO_PUBLICAR')
            ->assertDontSee('SHA1_ID_NO_PUBLICAR');
    }

    public function test_access_log_filters_exact_date_and_uses_recorded_plaza_and_current_role(): void
    {
        $root = $this->createUser('root');
        $subject = $this->createUser('admin');

        $this->insertAccess(
            user: $subject,
            date: '2026-10-10',
            plazaId: 2,
            time: '08:30:00',
        );

        $this->insertAccess(
            user: $subject,
            date: '2026-10-11',
            plazaId: 1,
            time: '09:00:00',
        );

        /*
         * El legacy resuelve el rol durante la consulta de la bitácora,
         * usando el rol actual del usuario, no un snapshot histórico.
         */
        DB::table('tbl_usuarios')
            ->where('id', $subject->id)
            ->update(['rol_id' => 5]);

        $response = $this->actingAs($root, 'web')
            ->get(route('usuarios.accesos', [
                'date' => '2026-10-10',
            ]))
            ->assertOk();

        $records = $response->viewData('records');

        $this->assertSame(1, $records->total());
        $this->assertSame(200, $records->perPage());
        $this->assertSame('2026-10-10', $records->first()->Fecha);
        $this->assertSame('Plaza del acceso', $records->first()->Plaza);
        $this->assertSame('Socio', $records->first()->Rol);
        $this->assertSame($subject->Nombre, $records->first()->Usuario);
    }

    public function test_reset_returns_to_today_and_default_order_is_newest_first(): void
    {
        $root = $this->createUser('root');

        $this->insertAccess(
            user: $root,
            date: '2026-10-13',
            plazaId: 1,
            time: '08:00:00',
        );

        $this->insertAccess(
            user: $root,
            date: '2026-10-13',
            plazaId: 2,
            time: '10:00:00',
        );

        $this->insertAccess(
            user: $root,
            date: '2026-10-12',
            plazaId: 1,
            time: '12:00:00',
        );

        $response = $this->actingAs($root, 'web')
            ->get('/Usuarios/listaAccesos')
            ->assertOk();

        $this->assertSame(
            '2026-10-13',
            $response->viewData('filters')['date'],
        );

        $records = $response->viewData('records');

        $this->assertSame(2, $records->total());
        $this->assertSame('10:00:00', $records->first()->Hora);

        $response->assertSee(
            'href="'.route('usuarios.accesos').'"',
            false,
        );
    }

    public function test_pagination_uses_100_and_200_records_and_keeps_date(): void
    {
        $root = $this->createUser('root');

        $base = $root->getAttributes();
        unset($base['id']);

        $users = [];

        for ($number = 1; $number <= 100; $number++) {
            $users[] = [
                ...$base,
                'Nombre' => 'Consulta '.$number,
                'Email' => 'consulta'.$number.'@example.test',
                'Usuario' => 'consulta'.$number,
            ];
        }

        DB::table('tbl_usuarios')->insert($users);

        $accesses = [];

        for ($number = 1; $number <= 201; $number++) {
            $accesses[] = [
                'Fecha' => '2026-10-10',
                'Hora' => '10:00:00',
                'usuario_id' => $root->id,
                'plaza_id' => 1,
            ];
        }

        DB::table('tbl_bitacora_accesos')->insert($accesses);

        $this->actingAs($root, 'web');

        $directory = $this->get('/Usuarios')
            ->assertOk()
            ->viewData('records');

        $this->assertCount(100, $directory->items());
        $this->assertSame(101, $directory->total());

        $this->assertCount(
            1,
            $this->get('/Usuarios?page=2')
                ->assertOk()
                ->viewData('records')
                ->items(),
        );

        $log = $this->get(route('usuarios.accesos', [
            'date' => '2026-10-10',
        ]))
            ->assertOk()
            ->viewData('records');

        $this->assertCount(200, $log->items());
        $this->assertStringContainsString(
            'date=2026-10-10',
            $log->nextPageUrl(),
        );

        $this->assertCount(
            1,
            $this->get($log->nextPageUrl())
                ->assertOk()
                ->viewData('records')
                ->items(),
        );
    }

    public function test_date_and_sort_inputs_are_validated(): void
    {
        $this->actingAs($this->createUser('root'), 'web');

        $this->getJson(route('usuarios.accesos', [
            'date' => '2026-02-30',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('date');

        $this->getJson(route('usuarios.index', [
            'sort' => 'Password',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sort');

        $this->getJson(route('usuarios.index', [
            'direction' => 'invalid',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('direction');
    }

    public function test_search_sort_and_exports_use_same_filters(): void
    {
        $root = $this->createUser('root');
        $target = $this->createUser('admin', plaza: 2);

        $this->actingAs($root, 'web');

        $filters = [
            'q' => $target->Email,
            'sort' => 'name',
            'direction' => 'desc',
        ];

        $response = $this->get(route('usuarios.index', $filters))
            ->assertOk();

        $this->assertSame(
            1,
            $response->viewData('records')->total(),
        );

        $csv = $this->get(route('usuarios.export', [
            ...$filters,
            'format' => 'csv',
        ]))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString($target->Email, $csv);
        $this->assertStringNotContainsString($root->Email, $csv);

        $this->get(route('usuarios.export', [
            ...$filters,
            'format' => 'print',
        ]))
            ->assertOk()
            ->assertSee($target->Email)
            ->assertDontSee($root->Email);
    }

    public function test_values_are_escaped_and_search_wildcards_are_literal(): void
    {
        $root = $this->createUser('root');
        $target = $this->createUser('admin');

        DB::table('tbl_usuarios')
            ->where('id', $target->id)
            ->update([
                'Nombre' => '<script>x</script>',
                'Usuario' => 'persona%especial',
            ]);

        $response = $this->actingAs($root, 'web')
            ->get(route('usuarios.index', ['q' => '%']))
            ->assertOk();

        $this->assertSame(
            1,
            $response->viewData('records')->total(),
        );

        $response
            ->assertSee('&lt;script&gt;x&lt;/script&gt;', false)
            ->assertDontSee('<script>x</script>', false);
    }

    public function test_navigation_reflects_permissions_and_removes_empty_groups(): void
    {
        $root = $this->createUser('root');

        $this->actingAs($root, 'web')
            ->get('/Inicio')
            ->assertOk()
            ->assertSee(
                'href="'.route('usuarios.index').'"',
                false,
            )
            ->assertSee(
                'href="'.route('usuarios.accesos').'"',
                false,
            );

        $superadmin = $this->createUser('superadmin');

        $this->actingAs($superadmin, 'web')
            ->get('/Inicio')
            ->assertOk()
            ->assertDontSee(
                'href="'.route('usuarios.index').'"',
                false,
            )
            ->assertSee(
                'href="'.route('usuarios.accesos').'"',
                false,
            );

        Role::findByName('superadmin', 'web')
            ->revokePermissionTo('access-log.view');

        $this->actingAs($superadmin->fresh(), 'web')
            ->get('/Inicio')
            ->assertOk()
            ->assertDontSee(
                'href="'.route('usuarios.accesos').'"',
                false,
            );
    }

    public function test_read_requests_do_not_modify_legacy_or_spatie_records(): void
    {
        $root = $this->createUser('root');

        $this->insertAccess(
            user: $root,
            date: '2026-10-13',
            plazaId: 2,
            time: '10:00:00',
        );

        $this->actingAs($root, 'web');

        $before = $this->snapshot();

        foreach (['usuarios.index', 'usuarios.accesos'] as $route) {
            $this->get(route($route))->assertOk();
        }

        foreach (['usuarios.export', 'usuarios.accesos.export'] as $route) {
            $this->get(route($route, ['format' => 'csv']))
                ->assertOk()
                ->streamedContent();

            $this->get(route($route, ['format' => 'print']))
                ->assertOk();
        }

        $this->assertSame($before, $this->snapshot());
    }

    public function test_permission_seeder_is_idempotent_and_preserves_unrelated_permissions(): void
    {
        $extra = Permission::findOrCreate('other-module.view', 'web');

        Role::findByName('admin', 'web')
            ->givePermissionTo($extra);

        /*
         * Simula una asignación incorrecta previa. El seeder debe retirarla,
         * pues la matriz inicial es parte del contrato del módulo.
         */
        Role::findByName('admin', 'web')
            ->givePermissionTo('users.directory.view');

        $this->seed(UsersReadPermissionsSeeder::class);
        $this->seed(UsersReadPermissionsSeeder::class);

        $admin = Role::findByName('admin', 'web');

        $this->assertTrue(
            $admin->hasPermissionTo('other-module.view'),
        );

        $this->assertFalse(
            $admin->hasPermissionTo('users.directory.view'),
        );

        $this->assertTrue(
            Role::findByName('root', 'web')
                ->hasPermissionTo('users.directory.view'),
        );

        $this->assertTrue(
            Role::findByName('superadmin', 'web')
                ->hasPermissionTo('access-log.view'),
        );
    }

    private function createUser(string $role, int $plaza = 1): User
    {
        $number = DB::table('tbl_usuarios')->count() + 1;

        $roleIds = [
            'superadmin' => 1,
            'admin' => 2,
            'root' => 3,
            'submanager' => 4,
            'socio' => 5,
        ];

        $id = DB::table('tbl_usuarios')->insertGetId([
            'Nombre' => 'Persona '.$number,
            'Apepat' => 'Apellido',
            'Apemat' => 'Segundo',
            'Edad' => 30,
            'Sexo' => 'H',
            'Email' => 'persona'.$number.'@example.test',
            'Usuario' => 'persona'.$number,
            'Password' => sha1('clave-de-prueba'),
            'Hash' => 'HASH_NO_PUBLICAR',
            'sha1_id' => 'SHA1_ID_NO_PUBLICAR',
            'rol_id' => $roleIds[$role],
            'plaza_id' => $plaza,
        ]);

        $user = User::query()->findOrFail($id);
        $user->assignRole($role);

        return $user;
    }

    private function insertAccess(
        User $user,
        string $date,
        int $plazaId,
        string $time,
    ): void {
        DB::table('tbl_bitacora_accesos')->insert([
            'Fecha' => $date,
            'Hora' => $time,
            'usuario_id' => $user->id,
            'plaza_id' => $plazaId,
        ]);
    }

    private function snapshot(): array
    {
        $snapshot = [];

        foreach ([
                     'tbl_usuarios',
                     'tbl_roles',
                     'tbl_plazas',
                     'tbl_bitacora_accesos',
                     'roles',
                     'permissions',
                     'model_has_roles',
                     'model_has_permissions',
                     'role_has_permissions',
                 ] as $table) {
            $snapshot[$table] = DB::table($table)
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all();
        }

        return $snapshot;
    }

    private function createSchema(): void
    {
        $schema = Schema::connection(self::CONNECTION);

        $schema->create('tbl_plazas', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('Nombre', 50);
            $table->string('Acronimo', 20);
        });

        $schema->create('tbl_roles', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('Nombre', 50);
        });

        $schema->create('tbl_usuarios', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('Nombre', 25);
            $table->string('Apepat', 25);
            $table->string('Apemat', 25);
            $table->tinyInteger('Edad');
            $table->string('Sexo', 1);
            $table->string('Email', 75)->unique();
            $table->string('Usuario', 50)->unique();
            $table->string('Password');
            $table->string('Hash');
            $table->string('sha1_id');
            $table->unsignedInteger('plaza_id');
            $table->unsignedInteger('rol_id');

            $table->foreign('plaza_id')
                ->references('id')
                ->on('tbl_plazas');

            $table->foreign('rol_id')
                ->references('id')
                ->on('tbl_roles');
        });

        $schema->create(
            'tbl_bitacora_accesos',
            function (Blueprint $table): void {
                $table->increments('id');
                $table->date('Fecha');
                $table->time('Hora');
                $table->unsignedInteger('usuario_id');
                $table->unsignedInteger('plaza_id');
            },
        );

        foreach (['roles', 'permissions'] as $tableName) {
            $schema->create(
                $tableName,
                function (Blueprint $table): void {
                    $table->bigIncrements('id');
                    $table->string('name');
                    $table->string('guard_name');
                    $table->timestamps();

                    $table->unique(['name', 'guard_name']);
                },
            );
        }

        foreach ([
                     'model_has_roles' => 'role_id',
                     'model_has_permissions' => 'permission_id',
                 ] as $tableName => $key) {
            $schema->create(
                $tableName,
                function (Blueprint $table) use ($key): void {
                    $table->unsignedBigInteger($key);
                    $table->string('model_type');
                    $table->unsignedBigInteger('model_id');

                    $table->primary([
                        $key,
                        'model_id',
                        'model_type',
                    ]);
                },
            );
        }

        $schema->create(
            'role_has_permissions',
            function (Blueprint $table): void {
                $table->unsignedBigInteger('permission_id');
                $table->unsignedBigInteger('role_id');

                $table->primary(['permission_id', 'role_id']);
            },
        );
    }
}
