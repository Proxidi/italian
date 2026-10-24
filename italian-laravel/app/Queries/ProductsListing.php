<?php

namespace App\Queries;

use App\Models\Product;
use Illuminate\Database\Query\Builder;

class ProductsListing
{
    public const COLUMNS = [
        ['field' => 'id', 'label' => '#'], ['field' => 'Clave', 'label' => 'Clave'],
        ['field' => 'Nombre', 'label' => 'Nombre'], ['field' => 'Familia', 'label' => 'Familia'],
    ];

    private const SORTS = ['id' => 'productos.id', 'Clave' => 'productos.clave', 'Nombre' => 'productos.nombre', 'Familia' => 'family.nombre'];

    public function query(array $filters): Builder
    {
        $query = Product::query()->join('familias_productos as family', 'family.id', '=', 'productos.familia_id')
            ->select(['productos.id', 'productos.clave as Clave', 'productos.nombre as Nombre', 'productos.estado as Estado', 'family.nombre as Familia'])->toBase();
        if (filled($filters['family'] ?? null)) {
            $query->where('productos.familia_id', $filters['family']);
        }
        if (filled($search = $filters['q'] ?? null)) {
            $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';
            $query->where(function (Builder $query) use ($pattern): void {
                foreach (self::SORTS as $column) {
                    $query->orWhereRaw($column." LIKE ? ESCAPE '!'", [$pattern]);
                }
            });
        }

        return $query->orderBy(self::SORTS[$filters['sort'] ?? 'id'], $filters['direction'] ?? 'asc')->orderBy('productos.id');
    }
}
