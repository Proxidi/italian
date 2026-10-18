<?php

namespace App\Actions\Products;

use App\Models\Ingredient;
use App\Models\IngredientConversion;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateIngredientConversion
{
    public function handle(Ingredient $ingredient, IngredientConversion $conversion, array $data): void
    {
        DB::transaction(function () use ($ingredient, $conversion, $data): void {
            // Serialize Laravel writers, then recheck catalogs and conversion cardinality.
            Ingredient::query()->whereKey($ingredient->id)->withCatalogs()->select('tbl_insumos.id')->lockForUpdate()->firstOrFail();
            $rows = $ingredient->conversions()->orderBy('id')->limit(2)->lockForUpdate()->get();
            if ($rows->count() !== 1 || $rows->first()->id !== $conversion->id) {
                throw ValidationException::withMessages(['conversion' => 'No se guardó: la conversión cambió o existen varias filas para este insumo. Debe revisarse antes de editar.']);
            }
            $rows->first()->update(['Bodega' => $data['bodega'], 'Pedido' => $data['pedido']]);
        });
    }
}
