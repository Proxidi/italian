<?php

namespace Tests\Feature;

use App\Actions\Users\SaveUser;
use App\Models\User;
use Barryvdh\DomPDF\PDF;
use Carbon\Carbon;
use Database\Seeders\UsersReadPermissionsSeeder;
use Database\Seeders\UsersWritePermissionsSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
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
            // Keep exports isolated from a mounted application's storage owner.
            'excel.temporary_files.local_path' => sys_get_temp_dir(),
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
            ['Nombre', 'Edad', 'Email', 'Usuario', 'Plaza', 'id'],
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

    private function userPayload(array $overrides = []): array
    {
        return array_replace([
            'nombre' => 'María José', 'apepat' => 'Pérez', 'apemat' => 'López',
            'edad' => 32, 'sexo' => 'M', 'email' => 'nueva@example.test',
            'usuario' => 'nueva', 'password' => 'clave-nueva', 'plaza_id' => 2, 'rol_id' => 2,
        ], $overrides);
    }

    private function enableWrites(): void
    {
        $this->seed(UsersWritePermissionsSeeder::class);
    }

    public function test_write_routes_require_authentication(): void
    {
        $user = $this->createUser('admin');
        $this->post(route('usuarios.store'), $this->userPayload())->assertRedirect(route('login'));
        $this->get(route('usuarios.edit', $user))->assertRedirect(route('login'));
        $this->put(route('usuarios.update', $user), [])->assertRedirect(route('login'));
    }

    #[DataProvider('roleAccess')]
    public function test_write_permissions_are_enforced_for_every_role(string $role, int $directoryStatus, int $accessLogStatus): void
    {
        $this->enableWrites();
        $actor = $this->createUser($role);
        $subject = $this->createUser('admin');
        $this->actingAs($actor, 'web');
        $snapshot = $this->snapshot();
        $status = $role === 'root' ? 200 : 403;
        $this->get(route('usuarios.edit', $subject))->assertStatus($status);
        if ($role !== 'root') {
            $this->post(route('usuarios.store'), $this->userPayload())->assertForbidden();
            $this->put(route('usuarios.update', $subject), [])->assertForbidden();
        }
        $this->assertSame($snapshot, $this->snapshot());
    }

    public function test_directory_read_permission_alone_does_not_authorize_writes(): void
    {
        $root = $this->createUser('root');
        $subject = $this->createUser('admin');
        $this->actingAs($root, 'web');
        $this->post(route('usuarios.store'), $this->userPayload())->assertForbidden();
        $this->get(route('usuarios.edit', $subject))->assertForbidden();
        $this->put(route('usuarios.update', $subject), [])->assertForbidden();
    }

    public function test_create_saves_complete_legacy_user_and_spatie_role_atomically(): void
    {
        $this->enableWrites();
        $root = $this->createUser('root');
        $this->actingAs($root, 'web')
            ->post(route('usuarios.store'), $this->userPayload())
            ->assertRedirect(route('usuarios.index'))->assertSessionHas('message');
        $user = User::where('Usuario', 'nueva')->firstOrFail();
        $this->assertSame('María José', $user->Nombre);
        $this->assertSame('M', $user->Sexo);
        $this->assertSame(2, $user->plaza_id);
        $this->assertSame(2, $user->rol_id);
        $this->assertSame(sha1('clave-nueva'), $user->Password);
        $this->assertSame(sha1((string) $user->id), $user->sha1_id);
        $this->assertTrue(password_verify((string) $user->id, $user->Hash));
        $this->assertSame(['admin'], $user->getRoleNames()->all());
        $this->assertDatabaseCount('tbl_bitacora_accesos', 0);
    }

    public function test_update_preserves_password_sex_identifiers_and_existing_roles_when_role_unchanged(): void
    {
        $this->enableWrites();
        $root = $this->createUser('root');
        $subject = $this->createUser('admin');
        $subject->assignRole('socio');
        $original = $subject->getAttributes();
        $payload = $this->userPayload(['password' => '', 'rol_id' => 2]);
        unset($payload['sexo']);
        $this->actingAs($root, 'web')->put(route('usuarios.update', $subject), $payload)
            ->assertRedirect(route('usuarios.index'))->assertSessionHas('message', 'Perfil Actualizado');
        $subject->refresh();
        foreach (['Password', 'Hash', 'sha1_id', 'Sexo'] as $field) {
            $this->assertSame($original[$field], $subject->{$field});
        }
        $this->assertSame(2, $subject->plaza_id);
        $this->assertSame('María José', $subject->Nombre);
        $this->assertEqualsCanonicalizing(['admin', 'socio'], $subject->getRoleNames()->all());
    }

    public function test_logout_requires_csrf_and_invalidates_the_authenticated_session(): void
    {
        // Enable real CSRF verification while retaining this test's isolated SQLite DB.
        $this->app['env'] = 'local';
        $token = str_repeat('a', 40);
        $user = $this->createUser('root');
        $this->actingAs($user, 'web')->withSession([
            '_token' => $token,
            'logout-test-marker' => 'must-be-cleared',
        ])->get(route('inicio'))->assertOk()
            ->assertSee('action="'.route('logout').'" method="POST"', false);

        $this->post(route('logout'))->assertStatus(419);
        $this->assertAuthenticatedAs($user, 'web');

        $this->post(route('logout'), ['_token' => $token])
            ->assertRedirect(route('login'))
            ->assertSessionMissing('logout-test-marker');
        $this->assertGuest('web');
        $this->assertNotSame($token, session()->token());
        $this->get(route('inicio'))->assertRedirect(route('login'));
    }

    public function test_changed_role_and_password_remain_compatible_with_legacy_login(): void
    {
        $this->enableWrites();
        $root = $this->createUser('root');
        $subject = $this->createUser('admin');
        $payload = $this->userPayload(['rol_id' => 5]);
        unset($payload['sexo']);
        $this->actingAs($root, 'web')->put(route('usuarios.update', $subject), $payload)->assertRedirect();
        $subject->refresh();
        $this->assertSame(5, $subject->rol_id);
        $this->assertSame(['socio'], $subject->getRoleNames()->all());
        $this->assertSame(sha1('clave-nueva'), $subject->Password);

        // Only synthetic SQLite data; exercise the active login without changing it.
        Schema::create('tbl_reporte_ventas', function (Blueprint $table): void {
            $table->date('Fecha');
            $table->integer('plaza_id');
        });
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->post(route('login.store'), ['username' => 'nueva', 'password' => 'clave-nueva'])
            ->assertRedirect(route('inicio'));
        $this->assertAuthenticatedAs($subject, 'web');
        $this->assertSame(sha1('clave-nueva'), $subject->fresh()->Password);
        $this->assertDatabaseHas('tbl_bitacora_accesos', ['usuario_id' => $subject->id, 'plaza_id' => 2]);
    }

    #[DataProvider('legacyLoginRoles')]
    public function test_login_reconciles_a_legacy_created_user_without_a_spatie_assignment(
        int $legacyRoleId,
        string $spatieRole,
    ): void {
        $subject = $this->createLegacyOnlyUser($legacyRoleId);
        $this->createLoginReportTable();

        $this->post(route('login.store'), [
            'username' => $subject->Usuario,
            'password' => 'clave-de-prueba',
        ])->assertRedirect(route('inicio'));

        $this->assertAuthenticatedAs($subject, 'web');
        $this->assertSame([$spatieRole], $subject->fresh()->getRoleNames()->all());
        $this->assertDatabaseHas('tbl_bitacora_accesos', [
            'usuario_id' => $subject->id,
            'plaza_id' => $subject->plaza_id,
        ]);
    }

    public static function legacyLoginRoles(): array
    {
        return [
            'dueño' => [1, 'superadmin'],
            'manager' => [2, 'admin'],
            'gerente' => [3, 'root'],
            'submanager' => [4, 'submanager'],
            'socio' => [5, 'socio'],
        ];
    }

    public function test_login_replaces_a_stale_spatie_role_after_a_legacy_role_change(): void
    {
        $subject = $this->createUser('admin');
        DB::table('tbl_usuarios')->where('id', $subject->id)->update(['rol_id' => 4]);
        $this->createLoginReportTable();

        $this->post(route('login.store'), [
            'username' => $subject->Usuario,
            'password' => 'clave-de-prueba',
        ])->assertRedirect(route('inicio'));

        $this->assertSame(['submanager'], $subject->fresh()->getRoleNames()->all());
        $this->assertDatabaseHas('tbl_bitacora_accesos', ['usuario_id' => $subject->id]);
    }

    public function test_login_rejects_an_invalid_legacy_role_without_assigning_access_or_logging_it(): void
    {
        DB::table('tbl_roles')->insert(['id' => 99, 'Nombre' => 'No migrado']);
        $subject = $this->createLegacyOnlyUser(99);
        $this->createLoginReportTable();

        $this->post(route('login.store'), [
            'username' => $subject->Usuario,
            'password' => 'clave-de-prueba',
        ])->assertForbidden();

        $this->assertGuest('web');
        $this->assertSame([], $subject->fresh()->getRoleNames()->all());
        $this->assertDatabaseMissing('tbl_bitacora_accesos', ['usuario_id' => $subject->id]);
    }

    public function test_login_rejects_a_legacy_role_when_its_spatie_role_is_not_provisioned(): void
    {
        Role::findByName('submanager', 'web')->delete();
        $subject = $this->createLegacyOnlyUser(4);
        $this->createLoginReportTable();

        $this->post(route('login.store'), [
            'username' => $subject->Usuario,
            'password' => 'clave-de-prueba',
        ])->assertForbidden();

        $this->assertGuest('web');
        $this->assertDatabaseMissing('tbl_bitacora_accesos', ['usuario_id' => $subject->id]);
    }

    public function test_success_messages_preserve_the_legacy_edit_alert_and_creation_dialog_contract(): void
    {
        $root = $this->createUser('root');

        $this->actingAs($root, 'web')
            ->withSession(['message' => 'Perfil Actualizado'])
            ->get(route('usuarios.index'))
            ->assertSee('ui-feedback--banner', false)
            ->assertSee('Perfil Actualizado')
            ->assertSee('data-dismiss-feedback', false)
            ->assertDontSee('data-feedback-dialog', false);

        $this->actingAs($root, 'web')
            ->withSession(['message' => 'Usuario Ingresado!', 'user-created' => true])
            ->get(route('usuarios.index'))
            ->assertSee('data-user-created-dialog', false)
            ->assertSee('Registro Exitoso!')
            ->assertSee('data-redirect-url', false)
            ->assertSee('data-duration="2200"', false)
            ->assertDontSee('ui-feedback--banner', false);
    }

    public function test_validation_errors_suppress_stale_success_feedback(): void
    {
        $root = $this->createUser('root');
        $errors = new ViewErrorBag;
        $errors->put('default', new MessageBag(['nombre' => ['Error de prueba.']]));
        $screen = $this->actingAs($root, 'web')->get(route('usuarios.index'))->assertOk();
        session()->put(['message' => 'Usuario Ingresado!', 'user-created' => true]);
        // Render the real view with conflicting success/error state explicitly.
        $html = $screen->original->with('errors', $errors)->render();
        $this->assertStringContainsString('Error de prueba.', $html);
        $this->assertStringNotContainsString('data-feedback-dialog', $html);
        $this->assertStringNotContainsString('ui-feedback--banner', $html);
    }

    public function test_update_allows_unchanged_unique_fields_and_edit_does_not_expose_password_hash(): void
    {
        $this->enableWrites();
        $root = $this->createUser('root');
        $subject = $this->createUser('superadmin', plaza: 2);
        $this->actingAs($root, 'web')->get(route('usuarios.edit', $subject))->assertOk()
            ->assertSee('Apellido Paterno')->assertSee('Deja vacío')
            ->assertDontSee($subject->Password)->assertDontSee($subject->Hash)->assertDontSee($subject->sha1_id)
            ->assertDontSee('name="sexo"', false);
        $payload = $this->userPayload(['email' => $subject->Email, 'usuario' => $subject->Usuario, 'rol_id' => 1]);
        unset($payload['sexo'], $payload['password']);
        $this->put(route('usuarios.update', $subject), $payload)->assertRedirect(route('usuarios.index'));
        $this->assertSame(1, $subject->fresh()->rol_id);
        $this->get(route('usuarios.edit', 999999))->assertNotFound();
    }

    public function test_create_validates_required_fields_duplicates_references_and_lengths_without_writes(): void
    {
        $this->enableWrites();
        $root = $this->createUser('root');
        $this->actingAs($root, 'web');
        $before = $this->snapshot();
        $this->post(route('usuarios.store'), [])->assertSessionHasErrors([
            'nombre', 'apepat', 'apemat', 'edad', 'sexo', 'email', 'usuario', 'password', 'plaza_id', 'rol_id',
        ]);
        $this->post(route('usuarios.store'), $this->userPayload([
            'nombre' => str_repeat('a', 26), 'edad' => 128, 'sexo' => 'X',
            'email' => $root->Email, 'usuario' => $root->Usuario, 'plaza_id' => 999, 'rol_id' => 999,
        ]))->assertSessionHasErrors(['nombre', 'edad', 'sexo', 'email', 'usuario', 'plaza_id', 'rol_id']);
        $this->assertSame($before, $this->snapshot());
    }

    public function test_update_rejects_duplicate_credentials_and_protected_fields_without_changes(): void
    {
        $this->enableWrites();
        $root = $this->createUser('root');
        $subject = $this->createUser('admin');
        $before = $this->snapshot();
        $this->actingAs($root, 'web')->put(route('usuarios.update', $subject), $this->userPayload([
            'email' => $root->Email, 'usuario' => $root->Usuario,
            'Password' => 'spoof', 'Hash' => 'spoof', 'sha1_id' => 'spoof',
        ]))->assertSessionHasErrors(['email', 'usuario', 'sexo', 'Password', 'Hash', 'sha1_id']);
        $this->assertSame($before, $this->snapshot());
    }

    public function test_create_rolls_back_if_spatie_role_sync_fails(): void
    {
        Role::findByName('admin', 'web')->delete();
        $before = $this->snapshot();
        try {
            app(SaveUser::class)->handle($this->userPayload());
            $this->fail('Expected missing mapped role to abort transaction');
        } catch (RoleDoesNotExist) {
            $this->assertSame($before, $this->snapshot());
        }
    }

    public function test_update_rolls_back_fields_password_and_role_if_sync_fails(): void
    {
        $subject = $this->createUser('admin');
        Role::findByName('socio', 'web')->delete();
        $before = $this->snapshot();
        try {
            app(SaveUser::class)->handle($this->userPayload(['rol_id' => 5]), $subject);
            $this->fail('Expected missing mapped role to abort transaction');
        } catch (RoleDoesNotExist) {
            $this->assertSame($before, $this->snapshot());
        }
    }

    public function test_write_permissions_seeder_is_idempotent_and_preserves_unrelated_permissions(): void
    {
        $other = Permission::findOrCreate('other-module.update', 'web');
        Role::findByName('admin', 'web')->givePermissionTo($other);
        $this->enableWrites();
        $first = DB::table('role_has_permissions')->orderBy('permission_id')->orderBy('role_id')->get()->toJson();
        $this->enableWrites();
        $this->assertSame($first, DB::table('role_has_permissions')->orderBy('permission_id')->orderBy('role_id')->get()->toJson());
        $this->assertTrue(Role::findByName('admin', 'web')->hasPermissionTo('other-module.update'));
        $this->assertFalse(Role::findByName('superadmin', 'web')->hasPermissionTo('users.update'));
    }

    public function test_xlsx_preserves_all_rows_across_batches_filters_sort_formats_and_formula_safety(): void
    {
        $root = $this->createUser('root');
        $rows = [];
        for ($i = 0; $i < 505; $i++) {
            $rows[] = [
                'Nombre' => sprintf('Listado %04d', $i), 'Apepat' => 'Apellido', 'Apemat' => 'Segundo',
                'Edad' => 30, 'Sexo' => 'H', 'Email' => "export$i@example.test", 'Usuario' => "export$i",
                'Password' => sha1('test'), 'Hash' => 'test', 'sha1_id' => sha1((string) $i), 'rol_id' => 2, 'plaza_id' => 2,
            ];
        }
        DB::table('tbl_usuarios')->insert($rows);
        DB::table('tbl_usuarios')->where('Usuario', 'export0')->update(['Email' => '=HYPERLINK("test")']);
        $before = $this->snapshot();
        $response = $this->actingAs($root, 'web')->get(route('usuarios.export', [
            'format' => 'xlsx', 'q' => 'Listado', 'sort' => 'name', 'direction' => 'desc', 'page' => 2,
        ]))->assertOk()->assertDownload('usuarios.xlsx');
        $this->assertStringStartsWith('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
        $path = $response->baseResponse->getFile()->getPathname();
        try {
            $book = IOFactory::load($path);
            $sheet = $book->getActiveSheet();
            $this->assertSame('Usuarios', $sheet->getCell('A1')->getValue());
            $this->assertSame(['Nombre', 'Edad', 'Email', 'Usuario', 'Plaza'], $sheet->rangeToArray('A3:E3')[0]);
            $this->assertSame(508, $sheet->getHighestRow());
            $this->assertSame('E', $sheet->getHighestColumn());
            $this->assertSame('Listado 0504', $sheet->getCell('A4')->getValue());
            $this->assertSame('Listado 0000', $sheet->getCell('A508')->getValue());
            $this->assertSame('=HYPERLINK("test")', $sheet->getCell('C508')->getValue());
            $this->assertSame('s', $sheet->getCell('C508')->getDataType());
            $this->assertSame('30', $sheet->getCell('B4')->getValue());
            $this->assertSame('A4', $sheet->getFreezePane());
            $this->assertSame('A3:E508', $sheet->getAutoFilter()->getRange());
            $this->assertTrue($sheet->getStyle('A1')->getFont()->getBold());
            $this->assertSame(['A1:E1' => 'A1:E1'], $sheet->getMergeCells());
            $book->disconnectWorksheets();
        } finally {
            @unlink($path);
        }
        $this->assertSame($before, $this->snapshot());
    }

    public function test_access_xlsx_preserves_date_scope_and_current_role(): void
    {
        $root = $this->createUser('root');
        $this->insertAccess($root, '2026-10-10', 2, '09:00:00');
        $this->insertAccess($root, '2026-10-11', 1, '10:00:00');
        $response = $this->actingAs($root, 'web')->get(route('usuarios.accesos.export', [
            'format' => 'xlsx', 'date' => '2026-10-10',
        ]))->assertOk()->assertDownload('bitacora-accesos-2026-10-10.xlsx');
        $path = $response->baseResponse->getFile()->getPathname();
        try {
            $book = IOFactory::load($path);
            $sheet = $book->getActiveSheet();
            $this->assertSame(4, $sheet->getHighestRow());
            $this->assertSame(['2026-10-10', '09:00:00', 'Plaza del acceso', $root->Nombre, 'Gerente'], $sheet->rangeToArray('A4:E4')[0]);
            $book->disconnectWorksheets();
        } finally {
            @unlink($path);
        }
    }

    public function test_pdf_download_is_real_and_uses_safe_options_filters_and_sorted_complete_scope(): void
    {
        $root = $this->createUser('root');
        for ($i = 0; $i < 105; $i++) {
            $other = $this->createUser('admin', plaza: 2);
            $other->Nombre = sprintf('PDF %03d', $i);
            $other->save();
        }
        $other->Nombre = 'PDF <script>alert(1)</script>';
        $other->save();
        $this->actingAs($root, 'web');
        $before = $this->snapshot();
        $wrapper = $this->capturePdfSource();
        $response = $this->get(route('usuarios.export', [
            'format' => 'pdf', 'q' => 'PDF', 'sort' => 'name', 'direction' => 'asc', 'page' => 2,
        ]))->assertOk()->assertDownload('usuarios.pdf')->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $pdf = $wrapper->getDomPDF();
        $dom = $wrapper->sourceDom;
        $this->assertSame(0, $dom->getElementsByTagName('script')->length);
        $cells = $dom->getElementsByTagName('td');
        $this->assertSame(105 * 5, $cells->length);
        $this->assertSame('PDF 000', $cells->item(0)->textContent);
        $this->assertSame('PDF <script>alert(1)</script>', $cells->item(104 * 5)->textContent);
        $this->assertStringNotContainsString($root->Nombre, $dom->textContent);
        $options = $pdf->getOptions();
        $this->assertFalse($options->getIsRemoteEnabled());
        $this->assertFalse($options->getIsPhpEnabled());
        $this->assertFalse($options->getIsJavascriptEnabled());
        $this->assertSame('DejaVu Sans', $options->getDefaultFont());
        $this->assertEqualsWithDelta([0, 0, 595.28, 841.89], $pdf->getPaperSize(), 0.1);
        $this->assertSame($before, $this->snapshot());
    }

    public function test_access_pdf_preserves_date_headers_and_recorded_plaza(): void
    {
        $root = $this->createUser('root');
        $this->insertAccess($root, '2026-10-10', 2, '09:00:00');
        $this->insertAccess($root, '2026-10-11', 1, '10:00:00');
        $wrapper = $this->capturePdfSource();
        $response = $this->actingAs($root, 'web')->get(route('usuarios.accesos.export', [
            'format' => 'pdf', 'date' => '2026-10-10',
        ]))->assertOk()->assertDownload('bitacora-accesos-2026-10-10.pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $dom = $wrapper->sourceDom;
        $this->assertSame(5, $dom->getElementsByTagName('td')->length);
        $this->assertStringContainsString('Fecha: 2026-10-10', $dom->textContent);
        $this->assertStringContainsString('Plaza del acceso', $dom->textContent);
        $this->assertStringNotContainsString('2026-10-11', $dom->textContent);
    }

    public function test_create_validation_reopens_modal_and_never_flashes_password(): void
    {
        $this->enableWrites();
        $root = $this->createUser('root');
        $this->actingAs($root, 'web')->from(route('usuarios.index'))
            ->post(route('usuarios.store'), $this->userPayload(['usuario' => $root->Usuario]))
            ->assertRedirect(route('usuarios.index'))->assertSessionHasErrors('usuario')
            ->assertSessionMissing('_old_input.password');
        $this->assertSame(['usuario'], array_keys(session('errors')->getBag('default')->messages()));
        $screen = $this->withCookie(config('session.cookie'), session()->getId())
            ->get(route('usuarios.index'))->assertOk();
        $this->assertTrue(str_contains($screen->getContent(), 'data-reopen'), 'Modal should reopen after validation');
        $screen->assertSee('María José')->assertDontSee('clave-nueva');
    }

    public function test_filter_validation_does_not_open_create_modal(): void
    {
        $this->enableWrites();
        $root = $this->createUser('root');
        $this->actingAs($root, 'web')->from(route('usuarios.index'))
            ->get(route('usuarios.index', ['sort' => 'invalid']))
            ->assertSessionHasErrors('sort');
        $this->withCookie(config('session.cookie'), session()->getId())
            ->get(route('usuarios.index'))->assertOk()->assertSee('Revisa los datos:')
            ->assertDontSee('data-reopen', false);
    }

    public function test_update_rejects_whitespace_password_that_the_login_would_reject(): void
    {
        $this->enableWrites();
        $root = $this->createUser('root');
        $subject = $this->createUser('admin');
        $before = $this->snapshot();
        $payload = $this->userPayload(['password' => '   ']);
        unset($payload['sexo']);
        $this->actingAs($root, 'web')->put(route('usuarios.update', $subject), $payload)
            ->assertSessionHasErrors('password');
        $this->assertSame($before, $this->snapshot());
    }

    private function capturePdfSource(): PDF
    {
        $wrapper = new class(app('dompdf'), app('config'), app('files'), app('view')) extends PDF
        {
            public ?\DOMDocument $sourceDom = null;

            public function render(): void
            {
                // Dompdf releases table nodes while rendering; retain only a test snapshot.
                $this->sourceDom = clone $this->getDomPDF()->getDom();
                parent::render();
            }
        };
        $this->app->instance('dompdf.wrapper', $wrapper);

        return $wrapper;
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

    private function createLegacyOnlyUser(int $legacyRoleId, int $plaza = 1): User
    {
        $number = DB::table('tbl_usuarios')->count() + 1;
        $id = DB::table('tbl_usuarios')->insertGetId([
            'Nombre' => 'Legacy '.$number,
            'Apepat' => 'Apellido',
            'Apemat' => 'Segundo',
            'Edad' => 30,
            'Sexo' => 'H',
            'Email' => 'legacy'.$number.'@example.test',
            'Usuario' => 'legacy'.$number,
            'Password' => sha1('clave-de-prueba'),
            'Hash' => 'HASH_NO_PUBLICAR',
            'sha1_id' => 'SHA1_ID_NO_PUBLICAR',
            'rol_id' => $legacyRoleId,
            'plaza_id' => $plaza,
        ]);

        return User::query()->findOrFail($id);
    }

    private function createLoginReportTable(): void
    {
        Schema::create('tbl_reporte_ventas', function (Blueprint $table): void {
            $table->date('Fecha');
            $table->integer('plaza_id');
        });
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
