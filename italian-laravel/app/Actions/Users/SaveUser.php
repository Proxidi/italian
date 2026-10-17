<?php

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class SaveUser
{
    public const ROLES = [1 => 'superadmin', 2 => 'admin', 3 => 'root', 4 => 'submanager', 5 => 'socio'];

    public function handle(array $data, ?User $subject = null): User
    {
        return DB::transaction(function () use ($data, $subject): User {
            $creating = $subject === null;
            $user = $creating ? new User : User::query()->lockForUpdate()->findOrFail($subject->getKey());
            $roleChanged = $creating || $user->rol_id !== (int) $data['rol_id'];

            $user->fill([
                'Nombre' => $data['nombre'], 'Apepat' => $data['apepat'], 'Apemat' => $data['apemat'],
                'Edad' => $data['edad'], 'Email' => $data['email'], 'Usuario' => $data['usuario'],
            ]);
            $user->plaza_id = $data['plaza_id'];
            $user->rol_id = $data['rol_id'];

            if ($creating) {
                $user->Sexo = $data['sexo'];
                // Both NOT NULL legacy identifiers are completed in this transaction.
                $user->Hash = '';
                $user->sha1_id = '';
            }
            if (isset($data['password']) && $data['password'] !== '') {
                // Required while CodeIgniter still authenticates against this column.
                $user->Password = sha1($data['password']);
            }
            $user->save();

            if ($creating) {
                $user->Hash = password_hash((string) $user->getKey(), PASSWORD_BCRYPT);
                $user->sha1_id = sha1((string) $user->getKey());
                $user->save();
            }
            if ($roleChanged) {
                // Fail atomically if the mapped web role has not been provisioned.
                $user->syncRoles([Role::findByName(self::ROLES[$user->rol_id], 'web')]);
            }

            return $user;
        });
    }
}
