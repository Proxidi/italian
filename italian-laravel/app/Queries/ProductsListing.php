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

    private const SORTS = ['id' => 'tbl_productos.id', 'Clave' => 'tbl_productos.Clave', 'Nombre' => 'tbl_productos.Nombre', 'Familia' => 'family.Nombre'];

    public function query(array $filters): Builder
    {
        $query = Product::query()->join('tbl_familia_productos as family', 'family.id', '=', 'tbl_productos.familia_id')
            ->select(['tbl_productos.*', 'family.Nombre as Familia'])->toBase();
        if (filled($filters['family'] ?? null)) {
            $query->where('tbl_productos.familia_id', $filters['family']);
        }
        if (filled($search = $filters['q'] ?? null)) {
            $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';
            $query->where(function (Builder $query) use ($pattern): void {
                foreach (self::SORTS as $column) {
                    $query->orWhereRaw($column." LIKE ? ESCAPE '!'", [$pattern]);
                }
            });
        }

        return $query->orderBy(self::SORTS[$filters['sort'] ?? 'id'], $filters['direction'] ?? 'asc')->orderBy('tbl_productos.id');
    }
}
