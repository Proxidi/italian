<?php

namespace App\Actions\Products;

use App\Models\Ingredient;
use App\Models\Product;
use App\Models\ProductIngredient;
use App\Queries\ProductFormulas;
use App\Rules\ExactProductDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveFormula
{
    public function handle(Product $product, array $data, ?ProductIngredient $line = null): void
    {
        DB::transaction(function () use ($product, $data, $line): void {
            // All Laravel formula writers lock the same parent before checking duplicates.
            $parent = Product::query()->lockForUpdate()->findOrFail($product->id);
            abort_unless($parent->family()->exists(), 404);
            $current = $line ? $parent->formulas()->lockForUpdate()->findOrFail($line->id) : null;
            $ingredient = Ingredient::query()->lockForUpdate()->findOrFail($current?->insumo_id ?? $data['insumo_id']);
            if (! $ingredient->initialUnit()->exists() || (int) $ingredient->unidad_id !== (int) $data['unit_id']) {
                throw ValidationException::withMessages(['unit_id' => 'La unidad del insumo cambió o no existe. Consulta de nuevo la fórmula antes de guardar.']);
            }
            $matches = $parent->formulas()->where('insumo_id', $ingredient->id)->lockForUpdate()->get();
            if (($current && $matches->count() !== 1) || (! $current && $matches->isNotEmpty())) {
                throw ValidationException::withMessages(['insumo_id' => 'El insumo ya está asociado o existen filas duplicadas que requieren revisión.']);
            }
            if ($current) {
                $current->update(['cantidad' => ExactProductDecimal::normalize($data['cantidad'])]);
            } else {
                if (! app(ProductFormulas::class)->eligibleIngredients()->whereKey($ingredient->id)->exists()) {
                    throw ValidationException::withMessages(['insumo_id' => 'Selecciona un insumo de producción con sus catálogos completos.']);
                }
                $parent->formulas()->create(['insumo_id' => $ingredient->id, 'cantidad' => ExactProductDecimal::normalize($data['cantidad'])]);
            }
        });
    }
}
