<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeasurementUnit extends Model
{
    protected $table = 'tbl_unidades';

    public $timestamps = false;

    protected $fillable = ['Nombre'];
}
