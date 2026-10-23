<?php

namespace Tests\Support;

use App\Models\Plaza;
use App\Models\Usuario;

trait NativeCoreFixtures
{
    protected function createNativeCoreSchema(): void
    {
        // Consumers call this only after configuring their own in-memory DB.
        $migration = require database_path('migrations/0001_01_01_000000_create_users_table.php');
        $migration->up();
    }

    protected function nativeUser(string $role = 'root', array $attributes = []): Usuario
    {
        $id = $attributes['plaza_id'] ?? 1;
        $plaza = Plaza::find($id) ?? Plaza::forceCreate(['id' => $id, 'nombre' => 'Plaza sintética', 'acronimo' => 'PRU']);
        $number = Usuario::count() + 1;
        $user = Usuario::forceCreate(array_replace([
            'nombre' => 'Persona '.$number, 'apellido_paterno' => 'Apellido', 'apellido_materno' => 'Segundo',
            'edad' => 30, 'sexo' => 'H', 'email' => 'persona'.$number.'@example.test', 'usuario' => 'persona'.$number,
            'password' => sha1('clave-de-prueba'), 'plaza_id' => $plaza->id,
        ], $attributes));
        $user->assignRole($role);

        return $user;
    }
}
