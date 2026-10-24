<?php

namespace App\Queries;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfigurationSettings
{
    public const SECTIONS = [
        'dates' => [
            'table' => 'configuracion_corte_dlc',
            'mapping' => ['id' => 'id', 'Numero_Dias' => 'numero_dias', 'Descripcion' => 'descripcion'],
            'columns' => [
                ['field' => 'Numero_Dias', 'label' => 'Días'],
                ['field' => 'Descripcion', 'label' => 'Descripción'],
            ],
        ],
        'notifications' => [
            'table' => 'configuracion_notificaciones',
            'mapping' => ['id' => 'id', 'Fecha' => 'fecha_inicial', 'Descripcion' => 'descripcion'],
            'columns' => [
                ['field' => 'id', 'label' => '#'],
                ['field' => 'Fecha', 'label' => 'Fecha Inicio'],
                ['field' => 'Descripcion', 'label' => 'Descripción'],
            ],
        ],
    ];

    public function query(string $section, string $search = '', string $sort = 'id', string $direction = 'asc'): Builder
    {
        $definition = self::SECTIONS[$section];
        $allowed = [...array_column($definition['columns'], 'field'), 'id'];
        abort_unless(in_array($sort, $allowed, true) && in_array($direction, ['asc', 'desc'], true), 422);
        // Keep the existing HTTP/export fields without legacy physical columns.
        $query = DB::table($definition['table'])->select(array_map(
            fn ($alias, $column) => $column.' as '.$alias,
            array_keys($definition['mapping']), array_values($definition['mapping'])
        ))->orderBy($definition['mapping'][$sort], $direction);
        if ($sort !== 'id') {
            $query->orderBy('id');
        }

        if ($search !== '') {
            $literal = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';
            $query->where(function (Builder $query) use ($definition, $literal): void {
                foreach ($definition['columns'] as $column) {
                    $query->orWhereRaw($definition['mapping'][$column['field']]." LIKE ? ESCAPE '!'", [$literal]);
                }
            });
        }

        return $query;
    }

    public function singleton(string $section): ?object
    {
        $rows = $this->query($section)->limit(2)->get();

        return $rows->count() === 1 ? $rows->first() : null;
    }

    public function updateDays(int $id, int $days): void
    {
        if ($days < 1 || $days > 127) {
            throw ValidationException::withMessages(['numero_dias_valor' => 'Selecciona un número de días entre 1 y 127.']);
        }
        $this->updateSingleton('dates', $id, ['numero_dias' => $days]);
    }

    public function updateNotificationDate(int $id, string $date): void
    {
        validator(['fecha' => $date], ['fecha' => ['required', 'date_format:Y-m-d', 'after_or_equal:1000-01-01', 'before_or_equal:9999-12-31']])->validate();
        $this->updateSingleton('notifications', $id, ['fecha_inicial' => $date]);
    }

    /** Future consumers must fail explicitly on missing/ambiguous configuration. */
    public function uploadWindowDays(): int
    {
        $row = $this->singleton('dates');
        if ($row === null || $row->Numero_Dias < 1 || $row->Numero_Dias > 127) {
            throw ValidationException::withMessages(['configuration' => 'Configuración global de Corte & DLC inválida.']);
        }

        return (int) $row->Numero_Dias;
    }

    public function notificationStartDate(): string
    {
        $row = $this->singleton('notifications');
        if ($row === null) {
            throw ValidationException::withMessages(['configuration' => 'Configuración global de Notificaciones ausente o ambigua.']);
        }
        validator(['fecha' => $row->Fecha], ['fecha' => ['required', 'date_format:Y-m-d', 'after_or_equal:1000-01-01', 'before_or_equal:9999-12-31']])->validate();

        return $row->Fecha;
    }

    private function updateSingleton(string $section, int $id, array $values): void
    {
        DB::transaction(function () use ($section, $id, $values): void {
            // Never infer an all-row update from the legacy's unqualified UPDATE.
            // Check the complete configuration, independently of list filters.
            $rows = $this->query($section)->lockForUpdate()->limit(2)->get();

            if ($rows->count() !== 1 || (int) $rows->first()->id !== $id) {
                throw ValidationException::withMessages([
                    'configuration' => 'No se guardaron cambios. Debe existir un único registro de configuración; revisa el estado de los datos con el equipo.',
                ]);
            }

            DB::table(self::SECTIONS[$section]['table'])->where('id', $id)->update($values);
        });
    }
}
