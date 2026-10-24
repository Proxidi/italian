<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $table = 'productos';

    public $timestamps = false;

    protected $fillable = ['nombre', 'clave', 'familia_id', 'estado'];

    public function family(): BelongsTo
    {
        return $this->belongsTo(ProductFamily::class, 'familia_id');
    }

    public function formulas(): HasMany
    {
        return $this->hasMany(ProductIngredient::class, 'producto_id');
    }
}
