<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

// Prepared for the new schema; the active auth provider still uses legacy User.
class Usuario extends Authenticatable
{
    use HasRoles, Notifiable;

    protected $table = 'usuarios';

    protected $guard_name = 'web';

    protected $fillable = [
        'nombre', 'apellido_paterno', 'apellido_materno', 'edad', 'sexo',
        'email', 'usuario', 'plaza_id',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        // No hashed cast: future importers must preserve the original SHA-1
        // until a correctly authenticated login can replace it.
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
