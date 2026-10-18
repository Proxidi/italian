<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IngredientCategory extends Model
{
    protected $table = 'tbl_categorias_insumos';

    public $timestamps = false;

    protected $guarded = ['*'];
}
