<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UsersReadPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        try {
            DB::transaction(function (): void {
                // Los roles ya deben existir mediante LegacyUserRolesSeeder.
                $root = Role::findByName('root', 'web');
                $superadmin = Role::findByName('superadmin', 'web');

                $directory = Permission::findOrCreate(
                    'users.directory.view',
                    'web',
                );

                $accessLog = Permission::findOrCreate(
                    'access-log.view',
                    'web',
                );

                /*
                 * Estas relaciones son la fuente de verdad para los dos
                 * permisos de consulta de Usuarios.
                 *
                 * sync() solo afecta las relaciones de cada permiso:
                 * no elimina otros permisos de root o superadmin,
                 * no toca roles de usuarios y no modifica tablas legacy.
                 */
                $directory->roles()->sync([$root->getKey()]);

                $accessLog->roles()->sync([
                    $root->getKey(),
                    $superadmin->getKey(),
                ]);
            });
        } finally {
            $registrar->forgetCachedPermissions();
        }
    }
}
