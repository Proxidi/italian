<?php

namespace App\Services\Legacy;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Validator;

class ConfigurationImporter
{
    public const MAP = [
        'configuracion_corte_dlc' => ['source' => 'tbl_rango_archivos', 'value' => 'Numero_Dias', 'target' => 'numero_dias'],
        'configuracion_notificaciones' => ['source' => 'tbl_notificaciones', 'value' => 'Fecha', 'target' => 'fecha_inicial'],
    ];

    public function run(bool $dryRun): array
    {
        [$source, $target] = app(ImportConnections::class)->check();
        $values = $this->readSource($source);
        // Prevalidate both sections before writing either one.
        $plan = $this->plan($target, $values, false);
        if ($dryRun) {
            return $plan;
        }

        return $target->transaction(function () use ($source, $target, $values): array {
            if ($this->readSource($source) !== $values) {
                throw new ImportSafetyException('El origen cambió durante la validación; reejecute.');
            }
            $plan = $this->plan($target, $values, true);
            foreach ($plan as $table => $status) {
                if ($status === 'pendiente') {
                    // Global IDs have no external relationships. A fixed key also
                    // prevents two concurrent imports from creating duplicates.
                    $target->table($table)->insert(['id' => 1, ...$values[$table]]);
                    $plan[$table] = 'importado';
                }
            }

            return $plan;
        });
    }

    private function readSource(Connection $source): array
    {
        $result = [];
        foreach (self::MAP as $table => $map) {
            $rows = $source->table($map['source'])->select([$map['value'], 'Descripcion'])->limit(2)->get();
            if ($rows->count() !== 1) {
                throw new ImportSafetyException($map['source'].': se requiere exactamente una fila global; no se seleccionará una arbitrariamente.');
            }
            $row = $rows->first();
            $value = $row->{$map['value']};
            $rules = $table === 'configuracion_corte_dlc'
                ? ['required', 'integer', 'between:1,127']
                : ['required', 'date_format:Y-m-d', 'after_or_equal:1000-01-01', 'before_or_equal:9999-12-31'];
            if (Validator::make(['value' => $value, 'description' => $row->Descripcion], [
                'value' => $rules, 'description' => ['present', 'string', 'max:50'],
            ])->fails() || ! mb_check_encoding((string) $row->Descripcion, 'UTF-8')) {
                throw new ImportSafetyException($map['source'].': valor, fecha o descripción inválidos; revise el origen sin normalización automática.');
            }
            $result[$table] = [$map['target'] => $table === 'configuracion_corte_dlc' ? (int) $value : $value, 'descripcion' => $row->Descripcion];
        }

        return $result;
    }

    private function plan(Connection $target, array $values, bool $lock): array
    {
        $plan = [];
        foreach ($values as $table => $expected) {
            $query = $target->table($table)->orderBy('id');
            $rows = ($lock ? $query->lockForUpdate() : $query)->limit(2)->get();
            if ($rows->count() > 1) {
                throw new ImportSafetyException($table.': destino ambiguo, sin cambios.');
            }
            if ($rows->isEmpty()) {
                $plan[$table] = 'pendiente';

                continue;
            }
            foreach ($expected as $field => $value) {
                if ((string) $rows->first()->{$field} !== (string) $value) {
                    throw new ImportSafetyException($table.': conflicto con configuración existente; no se sobrescribe.');
                }
            }
            $plan[$table] = 'omitido (idéntico)';
        }

        return $plan;
    }
}
