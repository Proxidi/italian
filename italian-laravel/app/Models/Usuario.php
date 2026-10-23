<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class Usuario extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    protected $table = 'usuarios';

    protected $guard_name = 'web';

    protected $fillable = [
        'nombre', 'apellido_paterno', 'apellido_materno', 'edad', 'sexo',
        'email', 'usuario', 'plaza_id',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        // Importers preserve SHA-1. Only an authenticated login or an explicit
        // administrative password change may write a modern hash.
        return ['edad' => 'integer', 'plaza_id' => 'integer'];
    }

    public function plaza(): BelongsTo
    {
        return $this->belongsTo(Plaza::class, 'plaza_id');
    }

    public function accesos(): HasMany
    {
        return $this->hasMany(Acceso::class, 'usuario_id');
    }
}
