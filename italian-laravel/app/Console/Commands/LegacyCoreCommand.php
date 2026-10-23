<?php

namespace App\Console\Commands;

use App\Services\Legacy\CoreBootstrap;
use App\Services\Legacy\CoreImporter;
use App\Services\Legacy\ImportConnections;
use App\Services\Legacy\ImportReport;
use App\Services\Legacy\ImportSafetyException;
use Illuminate\Console\Command;
use Throwable;

abstract class LegacyCoreCommand extends Command
{
    public function handle(CoreImporter $importer, CoreBootstrap $bootstrap): int
    {
        try {
            [$source, $target] = app(ImportConnections::class)->check();
            $this->info('Conexiones verificadas: legacy='.$source->getDatabaseName().'; mysql='.$target->getDatabaseName());
            $batch = $this->hasOption('lote') ? (string) $this->option('lote') : '500';
            if (! ctype_digit($batch) || (int) $batch < 1 || (int) $batch > 2000) {
                throw new ImportSafetyException('--lote debe ser un entero entre 1 y 2000.');
            }
            if ($this->getName() === 'legacy:inicializar-permisos') {
                $status = $bootstrap->run((bool) $this->option('dry-run'));
                $this->info('Bootstrap Spatie: '.$status.'. 5 roles / 22 permisos; las asignaciones posteriores se conservan.');

                return self::SUCCESS;
            }
            $validation = $this->getName() === 'legacy:validar-nucleo';
            if ($validation) {
                $this->info('Prevalidación sin escrituras. Las dependencias se evalúan en el orden de importación.');
                $this->info('Bootstrap: '.$bootstrap->run(true));
            }
            $entities = $validation ? ['plazas', 'usuarios', 'accesos'] : [substr($this->getName(), strlen('legacy:importar-'))];
            foreach ($entities as $entity) {
                $report = $importer->run($entity, $validation || (bool) $this->option('dry-run'), (int) $batch, $validation);
                $this->renderReport($entity, $report);
                if ($report->hasProblems()) {
                    $this->error('No se escribió este conjunto. Corrija/revise los conflictos y reejecute; no se evaluarán dependencias posteriores.');

                    return self::FAILURE;
                }
            }
            if ($validation) {
                $importer->validateTargetIntegrity();
                $this->info('Integridad global del destino: correcta.');
            }

            return self::SUCCESS;
        } catch (ImportSafetyException $e) {
            $this->error($e->getMessage());
        } catch (Throwable) {
            // QueryException messages can include credentials, hashes, names or
            // entire INSERT bindings. Never print/log the original exception.
            $this->error('Operación interrumpida: compruebe conexiones, grants, esquema y cambios concurrentes. El lote activo se revierte; los lotes confirmados se conservan y puede reejecutar. No se muestran SQL ni datos sensibles.');
        }

        return self::FAILURE;
    }

    private function renderReport(string $entity, ImportReport $report): void
    {
        $this->info($entity.': origen='.$report->sourceCount.'; destino='.$report->targetCount);
        $this->table(array_keys($report->counts), [array_values($report->counts)]);
        if ($report->samples) {
            $this->table(['ID', 'Motivo (máximo 25 muestras)'], $report->samples);
        }
        $this->line('AUTO_INCREMENT: '.json_encode($report->sequence));
    }
}
