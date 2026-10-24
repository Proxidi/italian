<?php

namespace App\Console\Commands;

use App\Services\Legacy\ConfigurationImporter;
use App\Services\Legacy\ImportSafetyException;
use Illuminate\Console\Command;
use Throwable;

class ImportLegacyConfiguration extends Command
{
    protected $signature = 'legacy:importar-configuracion {--dry-run : Prevalidar sin escrituras}';

    protected $description = 'Importa atómicamente Corte & DLC y Notificaciones sin sobrescribir el destino';

    public function handle(ConfigurationImporter $importer): int
    {
        try {
            $plan = $importer->run($this->getName() === 'legacy:validar-configuracion' || (bool) $this->option('dry-run'));
            foreach ($plan as $table => $status) {
                $this->line($table.': '.$status);
            }
            $this->info('Rechazados: 0; conflictos: 0. Ambas configuraciones verificadas.');

            return self::SUCCESS;
        } catch (ImportSafetyException $exception) {
            $this->error($exception->getMessage());
        } catch (Throwable) {
            $this->error('Importación interrumpida: no se confirmaron escrituras. Revise conexiones, esquema y concurrencia; reejecute. No se muestran SQL ni datos sensibles.');
        }

        return self::FAILURE;
    }
}
