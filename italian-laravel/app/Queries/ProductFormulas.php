<?php

namespace App\Queries;

use App\Models\Ingredient;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProductFormulas
{
    // Preserve the legacy picker: production ingredients with category and final unit.
    // Also require the initial unit actually displayed by the formula editor.
    public function eligibleIngredients(): Builder
    {
        return Ingredient::query()->where('inv_produccion', 1)->whereHas('category')->whereHas('finalUnit')->whereHas('initialUnit');
    }

    public function lines(Product $product): Collection
    {
        $lines = $product->formulas()->with('ingredient.initialUnit')->orderBy('id')->get();
        $counts = $lines->countBy('insumo_id');
        foreach ($lines as $line) {
            $line->setAttribute('editable', $line->ingredient?->initialUnit !== null && $counts[$line->insumo_id] === 1);
        }

        return $lines;
    }
}
