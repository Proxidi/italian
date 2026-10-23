<?php

namespace Tests\Feature;

use App\Actions\Users\SaveUser;
use App\Models\Usuario;
use Barryvdh\DomPDF\PDF;
use Carbon\Carbon;
use Database\Seeders\UsersReadPermissionsSeeder;
use Database\Seeders\UsersWritePermissionsSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
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

            'hashing.driver' => 'argon2id',
            'hashing.argon.memory' => 1024, 'hashing.argon.time' => 1,
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

        DB::table('plazas')->insert([
            [
                'id' => 1,
                'nombre' => 'Plaza actual',
                'acronimo' => 'ACT',
            ],
            [
                'id' => 2,
                'nombre' => 'Plaza del acceso',
                'acronimo' => 'ACC',
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
            ['nombre', 'edad', 'email', 'usuario', 'plaza', 'id'],
            array_keys((array) $records->first()),
        );

        $response
            ->assertSee($other->nombre)
            ->assertSee('Plaza del acceso')
            ->assertSee('Agregar Usuario')
            ->assertSee('Editar')
            ->assertDontSee('>Eliminar<', false)
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
        $subject->syncRoles(['socio']);

        $response = $this->actingAs($root, 'web')
            ->get(route('usuarios.accesos', [
                'date' => '2026-10-10',
            ]))
            ->assertOk();

        $records = $response->viewData('records');

        $this->assertSame(1, $records->total());
        $this->assertSame(200, $records->perPage());
        $this->assertSame('2026-10-10', $records->first()->fecha);
        $this->assertSame('Plaza del acceso', $records->first()->plaza);
        $this->assertSame('Socio', $records->first()->rol);
        $this->assertSame($subject->nombre, $records->first()->usuario);
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
        $this->assertSame('10:00:00', $records->first()->hora);

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
                'nombre' => 'Consulta '.$number,
                'email' => 'consulta'.$number.'@example.test',
                'usuario' => 'consulta'.$number,
            ];
        }

        DB::table('usuarios')->insert($users);

        $accesses = [];

        for ($number = 1; $number <= 201; $number++) {
            $accesses[] = [
                'fecha' => '2026-10-10',
                'hora' => '10:00:00',
                'usuario_id' => $root->id,
                'plaza_id' => 1,
            ];
        }

        DB::table('bitacora_accesos')->insert($accesses);

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
            'sort' => 'password',
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
            'q' => $target->email,
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

        $this->assertStringContainsString($target->email, $csv);
        $this->assertStringNotContainsString($root->email, $csv);

        $this->get(route('usuarios.export', [
            ...$filters,
            'format' => 'print',
        ]))
            ->assertOk()
            ->assertSee($target->email)
            ->assertDontSee($root->email);
    }

    public function test_values_are_escaped_and_search_wildcards_are_literal(): void
    {
        $root = $this->createUser('root');
        $target = $this->createUser('admin');

        DB::table('usuarios')
            ->where('id', $target->id)
            ->update([
                'nombre' => '<script>x</script>',
                'usuario' => 'persona%especial',
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

    public function test_read_requests_do_not_modify_core_or_spatie_records(): void
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
         * Una concesión administrativa posterior debe conservarse al repetir
         * el seeder; la matriz inicial ya no se impone con sync().
         */
        Role::findByName('admin', 'web')
            ->givePermissionTo('users.directory.view');

        $this->seed(UsersReadPermissionsSeeder::class);
        $this->seed(UsersReadPermissionsSeeder::class);

        $admin = Role::findByName('admin', 'web');

        $this->assertTrue(
            $admin->hasPermissionTo('other-module.view'),
        );

        $this->assertTrue(
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
            'nombre' => 'María José', 'apellido_paterno' => 'Pérez', 'apellido_materno' => 'López',
            'edad' => 32, 'sexo' => 'M', 'email' => 'nueva@example.test',
            'usuario' => 'nueva', 'password' => 'clave-nueva', 'plaza_id' => 2, 'role_id' => 2,
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

    public function test_create_saves_native_user_and_spatie_role_atomically(): void
    {
        $this->enableWrites();
        $root = $this->createUser('root');
        $this->actingAs($root, 'web')
            ->post(route('usuarios.store'), $this->userPayload())
            ->assertRedirect(route('usuarios.index'))->assertSessionHas('message');
        $user = Usuario::where('usuario', 'nueva')->firstOrFail();
        $this->assertSame('María José', $user->nombre);
        $this->assertSame('M', $user->sexo);
        $this->assertSame(2, $user->plaza_id);
        $this->assertTrue(Hash::check('clave-nueva', $user->password));
        $this->assertSame(['admin'], $user->getRoleNames()->all());
        $this->assertDatabaseCount('bitacora_accesos', 0);
    }

    public function test_update_preserves_password_sex_and_existing_role(): void
    {
        $this->enableWrites();
        $root = $this->createUser('root');
        $subject = $this->createUser('admin');
        $original = $subject->getAttributes();
        $payload = $this->userPayload(['password' => '', 'role_id' => 2]);
        unset($payload['sexo']);
        $this->actingAs($root, 'web')->put(route('usuarios.update', $subject), $payload)
            ->assertRedirect(route('usuarios.index'))->assertSessionHas('message', 'Perfil Actualizado');
        $subject->refresh();
        foreach (['password', 'sexo'] as $field) {
            $this->assertSame($original[$field], $subject->{$field});
        }
        $this->assertSame(2, $subject->plaza_id);
        $this->assertSame('María José', $subject->nombre);
        $this->assertSame(['admin'], $subject->getRoleNames()->all());
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

    public function test_changed_role_and_password_work_with_native_login(): void
    {
        $this->enableWrites();
        $root = $this->createUser('root');
        $subject = $this->createUser('admin');
        $payload = $this->userPayload(['role_id' => 5]);
        unset($payload['sexo']);
        $this->actingAs($root, 'web')->put(route('usuarios.update', $subject), $payload)->assertRedirect();
        $subject->refresh();
        $this->assertSame(['socio'], $subject->getRoleNames()->all());
        $this->assertTrue(Hash::check('clave-nueva', $subject->password));

        // Only synthetic SQLite data; exercise the active login without changing it.
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->post(route('login.store'), ['username' => 'nueva', 'password' => 'clave-nueva'])
            ->assertRedirect(route('inicio'));
        $this->assertAuthenticatedAs($subject, 'web');
        $this->assertTrue(Hash::check('clave-nueva', $subject->fresh()->password));
        $this->assertDatabaseHas('bitacora_accesos', ['usuario_id' => $subject->id, 'plaza_id' => 2]);
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
            ->assertDontSee($subject->password)
            ->assertDontSee('name="sexo"', false);
        $payload = $this->userPayload(['email' => $subject->email, 'usuario' => $subject->usuario, 'role_id' => 1]);
        unset($payload['sexo'], $payload['password']);
        $this->put(route('usuarios.update', $subject), $payload)->assertRedirect(route('usuarios.index'));
        $this->assertSame(['superadmin'], $subject->fresh()->getRoleNames()->all());
        $this->get(route('usuarios.edit', 999999))->assertNotFound();
    }

    public function test_create_validates_required_fields_duplicates_references_and_lengths_without_writes(): void
    {
        $this->enableWrites();
        $root = $this->createUser('root');
        $this->actingAs($root, 'web');
        $before = $this->snapshot();
        $this->post(route('usuarios.store'), [])->assertSessionHasErrors([
            'nombre', 'apellido_paterno', 'apellido_materno', 'edad', 'sexo', 'email', 'usuario', 'password', 'plaza_id', 'role_id',
        ]);
        $this->post(route('usuarios.store'), $this->userPayload([
            'nombre' => str_repeat('a', 26), 'edad' => 128, 'sexo' => 'X',
            'email' => $root->email, 'usuario' => $root->usuario, 'plaza_id' => 999, 'role_id' => 999,
        ]))->assertSessionHasErrors(['nombre', 'edad', 'sexo', 'email', 'usuario', 'plaza_id', 'role_id']);
        $this->assertSame($before, $this->snapshot());
    }

    public function test_update_rejects_duplicate_credentials_and_protected_fields_without_changes(): void
    {
        $this->enableWrites();
        $root = $this->createUser('root');
        $subject = $this->createUser('admin');
        $before = $this->snapshot();
        $this->actingAs($root, 'web')->put(route('usuarios.update', $subject), $this->userPayload([
            'email' => $root->email, 'usuario' => $root->usuario,
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
        } catch (ValidationException) {
            $this->assertSame($before, $this->snapshot());
        }
    }

    public function test_update_rolls_back_fields_password_and_role_if_sync_fails(): void
    {
        $subject = $this->createUser('admin');
        Role::findByName('socio', 'web')->delete();
        $before = $this->snapshot();
        try {
            app(SaveUser::class)->handle($this->userPayload(['role_id' => 5]), $subject);
            $this->fail('Expected missing mapped role to abort transaction');
        } catch (ValidationException) {
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
                'nombre' => sprintf('Listado %04d', $i), 'apellido_paterno' => 'Apellido', 'apellido_materno' => 'Segundo',
                'edad' => 30, 'sexo' => 'H', 'email' => "export$i@example.test", 'usuario' => "export$i",
                'password' => sha1('test'), 'plaza_id' => 2, 'created_at' => null, 'updated_at' => null,
            ];
        }
        DB::table('usuarios')->insert($rows);
        DB::table('usuarios')->where('usuario', 'export0')->update(['email' => '=HYPERLINK("test")']);
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
            $this->assertSame(['2026-10-10', '09:00:00', 'Plaza del acceso', $root->nombre, 'Gerente'], $sheet->rangeToArray('A4:E4')[0]);
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
            $other->nombre = sprintf('PDF %03d', $i);
            $other->save();
        }
        $other->nombre = 'PDF <script>alert(1)</script>';
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
        $this->assertStringNotContainsString($root->nombre, $dom->textContent);
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
            ->post(route('usuarios.store'), $this->userPayload(['usuario' => $root->usuario]))
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

    private function createUser(string $role, int $plaza = 1): Usuario
    {
        $number = Usuario::count() + 1;
        $user = Usuario::forceCreate([
            'nombre' => 'Persona '.$number, 'apellido_paterno' => 'Apellido', 'apellido_materno' => 'Segundo',
            'edad' => 30, 'sexo' => 'H', 'email' => 'persona'.$number.'@example.test',
            'usuario' => 'persona'.$number, 'password' => sha1('clave-de-prueba'), 'plaza_id' => $plaza,
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function insertAccess(
        Usuario $user,
        string $date,
        int $plazaId,
        string $time,
    ): void {
        DB::table('bitacora_accesos')->insert([
            'fecha' => $date,
            'hora' => $time,
            'usuario_id' => $user->id,
            'plaza_id' => $plazaId,
        ]);
    }

    private function snapshot(): array
    {
        $snapshot = [];

        foreach ([
            'usuarios',
            'plazas',
            'bitacora_accesos',
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
        $this->artisan('migrate', ['--database' => self::CONNECTION, '--force' => true])->assertExitCode(0);
    }
}
