<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingredient extends Model
{
    protected $table = 'tbl_insumos';

    public $timestamps = false;

    protected $guarded = ['*'];

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

    public function scopeWithCatalogs(Builder $query): void
    {
        $query->join('tbl_categorias_insumos as category', 'category.id', '=', 'tbl_insumos.categorias_insumos_id')
            ->join('tbl_unidades as initial_unit', 'initial_unit.id', '=', 'tbl_insumos.unidad_id')
            ->join('tbl_unidades as final_unit', 'final_unit.id', '=', 'tbl_insumos.unidad_final')
            ->join('tbl_unidades as order_unit', 'order_unit.id', '=', 'tbl_insumos.unidad_pedido');
    }
}
