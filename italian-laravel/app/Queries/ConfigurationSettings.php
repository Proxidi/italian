<?php

namespace App\Queries;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfigurationSettings
{
    public const SECTIONS = [
        'dates' => [
            'table' => 'tbl_rango_archivos',
            'columns' => [
                ['field' => 'Numero_Dias', 'label' => 'Días'],
                ['field' => 'Descripcion', 'label' => 'Descripción'],
            ],
        ],
        'notifications' => [
            'table' => 'tbl_notificaciones',
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
        $query = DB::table($definition['table'])->orderBy($sort, $direction);
        if ($sort !== 'id') {
            $query->orderBy('id');
        }

        if ($search !== '') {
            $literal = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';
            $query->where(function (Builder $query) use ($definition, $literal): void {
                foreach ($definition['columns'] as $column) {
                    $query->orWhereRaw($column['field']." LIKE ? ESCAPE '!'", [$literal]);
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
        $this->updateSingleton('dates', $id, ['Numero_Dias' => $days]);
    }

    public function updateNotificationDate(int $id, string $date): void
    {
        $this->updateSingleton('notifications', $id, ['Fecha' => $date]);
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
