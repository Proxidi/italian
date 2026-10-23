<?php

namespace App\Actions\Users;

use App\Models\Plaza;
use App\Models\Usuario;
use App\Support\CorePermissions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class SaveUser
{
    public function handle(#[\SensitiveParameter] array $data, ?Usuario $subject = null): Usuario
    {
        return DB::transaction(function () use ($data, $subject): Usuario {
            $creating = $subject === null;
            $user = $creating ? new Usuario : Usuario::query()->lockForUpdate()->findOrFail($subject->getKey());
            $role = Role::query()->whereKey($data['role_id'])->where('guard_name', 'web')->whereIn('name', array_keys(CorePermissions::labels()))->lockForUpdate()->first();
            if (! $role || $role->guard_name !== 'web' || ! array_key_exists($role->name, CorePermissions::labels())
                || ! Plaza::query()->whereKey($data['plaza_id'])->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['role_id' => 'La plaza o el rol seleccionado ya no está disponible.']);
            }

            $user->fill([
                'nombre' => $data['nombre'], 'apellido_paterno' => $data['apellido_paterno'], 'apellido_materno' => $data['apellido_materno'],
                'edad' => $data['edad'], 'email' => $data['email'], 'usuario' => $data['usuario'],
            ]);
            $user->plaza_id = $data['plaza_id'];
            if ($creating) {
                $user->sexo = $data['sexo'];
            }
            if (isset($data['password']) && $data['password'] !== '') {
                $user->password = Hash::make($data['password']);
            }
            $user->save();

            $user->syncRoles([$role]);

            return $user;
        });
    }
}
