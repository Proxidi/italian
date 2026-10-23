<?php

namespace Tests\Feature;

use App\Actions\Users\SaveUser;
use App\Models\Acceso;
use App\Models\Plaza;
use App\Models\Usuario;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\CoreFunctionalTestCase;

class NativeCoreTest extends CoreFunctionalTestCase
{
    public function test_create_edit_role_and_plaza_changes_use_native_tables_and_modern_passwords(): void
    {
        $root = $this->nativeUser();
        Plaza::forceCreate(['id' => 7, 'nombre' => 'Plaza nueva', 'acronimo' => 'PN']);
        $this->actingAs($root)->post(route('usuarios.store'), $this->payload())
            ->assertRedirect(route('usuarios.index'))->assertSessionHas('user-created');
        $subject = Usuario::where('usuario', 'nueva')->sole();
        $this->assertTrue(Hash::check('clave-nueva', $subject->password));
        $this->assertSame('argon2id', password_get_info($subject->password)['algoName']);
        $this->assertSame(['admin'], $subject->getRoleNames()->all());
        $this->assertDatabaseHas('model_has_roles', ['model_type' => 'usuario', 'model_id' => $subject->id, 'role_id' => 2]);
        $this->get(route('usuarios.edit', $subject))->assertOk()->assertDontSee($subject->password);
        Acceso::create(['fecha' => '2020-05-03', 'hora' => '23:59:58', 'usuario_id' => $subject->id, 'plaza_id' => 1]);
        $hash = $subject->password;
        $payload = $this->payload(['password' => '', 'role_id' => 5, 'plaza_id' => 7]);
        unset($payload['sexo']);
        $this->put(route('usuarios.update', $subject), $payload)->assertRedirect(route('usuarios.index'));
        $this->assertSame($hash, $subject->fresh()->password);
        $this->assertSame(7, $subject->fresh()->plaza_id);
        $this->assertSame(['socio'], $subject->fresh()->getRoleNames()->all());
        $this->assertSame(1, Acceso::sole()->plaza_id);
        $payload['password'] = 'contraseña-nueva';
        $this->put(route('usuarios.update', $subject), $payload)->assertRedirect(route('usuarios.index'));
        $this->assertTrue(Hash::check('contraseña-nueva', $subject->fresh()->password));
        $this->post('/logout');
        $this->post('/login', ['username' => 'nueva', 'password' => 'contraseña-nueva'])->assertRedirect(route('inicio'));
        $this->assertDatabaseHas('bitacora_accesos', ['fecha' => '2026-10-23', 'hora' => '00:30:00', 'usuario_id' => $subject->id, 'plaza_id' => 7]);
        $this->assertDatabaseCount('bitacora_accesos', 2);
        $this->assertDatabaseCount('inicializaciones_sistema', 0);
    }

    public function test_validation_protects_duplicates_roles_references_and_legacy_input(): void
    {
        $root = $this->nativeUser();
        $this->actingAs($root);
        $this->post(route('usuarios.store'), $this->payload(['email' => $root->email, 'usuario' => $root->usuario]))
            ->assertSessionHasErrors(['email', 'usuario']);
        $this->post(route('usuarios.store'), $this->payload(['plaza_id' => 999, 'role_id' => 999]))
            ->assertSessionHasErrors(['plaza_id', 'role_id']);
        $role = Role::create(['name' => 'unrecognized', 'guard_name' => 'web']);
        $this->post(route('usuarios.store'), $this->payload(['role_id' => $role->id]))->assertSessionHasErrors('role_id');
        $role = Role::create(['name' => 'root', 'guard_name' => 'api']);
        $this->post(route('usuarios.store'), $this->payload(['role_id' => $role->id]))->assertSessionHasErrors('role_id');
        $this->post(route('usuarios.store'), $this->payload(['rol_id' => 3, 'Hash' => 'spoof', 'sha1_id' => 'spoof']))
            ->assertSessionHasErrors(['rol_id', 'Hash', 'sha1_id']);
        if ($this->mysql) {
            $this->post(route('usuarios.store'), $this->payload(['usuario' => strtoupper($root->usuario), 'email' => strtoupper($root->email)]))
                ->assertSessionHasErrors(['email', 'usuario']);
        }
        $this->assertSame(1, Usuario::count());
    }

    public function test_ajax_and_http_directory_filters_sort_and_pagination_preserve_the_same_scope(): void
    {
        $root = $this->nativeUser();
        for ($i = 0; $i < 101; $i++) {
            $this->nativeUser('admin', ['nombre' => sprintf('Listado %03d', $i)]);
        }
        $this->actingAs($root);
        $params = ['q' => 'Listado', 'sort' => 'name', 'direction' => 'desc', 'page' => 2];
        $url = route('usuarios.index', $params);
        $full = $this->get($url)->assertOk()->assertSee('<html', false);
        $this->assertSame(101, $full->viewData('records')->total());
        $this->assertSame('Listado 000', $full->viewData('records')->sole()->nombre);
        $this->withHeader('X-Listing-Fragment', 'listing')->get($url)->assertOk()
            ->assertHeader('X-Listing-Fragment', 'listing')->assertDontSee('<html', false)->assertSee('Listado 000')->assertSee('q=Listado', false);
        $this->assertStringContainsString('data-copy', $full->getContent());
        $this->assertStringNotContainsString('>Eliminar<', $full->getContent());
        $this->delete(route('usuarios.update', $root))->assertStatus(405);
        $this->assertSame(102, Usuario::count());
    }

    public function test_access_search_pagination_exports_and_current_role_preserve_historical_plaza(): void
    {
        $root = $this->nativeUser();
        $subject = $this->nativeUser('admin');
        Plaza::forceCreate(['id' => 7, 'nombre' => 'Plaza histórica', 'acronimo' => 'PH']);
        for ($i = 0; $i < 201; $i++) {
            Acceso::create(['fecha' => '2020-05-03', 'hora' => '08:30:00', 'usuario_id' => $subject->id, 'plaza_id' => 7]);
        }
        $subject->syncRoles(['socio']);
        $this->actingAs($root);
        $params = ['date' => '2020-05-03', 'q' => 'Socio', 'sort' => 'plaza', 'direction' => 'asc'];
        $url = route('usuarios.accesos', [...$params, 'page' => 2]);
        $response = $this->get($url)->assertOk()->assertSee('Plaza histórica');
        $this->assertSame(201, $response->viewData('records')->total());
        $this->assertSame(1, $response->viewData('records')->count());
        $this->withHeader('X-Listing-Fragment', 'listing')->get($url)->assertOk()->assertDontSee('<html', false)->assertSee('2020-05-03');
        $this->flushHeaders();
        $csv = $this->get(route('usuarios.accesos.export', [...$params, 'format' => 'csv']))->assertOk()->streamedContent();
        $this->assertSame(201, substr_count($csv, 'Socio'));
        $this->assertStringContainsString('Plaza histórica', $csv);
        $pdf = $this->get(route('usuarios.accesos.export', [...$params, 'format' => 'pdf']))->assertOk();
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->get(route('usuarios.accesos.export', [...$params, 'format' => 'print']))->assertOk()->assertSee('Plaza histórica');
        $this->assertDatabaseCount('bitacora_accesos', 201);
    }

    public function test_excel_and_pdf_directory_use_native_fields_and_keep_formula_protection(): void
    {
        $root = $this->nativeUser();
        $this->nativeUser('admin', ['nombre' => '=1+1', 'email' => 'export@example.test', 'usuario' => 'export']);
        $this->actingAs($root);
        $params = ['q' => 'export', 'sort' => 'username', 'direction' => 'desc'];
        $response = $this->get(route('usuarios.export', [...$params, 'format' => 'xlsx']))->assertOk()->assertDownload('usuarios.xlsx');
        $book = IOFactory::load($response->baseResponse->getFile()->getPathname());
        $this->assertSame('=1+1', $book->getActiveSheet()->getCell('A4')->getValue());
        $this->assertSame('s', $book->getActiveSheet()->getCell('A4')->getDataType());
        $book->disconnectWorksheets();
        $pdf = $this->get(route('usuarios.export', [...$params, 'format' => 'pdf']))->assertOk();
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->get(route('usuarios.export', [...$params, 'format' => 'print']))->assertOk()->assertSee('=1+1');
    }

    public function test_blank_password_edit_preserves_a_newer_hash_even_when_the_bound_model_is_stale(): void
    {
        $subject = $this->nativeUser('admin');
        $current = Hash::make('changed-concurrently');
        DB::table('usuarios')->where('id', $subject->id)->update(['password' => $current]);
        $data = $this->payload(['password' => '']);
        unset($data['sexo']);
        app(SaveUser::class)->handle($data, $subject);
        $this->assertSame($current, $subject->fresh()->password);
    }

    public function test_revoked_permissions_stay_revoked_during_login_and_navigation(): void
    {
        $root = $this->nativeUser();
        Role::findByName('root', 'web')->revokePermissionTo('users.directory.view');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->post('/login', ['username' => $root->usuario, 'password' => 'clave-de-prueba'])->assertRedirect(route('inicio'));
        $response = $this->get('/Inicio')->assertOk();
        $this->assertStringNotContainsString('href="'.route('usuarios.index').'"', $response->getContent());
        $this->get('/Usuarios')->assertForbidden();
        $this->assertDatabaseCount('inicializaciones_sistema', 0);
    }

    public function test_foreign_keys_prevent_destroying_users_with_historical_accesses(): void
    {
        $user = $this->nativeUser();
        Acceso::create(['fecha' => '2020-05-03', 'hora' => '08:30:00', 'usuario_id' => $user->id, 'plaza_id' => $user->plaza_id]);
        $this->expectException(QueryException::class);
        DB::table('usuarios')->where('id', $user->id)->delete();
    }

    public function test_historical_accesses_are_not_duplicated_or_hidden_by_inconsistent_current_roles(): void
    {
        $root = $this->nativeUser();
        $subject = $this->nativeUser('admin');
        Acceso::create(['fecha' => '2020-05-03', 'hora' => '08:30:00', 'usuario_id' => $subject->id, 'plaza_id' => $subject->plaza_id]);
        $subject->syncRoles([]);
        $this->actingAs($root);
        $url = route('usuarios.accesos', ['date' => '2020-05-03', 'q' => 'Sin rol único', 'sort' => 'role']);
        $this->assertSame(1, $this->get($url)->assertOk()->viewData('records')->total());
        $this->get(route('usuarios.edit', $subject))->assertOk()->assertSee('Selecciona un rol');
        $subject->assignRole(['admin', 'socio']);
        $this->assertSame(1, $this->get($url)->assertOk()->viewData('records')->total());
        $this->assertDatabaseCount('bitacora_accesos', 1);
    }

    public function test_definitive_model_factory_uses_only_the_native_schema(): void
    {
        $user = Usuario::factory()->create();
        $this->assertSame('usuarios', $user->getTable());
        $this->assertTrue(Hash::check('synthetic-password', $user->password));
        $this->assertNotNull($user->plaza);
        $this->assertFalse($user->hasAttribute('rol_id'));
    }

    private function payload(array $changes = []): array
    {
        return array_replace([
            'nombre' => 'María', 'apellido_paterno' => 'Muñoz', 'apellido_materno' => 'Pérez', 'edad' => 30, 'sexo' => 'M',
            'email' => 'new@example.test', 'usuario' => 'nueva', 'password' => 'clave-nueva', 'plaza_id' => 1, 'role_id' => 2,
        ], $changes);
    }
}
