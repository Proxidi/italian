<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductIngredient extends Model
{
    protected $table = 'tbl_productos_insumos';

    public $timestamps = false;

    protected $fillable = ['producto_id', 'insumo_id', 'Cantidad'];

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class, 'insumo_id');
    }
}
