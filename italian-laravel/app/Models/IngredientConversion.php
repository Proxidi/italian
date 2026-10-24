<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IngredientConversion extends Model
{
    protected $table = 'conversiones_insumos';

    public $timestamps = false;

    protected $fillable = ['bodega', 'pedido'];

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class, 'insumo_id');
    }
}
