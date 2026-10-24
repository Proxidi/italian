<?php

namespace App\Queries;

use App\Models\Ingredient;
use Illuminate\Database\Query\Builder;

class IngredientsListing
{
    public const COLUMNS = [
        ['field' => 'id', 'label' => '#'],
        ['field' => 'Nombre', 'label' => 'Nombre'],
        ['field' => 'clave_pedido', 'label' => 'Clave Ped'],
        ['field' => 'initial_unit_name', 'label' => 'U Ini'],
        ['field' => 'final_unit_name', 'label' => 'U Fin'],
        ['field' => 'order_unit_name', 'label' => 'U Ped'],
    ];

    private const SORTS = [
        'id' => 'insumos.id', 'Nombre' => 'insumos.nombre', 'clave_pedido' => 'insumos.clave_pedido',
        'initial_unit_name' => 'initial_unit.nombre', 'final_unit_name' => 'final_unit.nombre', 'order_unit_name' => 'order_unit.nombre',
    ];

    public function query(array $filters): Builder
    {
        $query = Ingredient::query()->withCatalogs()->select([
            'insumos.id', 'insumos.nombre as Nombre', 'insumos.clave_pedido',
            'initial_unit.nombre as initial_unit_name', 'final_unit.nombre as final_unit_name', 'order_unit.nombre as order_unit_name',
        ])->toBase();
        if (filled($search = $filters['q'] ?? null)) {
            $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';
            $query->where(function (Builder $query) use ($pattern): void {
                foreach (self::SORTS as $column) {
                    $query->orWhereRaw($column." LIKE ? ESCAPE '!'", [$pattern]);
                }
            });
        }

        return $query->orderBy(self::SORTS[$filters['sort'] ?? 'id'], $filters['direction'] ?? 'asc')->orderBy('insumos.id');
    }
}
