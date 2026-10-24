<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductIngredient extends Model
{
    protected $table = 'productos_insumos';

    public $timestamps = false;

    protected $fillable = ['producto_id', 'insumo_id', 'cantidad'];

    protected function casts(): array
    {
        return ['cantidad' => 'decimal:24'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'producto_id');
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class, 'insumo_id');
    }
}
