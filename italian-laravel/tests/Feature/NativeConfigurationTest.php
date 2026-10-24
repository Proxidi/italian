<?php

namespace Tests\Feature;

use App\Queries\ConfigurationSettings;
use Illuminate\Support\Facades\DB;
use Tests\Support\CoreFunctionalTestCase;

class NativeConfigurationTest extends CoreFunctionalTestCase
{
    public function test_native_configuration_persists_without_legacy_and_preserves_contracts(): void
    {
        DB::table('configuracion_corte_dlc')->insert(['id' => 1, 'numero_dias' => 4, 'descripcion' => 'Corte']);
        DB::table('configuracion_notificaciones')->insert(['id' => 1, 'fecha_inicial' => '2019-01-01', 'descripcion' => 'Avisos']);
        $this->actingAs($this->nativeUser('root'), 'web');
        $this->get(route('configuration.dates'))->assertOk()->assertSee('Corte');
        $this->get(route('configuration.notifications'))->assertOk()->assertSee('2019-01-01');
        $this->put(route('configuration.dates.update'), ['setting_id' => 1, 'numero_dias_valor' => 127])->assertRedirect();
        $this->put(route('configuration.notifications.update'), ['setting_id' => 1, 'fecha' => '2024-02-29'])->assertRedirect();
        $settings = app(ConfigurationSettings::class);
        $this->assertSame(127, $settings->uploadWindowDays());
        $this->assertSame('2024-02-29', $settings->notificationStartDate());
        $this->put(route('configuration.dates.update'), ['setting_id' => 1, 'numero_dias_valor' => 128])->assertSessionHasErrors();
        $this->put(route('configuration.notifications.update'), ['setting_id' => 1, 'fecha' => '2025-02-29'])->assertSessionHasErrors();
        $this->assertSame(127, $settings->uploadWindowDays());
        DB::table('configuracion_corte_dlc')->insert(['id' => 2, 'numero_dias' => 1, 'descripcion' => 'Ambiguo']);
        $this->put(route('configuration.dates.update'), ['setting_id' => 1, 'numero_dias_valor' => 2])->assertSessionHasErrors('configuration');
        $this->assertDatabaseHas('configuracion_corte_dlc', ['id' => 1, 'numero_dias' => 127]);
    }

    public function test_csrf_is_required_for_configuration_writes(): void
    {
        $this->actingAs($this->nativeUser('root'), 'web');
        $this->app->detectEnvironment(fn () => 'local');
        $this->put(route('configuration.dates.update'), ['setting_id' => 1, 'numero_dias_valor' => 5])->assertStatus(419);
    }
}
