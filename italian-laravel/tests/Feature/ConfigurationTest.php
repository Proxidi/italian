<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Database\Seeders\ConfigurationPermissionsSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\NativeCoreFixtures;
use Tests\TestCase;

class ConfigurationTest extends TestCase
{
    use NativeCoreFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        // No migrations or shared MySQL connection: synthetic SQLite only.
        config([
            'database.default' => 'configuration_tests',
            'database.connections.configuration_tests' => [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
            ],
            'cache.default' => 'array', 'permission.cache.store' => 'array', 'session.driver' => 'array',
            'excel.temporary_files.local_path' => sys_get_temp_dir(),
        ]);
        DB::purge('configuration_tests');
        $this->app->forgetInstance(PermissionRegistrar::class);
        $this->withoutVite();

        Schema::create('tbl_plazas', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('Nombre');
        });
        $this->createNativeCoreSchema();
        Schema::create('tbl_rango_archivos', function (Blueprint $table): void {
            $table->tinyIncrements('id');
            $table->tinyInteger('Numero_Dias');
            $table->string('Descripcion', 50);
        });
        Schema::create('tbl_notificaciones', function (Blueprint $table): void {
            $table->tinyIncrements('id');
            $table->date('Fecha');
            $table->string('Descripcion', 50);
        });
        foreach (['roles', 'permissions'] as $name) {
            Schema::create($name, function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
                $table->unique(['name', 'guard_name']);
            });
        }
        foreach (['model_has_roles' => 'role_id', 'model_has_permissions' => 'permission_id'] as $name => $column) {
            Schema::create($name, function (Blueprint $table) use ($column): void {
                $table->unsignedBigInteger($column);
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->primary([$column, 'model_type', 'model_id']);
            });
        }
        Schema::create('role_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });

        DB::table('tbl_plazas')->insert(['id' => 1, 'Nombre' => 'Plaza sintética']);
        DB::table('tbl_rango_archivos')->insert(['id' => 1, 'Numero_Dias' => 1, 'Descripcion' => 'Rango de Fechas para DLC & Corte atrasados']);
        DB::table('tbl_notificaciones')->insert(['id' => 1, 'Fecha' => '2019-01-01', 'Descripcion' => 'Sistema de Notificaciones Corte y DLC']);
        foreach (['superadmin', 'admin', 'root', 'submanager', 'socio'] as $role) {
            Role::findOrCreate($role, 'web');
        }
        $this->seed(ConfigurationPermissionsSeeder::class);
    }

    protected function tearDown(): void
    {
        DB::purge('configuration_tests');
        parent::tearDown();
    }

    protected function actor(string $role = 'root'): Usuario
    {
        return $this->nativeUser($role);
    }

    private function snapshot(): array
    {
        return [
            DB::table('tbl_rango_archivos')->orderBy('id')->get()->toJson(),
            DB::table('tbl_notificaciones')->orderBy('id')->get()->toJson(),
        ];
    }

    public function test_upload_window_success_uses_the_legacy_timed_animation(): void
    {
        $this->actingAs($this->actor(), 'web')->put(route('configuration.dates.update'), [
            'setting_id' => 1, 'numero_dias_valor' => 5,
        ])->assertRedirect(route('configuration.dates'));
        $this->withCookie(config('session.cookie'), session()->getId())->get(route('configuration.dates'))
            ->assertOk()->assertSee('data-feedback-dialog', false)->assertSee('data-duration="1500"', false)
            ->assertSee('ui-feedback-dialog--corner', false)->assertDontSee('data-redirect-url', false);
    }

    public function test_notification_date_success_does_not_invent_a_legacy_animation(): void
    {
        $this->actingAs($this->actor(), 'web')->put(route('configuration.notifications.update'), [
            'setting_id' => 1, 'fecha' => '2026-10-18',
        ])->assertRedirect(route('configuration.notifications'));
        $this->withCookie(config('session.cookie'), session()->getId())->get(route('configuration.notifications'))
            ->assertOk()->assertSee('Fecha de notificaciones actualizada.')
            ->assertSee('data-feedback', false)->assertDontSee('data-feedback-dialog', false)->assertDontSee('data-duration', false);
    }

    public function test_guests_cannot_read_export_or_update(): void
    {
        foreach (['configuration.index', 'configuration.dates', 'configuration.notifications', 'configuration.dates.export', 'configuration.notifications.export'] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
        $before = $this->snapshot();
        $this->put(route('configuration.dates.update'), [])->assertRedirect(route('login'));
        $this->put(route('configuration.notifications.update'), [])->assertRedirect(route('login'));
        $this->assertSame($before, $this->snapshot());
    }

    public static function roles(): array
    {
        return array_map(fn ($role) => [$role], ['superadmin', 'admin', 'root', 'submanager', 'socio']);
    }

    #[DataProvider('roles')]
    public function test_permissions_protect_every_screen_export_and_write(string $role): void
    {
        $this->actingAs($this->actor($role), 'web');
        $status = $role === 'root' ? 200 : 403;
        $before = $this->snapshot();
        foreach (['configuration.index', 'configuration.dates', 'configuration.notifications'] as $route) {
            $this->get(route($route))->assertStatus($status);
        }
        foreach (['configuration.dates.export', 'configuration.notifications.export'] as $route) {
            foreach (['xlsx', 'pdf'] as $format) {
                $response = $this->get(route($route, ['format' => $format]))->assertStatus($status);
                if ($role === 'root' && $format === 'xlsx') {
                    unlink($response->baseResponse->getFile()->getPathname());
                }
            }
        }
        if ($role !== 'root') {
            $this->put(route('configuration.dates.update'), ['setting_id' => 1, 'numero_dias_valor' => 5])->assertForbidden();
            $this->put(route('configuration.notifications.update'), ['setting_id' => 1, 'fecha' => '2026-10-17'])->assertForbidden();
        }
        $this->assertSame($before, $this->snapshot());
    }

    public function test_panel_navigation_and_legacy_fields_are_available_without_writes(): void
    {
        $this->actingAs($this->actor(), 'web');
        $before = $this->snapshot();
        $this->get(route('inicio'))->assertOk()->assertSee(route('configuration.index'), false);
        $this->get(route('configuration.index'))->assertOk()->assertSee('CORTE &amp; DLC', false)
            ->assertSee(route('configuration.dates'), false)->assertSee(route('configuration.notifications'), false);
        $this->get(route('configuration.dates'))->assertOk()->assertSee('Días')->assertSee('Descripción')
            ->assertSee('data-open-days', false)->assertSee('name="_token"', false)->assertSee('value="PUT"', false);
        $this->get(route('configuration.notifications'))->assertOk()->assertSee('Fecha Inicio')->assertSee('2019-01-01')
            ->assertSee('Sistema de Notificaciones Corte y DLC');
        $this->assertSame($before, $this->snapshot());
    }

    public function test_view_permission_does_not_allow_writes_and_revocation_hides_navigation(): void
    {
        $role = Role::findByName('root', 'web');
        $role->revokePermissionTo(['configuration.upload-window.update', 'configuration.notifications.update']);
        $actor = $this->actor();
        $this->actingAs($actor, 'web');
        $before = $this->snapshot();
        $this->get(route('configuration.dates'))->assertOk()->assertDontSee('data-days-dialog', false);
        $this->get(route('configuration.notifications'))->assertOk()->assertSee('disabled', false);
        $this->put(route('configuration.dates.update'), [])->assertForbidden();
        $this->put(route('configuration.notifications.update'), [])->assertForbidden();
        $this->assertSame($before, $this->snapshot());
        $role->revokePermissionTo('configuration.notifications.view');
        $actor->unsetRelation('roles');
        $this->get(route('configuration.index'))->assertOk()->assertDontSee('Editar Notificaciones');
        $this->get(route('configuration.notifications'))->assertForbidden();
    }

    public function test_days_update_changes_only_the_existing_value_and_accepts_storage_boundaries(): void
    {
        $this->actingAs($this->actor(), 'web');
        foreach ([127, 1] as $days) {
            $this->put(route('configuration.dates.update'), [
                'setting_id' => 1, 'numero_dias_valor' => $days, 'Descripcion' => 'No modificar', 'Fecha' => '2000-01-01',
            ])->assertRedirect(route('configuration.dates'))->assertSessionHas('configuration-success');
            $this->assertDatabaseHas('tbl_rango_archivos', ['id' => 1, 'Numero_Dias' => $days, 'Descripcion' => 'Rango de Fechas para DLC & Corte atrasados']);
        }
        $this->assertDatabaseCount('tbl_rango_archivos', 1);
        $this->assertDatabaseHas('tbl_notificaciones', ['Fecha' => '2019-01-01']);
    }

    public function test_update_permission_alone_does_not_allow_access_without_subpage_read_permission(): void
    {
        Role::findByName('root', 'web')->revokePermissionTo([
            'configuration.upload-window.view', 'configuration.notifications.view',
        ]);
        $this->actingAs($this->actor(), 'web');
        $before = $this->snapshot();
        $this->get(route('configuration.dates'))->assertForbidden();
        $this->get(route('configuration.notifications'))->assertForbidden();
        $this->put(route('configuration.dates.update'), ['setting_id' => 1, 'numero_dias_valor' => 5])->assertForbidden();
        $this->put(route('configuration.notifications.update'), ['setting_id' => 1, 'fecha' => '2026-10-17'])->assertForbidden();
        $this->assertSame($before, $this->snapshot());
    }

    public function test_invalid_days_never_write_or_flash_success(): void
    {
        $this->actingAs($this->actor(), 'web');
        $before = $this->snapshot();
        foreach ([null, '', 0, -1, 128, '2.5', 'abc'] as $days) {
            $this->from(route('configuration.dates'))->put(route('configuration.dates.update'), [
                'setting_id' => 1, 'numero_dias_valor' => $days,
            ])->assertSessionHasErrors('numero_dias_valor')->assertSessionMissing('configuration-success');
            $this->assertSame($before, $this->snapshot());
        }
        $this->withCookie(config('session.cookie'), session()->getId())->get(route('configuration.dates'))
            ->assertOk()->assertSee('data-reopen', false)->assertDontSee('data-configuration-success', false);
    }

    public function test_notification_update_preserves_description_and_accepts_historical_and_future_dates(): void
    {
        $this->actingAs($this->actor(), 'web');
        foreach (['1000-01-01', '2024-02-29', '9999-12-31'] as $date) {
            $this->put(route('configuration.notifications.update'), ['setting_id' => 1, 'fecha' => $date, 'Descripcion' => 'No cambiar'])
                ->assertRedirect(route('configuration.notifications'))->assertSessionHas('configuration-success');
            $this->assertDatabaseHas('tbl_notificaciones', ['id' => 1, 'Fecha' => $date, 'Descripcion' => 'Sistema de Notificaciones Corte y DLC']);
        }
        $this->assertDatabaseCount('tbl_notificaciones', 1);
        $this->assertDatabaseHas('tbl_rango_archivos', ['Numero_Dias' => 1]);
    }

    public function test_invalid_notification_dates_never_write(): void
    {
        $this->actingAs($this->actor(), 'web');
        $before = $this->snapshot();
        foreach (['', '2025-02-29', '2026-02-30', '17/10/2026', '0000-00-00', '0999-12-31', 'tomorrow', '2026-10-17 10:00:00'] as $date) {
            $this->put(route('configuration.notifications.update'), ['setting_id' => 1, 'fecha' => $date])
                ->assertSessionHasErrors('fecha')->assertSessionMissing('configuration-success');
            $this->assertSame($before, $this->snapshot());
        }
    }

    public static function sections(): array
    {
        return [['dates', 'tbl_rango_archivos', ['Numero_Dias' => 5, 'Descripcion' => 'Otro rango'], ['numero_dias_valor' => 10]],
            ['notifications', 'tbl_notificaciones', ['Fecha' => '2020-01-01', 'Descripcion' => 'Otro aviso'], ['fecha' => '2026-10-17']]];
    }

    #[DataProvider('sections')]
    public function test_missing_ambiguous_or_stale_configuration_cannot_cause_mass_updates(string $section, string $table, array $extra, array $payload): void
    {
        $this->actingAs($this->actor(), 'web');
        $before = $this->snapshot();
        $this->put(route('configuration.'.$section.'.update'), ['setting_id' => 99, ...$payload])->assertSessionHasErrors('configuration');
        $this->assertSame($before, $this->snapshot());
        DB::table($table)->insert(['id' => 2, ...$extra]);
        $before = $this->snapshot();
        $this->get(route('configuration.'.$section, ['q' => 'Otro']))->assertOk()
            ->assertSee('debe existir un único registro')->assertViewHas('singleton', null);
        $this->put(route('configuration.'.$section.'.update'), ['setting_id' => 1, ...$payload])
            ->assertSessionHasErrors('configuration')->assertSessionMissing('configuration-success');
        $this->assertSame($before, $this->snapshot());
        DB::table($table)->delete();
        $this->get(route('configuration.'.$section))->assertOk()->assertSee('debe existir un único registro');
        $this->put(route('configuration.'.$section.'.update'), ['setting_id' => 1, ...$payload])->assertSessionHasErrors('configuration');
        $this->assertDatabaseCount($table, 0);
        // Replacing the singleton after a form was opened must not target the replacement.
        DB::table($table)->insert(['id' => 3, ...$extra]);
        $before = $this->snapshot();
        $this->put(route('configuration.'.$section.'.update'), ['setting_id' => 1, ...$payload])->assertSessionHasErrors('configuration');
        $this->assertSame($before, $this->snapshot());
    }

    public function test_filtering_is_literal_and_does_not_hide_singleton_configuration(): void
    {
        $this->actingAs($this->actor(), 'web');
        foreach (['%', '_', '!', "' OR 1=1 --", 'no coincide'] as $search) {
            $this->get(route('configuration.dates', ['q' => $search]))->assertOk()
                ->assertViewHas('records', fn ($records) => $records->total() === 0)
                ->assertViewHas('singleton', fn ($singleton) => $singleton->id === 1);
        }
        $this->get(route('configuration.dates', ['q' => 'Corte']))->assertOk()
            ->assertViewHas('records', fn ($records) => $records->total() === 1);
    }

    public function test_exports_keep_all_filtered_rows_order_headers_and_formula_safety(): void
    {
        $this->actingAs($this->actor(), 'web');
        DB::table('tbl_notificaciones')->insert([
            ['id' => 2, 'Fecha' => '2026-10-17', 'Descripcion' => '=FORMULA'],
            ['id' => 3, 'Fecha' => '2026-10-17', 'Descripcion' => '<script>literal</script>'],
        ]);
        $before = $this->snapshot();
        $response = $this->get(route('configuration.notifications.export', [
            'format' => 'xlsx', 'q' => '2026-10-17', 'sort' => 'id', 'direction' => 'desc', 'page' => 2,
        ]))->assertOk()->assertDownload('notificaciones.xlsx');
        $path = $response->baseResponse->getFile()->getPathname();
        try {
            $book = IOFactory::load($path);
            $sheet = $book->getActiveSheet();
            $this->assertSame('Datos', $sheet->getCell('A1')->getValue());
            $this->assertSame('Fecha Inicio', $sheet->getCell('B3')->getValue());
            $this->assertSame('3', $sheet->getCell('A4')->getValue());
            $this->assertSame('=FORMULA', $sheet->getCell('C5')->getValue());
            $this->assertSame('s', $sheet->getCell('C5')->getDataType());
            $this->assertSame(5, $sheet->getHighestRow());
            $book->disconnectWorksheets();
        } finally {
            unlink($path);
        }
        $pdf = $this->get(route('configuration.notifications.export', ['format' => 'pdf', 'q' => '2026-10-17']))
            ->assertOk()->assertDownload('notificaciones.pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->assertSame($before, $this->snapshot());
        $this->get(route('configuration.notifications'))->assertOk()->assertSee('&lt;script&gt;literal&lt;/script&gt;', false);
    }

    public function test_read_inputs_and_missing_ids_are_validated(): void
    {
        $this->actingAs($this->actor(), 'web');
        $this->get(route('configuration.dates', ['q' => str_repeat('x', 101), 'sort' => 'Password', 'direction' => 'DROP', 'page' => 0]))
            ->assertSessionHasErrors(['q', 'sort', 'direction', 'page']);
        $this->get(route('configuration.dates.export', ['format' => 'exe']))->assertSessionHasErrors('format');
        $this->put(route('configuration.dates.update'), ['numero_dias_valor' => 10])->assertSessionHasErrors('setting_id');
        $this->put(route('configuration.notifications.update'), ['fecha' => '2026-10-17'])->assertSessionHasErrors('setting_id');
    }

    public function test_read_pagination_keeps_80_rows_and_export_is_not_limited_to_the_page(): void
    {
        $this->actingAs($this->actor(), 'web');
        for ($id = 2; $id <= 85; $id++) {
            DB::table('tbl_notificaciones')->insert(['id' => $id, 'Fecha' => '2026-10-17', 'Descripcion' => 'Aviso '.$id]);
        }
        $before = $this->snapshot();
        $this->get(route('configuration.notifications', ['page' => 2, 'sort' => 'id', 'direction' => 'asc']))
            ->assertOk()->assertViewHas('records', fn ($records) => $records->total() === 85
                && $records->perPage() === 80 && $records->count() === 5 && $records->first()->id === 81);
        $response = $this->get(route('configuration.notifications.export', ['format' => 'xlsx', 'page' => 2]))->assertOk();
        $path = $response->baseResponse->getFile()->getPathname();
        try {
            $book = IOFactory::load($path);
            $this->assertSame(88, $book->getActiveSheet()->getHighestRow());
            $book->disconnectWorksheets();
        } finally {
            unlink($path);
        }
        $this->assertSame($before, $this->snapshot());
    }

    public function test_permission_seeder_is_idempotent_and_preserves_other_and_direct_permissions(): void
    {
        $other = Permission::findOrCreate('other-module.view', 'web');
        $admin = Role::findByName('admin', 'web');
        $admin->givePermissionTo([$other, 'configuration.view']);
        $actor = $this->actor('admin');
        $actor->givePermissionTo('configuration.notifications.view');
        $this->seed(ConfigurationPermissionsSeeder::class);
        $this->seed(ConfigurationPermissionsSeeder::class);
        $this->assertTrue($admin->fresh()->hasPermissionTo('other-module.view'));
        $this->assertTrue($admin->fresh()->hasPermissionTo('configuration.view'));
        $this->assertTrue($actor->fresh()->hasDirectPermission('configuration.notifications.view'));
        $this->assertDatabaseCount('permissions', 6);
        $this->assertDatabaseCount('role_has_permissions', 7);
    }
}
