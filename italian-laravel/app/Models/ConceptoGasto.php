<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConceptoGasto extends Model
{
    protected $table = 'conceptos_gastos';

    public $timestamps = false;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['stock_minimo' => 'decimal:24', 'stock_maximo' => 'decimal:24'];
    }

    public function insumo(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class, 'insumo_id');
    }

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class, 'unidad_id');
    }

    public function gastos(): HasMany
    {
        return $this->hasMany(Gasto::class, 'concepto_gasto_id');
    }
}
