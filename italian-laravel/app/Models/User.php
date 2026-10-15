<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    protected $table = 'tbl_usuarios';

    public $timestamps = false;

    protected $guard_name = 'web';

    // La tabla legacy no contiene remember_token.
    protected $rememberTokenName = '';

    protected $fillable = [
        'Nombre',
        'Apepat',
        'Apemat',
        'Edad',
        'Sexo',
        'Email',
        'Usuario',
    ];

    protected $hidden = [
        'Password',
        'Hash',
        'sha1_id',
    ];

    protected function casts(): array
    {
        return [
            'Edad' => 'integer',
            'plaza_id' => 'integer',
            'rol_id' => 'integer',
        ];
    }

    public function getAuthPasswordName(): string
    {
        return 'Password';
    }

    /*
     * Configurar el proveedor del guard web con App\Models\User.
     *
     * Durante la convivencia con CodeIgniter, Password permanece
     * intacto en SHA-1. No aplicar casts, mutadores ni observers
     * que transformen automáticamente esta columna.
     *
     * Los roles y permisos se consultan exclusivamente con Spatie.
     * rol_id se conserva como fuente de verdad para el Seeder:
     *
     * 1 => superadmin (Dueño)
     * 2 => admin      (Manager)
     * 3 => root       (Gerente)
     * 4 => submanager (SubManager)
     * 5 => socio      (Socio)
     */
}
