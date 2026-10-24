<?php

namespace App\Console\Commands;

use App\Services\Legacy\ImportSafetyException;
use App\Services\Legacy\RemittancesImporter;
use Illuminate\Console\Command;

class ImportLegacyRemittances extends Command
{
    protected $signature = 'legacy:importar-remesas {--dry-run : Validar sin escrituras} {--lote=500 : Filas por lote, 1–2000}';

    protected $description = 'Importa historial de Gastos y catálogos necesarios para Costo Remesas, sin habilitar captura';

    public function handle(RemittancesImporter $importer): int
    {
        try {
            $validate = $this->getName() === 'legacy:validar-remesas';
            $batch = $validate ? '500' : (string) $this->option('lote');
            if (! ctype_digit($batch)) {
                throw new ImportSafetyException('--lote debe ser entero.');
            }
            $reports = $importer->run($validate || (bool) $this->option('dry-run'), (int) $batch);
            $failed = false;
            foreach ($reports as $table => $report) {
                $this->info($table.': origen='.$report->sourceCount.'; destino='.$report->targetCount);
                $this->table(array_keys($report->counts), [array_values($report->counts)]);
                $this->line('AUTO_INCREMENT: '.json_encode($report->sequence));
                if ($report->samples) {
                    $this->table(['ID', 'Motivo (máximo 25)'], $report->samples);
                }
                $failed = $failed || $report->hasProblems();
            }

            return $failed ? self::FAILURE : self::SUCCESS;
        } catch (ImportSafetyException $e) {
            $this->error($e->getMessage());
        } catch (\Throwable) {
            $this->error('Importación interrumpida. La transacción revierte el historial no confirmado; la reserva de IDs puede permanecer. Reejecute con el mismo origen para verificar filas y secuencias. No se muestran SQL ni datos sensibles.');
        }

        return self::FAILURE;
    }
}
