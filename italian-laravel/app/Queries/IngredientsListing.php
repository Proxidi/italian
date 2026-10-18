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
        'id' => 'tbl_insumos.id', 'Nombre' => 'tbl_insumos.Nombre', 'clave_pedido' => 'tbl_insumos.clave_pedido',
        'initial_unit_name' => 'initial_unit.Nombre', 'final_unit_name' => 'final_unit.Nombre', 'order_unit_name' => 'order_unit.Nombre',
    ];

    public function query(array $filters): Builder
    {
        $query = Ingredient::query()->withCatalogs()->select([
            'tbl_insumos.id', 'tbl_insumos.Nombre', 'tbl_insumos.clave_pedido',
            'initial_unit.Nombre as initial_unit_name', 'final_unit.Nombre as final_unit_name', 'order_unit.Nombre as order_unit_name',
        ])->toBase();
        if (filled($search = $filters['q'] ?? null)) {
            $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';
            $query->where(function (Builder $query) use ($pattern): void {
                foreach (self::SORTS as $column) {
                    $query->orWhereRaw($column." LIKE ? ESCAPE '!'", [$pattern]);
                }
            });
        }

        return $query->orderBy(self::SORTS[$filters['sort'] ?? 'id'], $filters['direction'] ?? 'asc')->orderBy('tbl_insumos.id');
    }
}
