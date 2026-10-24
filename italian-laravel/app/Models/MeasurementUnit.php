<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeasurementUnit extends Model
{
    protected $table = 'medidas';

    public $timestamps = false;

    protected $fillable = ['nombre'];

    public function ingredients(): HasMany
    {
        return $this->hasMany(Ingredient::class, 'unidad_id');
    }
}
