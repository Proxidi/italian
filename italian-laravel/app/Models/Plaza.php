<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plaza extends Model
{
    protected $table = 'plazas';

    public $timestamps = false;

    protected $fillable = ['nombre', 'acronimo'];

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class, 'plaza_id');
    }

    public function accesos(): HasMany
    {
        return $this->hasMany(Acceso::class, 'plaza_id');
    }
}
