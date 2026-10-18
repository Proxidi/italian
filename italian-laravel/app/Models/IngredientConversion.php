<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IngredientConversion extends Model
{
    protected $table = 'tbl_insumos_inventario';

    public $timestamps = false;

    protected $fillable = ['Bodega', 'Pedido'];

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class, 'insumo_id');
    }
}
