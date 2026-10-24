<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IngredientCategory extends Model
{
    protected $table = 'categorias_insumos';

    public $timestamps = false;

    protected $guarded = ['*'];

    public function ingredients(): HasMany
    {
        return $this->hasMany(Ingredient::class, 'categorias_insumos_id');
    }
}
