<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductFamily extends Model
{
    protected $table = 'tbl_familia_productos';

    public $timestamps = false;

    protected $guarded = ['*'];
}
