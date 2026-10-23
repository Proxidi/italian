<?php

namespace Tests\Feature\Auth;

use App\Models\Usuario;
use App\Support\PasswordCompatibility;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\Support\CoreFunctionalTestCase;

class AuthenticationTest extends CoreFunctionalTestCase
{
    public function test_login_screen_retains_design_and_only_the_existing_authentication_routes(): void
    {
        $this->get('/login')->assertOk()->assertSee('Any Time is Coffee Time')
            ->assertSee('data-login-form', false)->assertSee('Contraseña');
        foreach (['/register', '/forgot-password', '/reset-password/example', '/verify-email', '/confirm-password'] as $path) {
            $this->get($path)->assertNotFound();
        }
    }

    public function test_imported_sha1_is_upgraded_then_native_session_and_second_login_work(): void
    {
        $user = $this->nativeUser();
        $before = session()->getId();
        $this->post('/login', ['username' => $user->usuario, 'password' => 'clave-de-prueba'])
            ->assertRedirect(route('inicio'))->assertSessionMissing('msj')->assertSessionMissing('hoy');
        $modern = $user->fresh()->password;
        $this->assertSame('argon2id', password_get_info($modern)['algoName']);
        $this->assertTrue(Hash::check('clave-de-prueba', $modern));
        $this->assertFalse(Hash::needsRehash($modern));
        $this->assertNotSame($before, session()->getId());
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('bitacora_accesos', ['usuario_id' => $user->id, 'plaza_id' => $user->plaza_id, 'fecha' => '2026-10-23', 'hora' => '00:30:00']);
        Auth::forgetGuards();
        $this->get('/Inicio')->assertOk()->assertSee('Plaza sintética');
        $this->assertInstanceOf(Usuario::class, Auth::user());
        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
        $this->post('/login', ['username' => $user->usuario, 'password' => 'clave-de-prueba'])->assertRedirect(route('inicio'));
        $this->assertSame($modern, $user->fresh()->password);
        $this->assertDatabaseCount('bitacora_accesos', 2);
        $this->assertNotInstanceOf(\PDO::class, DB::connection('legacy')->getRawPdo());
    }

    #[DataProvider('invalidCredentials')]
    public function test_bad_credentials_never_modify_a_hash_or_record_a_success(string $case): void
    {
        $user = $this->nativeUser();
        if ($case === 'invalid_sha1') {
            $user->forceFill(['password' => str_repeat('z', 40)])->save();
        }
        $hash = $user->password;
        $username = $case === 'missing_user' ? 'absent' : $user->usuario;
        $password = $case === 'wrong_password' ? 'incorrecta' : 'clave-de-prueba';
        $this->post('/login', compact('username', 'password'))->assertSessionHasErrors('username');
        $this->assertGuest();
        $this->assertSame($hash, $user->fresh()->password);
        $this->assertDatabaseCount('bitacora_accesos', 0);
    }

    public static function invalidCredentials(): array
    {
        return [['wrong_password'], ['invalid_sha1'], ['missing_user']];
    }

    #[DataProvider('roles')]
    public function test_each_role_keeps_its_panel_grants_and_polymorphic_assignment(string $role, string $panel): void
    {
        $user = $this->nativeUser($role);
        $pivots = DB::table('model_has_roles')->get()->toJson();
        $this->post('/login', ['username' => $user->usuario, 'password' => 'clave-de-prueba'])->assertRedirect(route('inicio'));
        $this->get('/Inicio')->assertOk()->assertViewHas('layout', fn ($layout) => $layout['panel'] === $panel && $layout['profile']['plaza'] === 'Plaza sintética');
        $this->assertSame($pivots, DB::table('model_has_roles')->get()->toJson());
        $this->assertSame('usuario', $user->getMorphClass());
        $this->get('/Usuarios')->assertStatus($role === 'root' ? 200 : 403);
        $this->get('/Usuarios/listaAccesos')->assertStatus(in_array($role, ['root', 'superadmin']) ? 200 : 403);
        if ($role === 'superadmin') {
            $this->assertFalse($user->can('users.create'));
        }
    }

    public static function roles(): array
    {
        return [['root', 'root'], ['superadmin', 'superadmin'], ['admin', 'manager'], ['submanager', 'manager'], ['socio', 'socio']];
    }

    #[DataProvider('invalidAccounts')]
    public function test_invalid_account_relations_reject_login_without_rehashing_or_assigning_roles(string $case): void
    {
        $user = $this->nativeUser();
        if ($case === 'missing_plaza') {
            Schema::disableForeignKeyConstraints();
            DB::table('usuarios')->where('id', $user->id)->update(['plaza_id' => 9999]);
            Schema::enableForeignKeyConstraints();
        } elseif ($case === 'no_role') {
            $user->syncRoles([]);
        } elseif ($case === 'multiple_roles') {
            $user->assignRole('admin');
        } else {
            $role = Role::create(['name' => 'unknown', 'guard_name' => 'web']);
            $user->syncRoles([$role]);
        }
        $pivots = DB::table('model_has_roles')->get()->toJson();
        $this->post('/login', ['username' => $user->usuario, 'password' => 'clave-de-prueba'])->assertSessionHasErrors('username');
        $this->assertGuest();
        $this->assertSame(sha1('clave-de-prueba'), $user->fresh()->password);
        $this->assertSame($pivots, DB::table('model_has_roles')->get()->toJson());
        $this->assertDatabaseCount('bitacora_accesos', 0);
    }

    public static function invalidAccounts(): array
    {
        return [['missing_plaza'], ['no_role'], ['multiple_roles'], ['unknown_role']];
    }

    public function test_supported_modern_bcrypt_is_verified_and_migrated_without_disabling_algorithm_checks(): void
    {
        $user = $this->nativeUser('root', ['password' => Hash::driver('bcrypt')->make('clave-de-prueba')]);
        $this->post('/login', ['username' => $user->usuario, 'password' => 'incorrecta'])->assertSessionHasErrors();
        $this->assertSame('bcrypt', password_get_info($user->fresh()->password)['algoName']);
        $this->post('/login', ['username' => $user->usuario, 'password' => 'clave-de-prueba'])->assertRedirect(route('inicio'));
        $this->assertSame('argon2id', password_get_info($user->fresh()->password)['algoName']);
        $this->assertTrue(config('hashing.argon.verify'));
        $this->assertTrue(config('hashing.bcrypt.verify'));
    }

    public function test_modern_hash_is_rehashed_when_cost_changes(): void
    {
        $user = $this->nativeUser('root', ['password' => Hash::make('clave-de-prueba', ['memory' => 512])]);
        $old = $user->password;
        $this->post('/login', ['username' => $user->usuario, 'password' => 'clave-de-prueba'])->assertRedirect(route('inicio'));
        $this->assertNotSame($old, $user->fresh()->password);
        $this->assertFalse(Hash::needsRehash($user->fresh()->password));
    }

    public function test_password_changed_concurrently_is_never_overwritten_by_a_stale_rehash(): void
    {
        $user = $this->nativeUser();
        $changed = Hash::make('new-concurrent-password');
        $this->mock(PasswordCompatibility::class, function ($mock) use ($user, $changed): void {
            $mock->shouldReceive('verify')->once()->andReturnUsing(function () use ($user, $changed) {
                DB::table('usuarios')->where('id', $user->id)->update(['password' => $changed]);

                return true;
            });
        });
        $this->post('/login', ['username' => $user->usuario, 'password' => 'clave-de-prueba'])->assertSessionHasErrors('username');
        $this->assertSame($changed, $user->fresh()->password);
        $this->assertGuest();
        $this->assertDatabaseCount('bitacora_accesos', 0);
    }

    public function test_mandatory_audit_failure_rolls_back_hash_and_clears_guard_session(): void
    {
        $user = $this->nativeUser();
        DB::listen(function (QueryExecuted $event): void {
            if (str_starts_with($event->sql, 'insert into') && str_contains($event->sql, 'bitacora_accesos')) {
                throw new \RuntimeException('synthetic sensitive failure');
            }
        });
        $this->post('/login', ['username' => $user->usuario, 'password' => 'clave-de-prueba'])
            ->assertStatus(503)->assertDontSee('synthetic sensitive failure');
        $this->assertGuest();
        $this->assertSame(sha1('clave-de-prueba'), $user->fresh()->password);
        $this->assertDatabaseCount('bitacora_accesos', 0);
    }

    public function test_login_is_throttled_and_csrf_protected_and_logout_invalidates_the_session(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['username' => 'absent', 'password' => 'bad'])->assertSessionHasErrors();
        }
        $this->post('/login', ['username' => 'absent', 'password' => 'bad'])->assertStatus(429);
        $this->travel(61)->seconds();
        $user = $this->nativeUser();
        $this->app['env'] = 'local';
        $this->post('/login', ['username' => $user->usuario, 'password' => 'clave-de-prueba'])->assertStatus(419);
        $token = str_repeat('a', 40);
        $this->withSession(['_token' => $token, 'old-marker' => true])->post('/login', ['_token' => $token, 'username' => $user->usuario, 'password' => 'clave-de-prueba'])->assertRedirect(route('inicio'));
        $token = session()->token();
        $this->post('/logout')->assertStatus(419);
        $this->post('/logout', ['_token' => $token])->assertRedirect(route('login'))->assertSessionMissing('old-marker');
        $this->assertGuest();
        $this->assertNotSame($token, session()->token());
        $this->get('/Inicio')->assertRedirect(route('login'));
    }
}
