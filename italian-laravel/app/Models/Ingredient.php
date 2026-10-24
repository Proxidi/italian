<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingredient extends Model
{
    protected $table = 'insumos';

    public $timestamps = false;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['costo_venta' => 'decimal:24'];
    }

    public function formulas(): HasMany
    {
        return $this->hasMany(ProductIngredient::class, 'insumo_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(IngredientCategory::class, 'categorias_insumos_id');
    }

    public function initialUnit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class, 'unidad_id');
    }

    public function finalUnit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class, 'unidad_final');
    }

    public function orderUnit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class, 'unidad_pedido');
    }

    public function conversions(): HasMany
    {
        return $this->hasMany(IngredientConversion::class, 'insumo_id');
    }

    public function expenseConcepts(): HasMany
    {
        return $this->hasMany(ConceptoGasto::class, 'insumo_id');
    }

    public function scopeWithCatalogs(Builder $query): void
    {
        $query->join('categorias_insumos as category', 'category.id', '=', 'insumos.categorias_insumos_id')
            ->join('medidas as initial_unit', 'initial_unit.id', '=', 'insumos.unidad_id')
            ->join('medidas as final_unit', 'final_unit.id', '=', 'insumos.unidad_final')
            ->join('medidas as order_unit', 'order_unit.id', '=', 'insumos.unidad_pedido');
    }
}
