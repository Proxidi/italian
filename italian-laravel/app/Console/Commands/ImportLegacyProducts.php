<?php

namespace App\Console\Commands;

use App\Services\Legacy\ImportSafetyException;
use App\Services\Legacy\ProductsImporter;
use Illuminate\Console\Command;

class ImportLegacyProducts extends Command
{
    protected $signature = 'legacy:importar-productos {--dry-run : Validación estrictamente sin escrituras} {--lote=500 : Filas por INSERT, 1–2000}';

    protected $description = 'Importa catálogos, insumos, conversiones, productos y fórmulas como un conjunto atómico';

    public function handle(ProductsImporter $importer): int
    {
        try {
            $batch = $this->getName() === 'legacy:validar-productos' ? '500' : (string) $this->option('lote');
            if (! ctype_digit($batch)) {
                throw new ImportSafetyException('--lote debe ser entero.');
            }
            $reports = $importer->run($this->getName() === 'legacy:validar-productos' || (bool) $this->option('dry-run'), (int) $batch);
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
            $this->error('Importación interrumpida. El catálogo se revierte si no se confirmó; la reserva técnica de IDs puede permanecer. Reejecute con el mismo origen para verificar datos y secuencias. No se muestran SQL ni datos sensibles.');
        }

        return self::FAILURE;
    }
}
