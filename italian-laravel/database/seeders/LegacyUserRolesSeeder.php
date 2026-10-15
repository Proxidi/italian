<?php
// database/seeders/LegacyUserRolesSeeder.php
// Ejecutar: php artisan db:seed --class=LegacyUserRolesSeeder

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class LegacyUserRolesSeeder extends Seeder
{
    public function run(): void
    {
        $mapping = [
            1 => 'superadmin',
            2 => 'admin',
            3 => 'root',
            4 => 'submanager',
            5 => 'socio',
        ];

        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        $processed = 0;

        try {
            DB::transaction(function () use ($mapping, &$processed): void {
                $roles = [];

                foreach ($mapping as $name) {
                    $roles[$name] = Role::findOrCreate($name, 'web');
                }

                User::query()->chunkById(
                    500,
                    function (Collection $users) use (
                        $mapping,
                        $roles,
                        &$processed,
                    ): void {
                        foreach ($users as $user) {
                            $legacyRoleId = (int) $user->rol_id;

                            if (! isset($mapping[$legacyRoleId])) {
                                throw new RuntimeException(
                                    "Usuario {$user->getKey()}: rol_id legacy "
                                    ."{$legacyRoleId} sin correspondencia."
                                );
                            }

                            // Sustituye las asignaciones anteriores:
                            // rol_id es la fuente de verdad en esta etapa.
                            $user->syncRoles([
                                $roles[$mapping[$legacyRoleId]],
                            ]);

                            // No actualiza tbl_usuarios ni Password.
                            // No migra ni modifica permisos de los roles.
                            $processed++;
                        }
                    }
                );
            });
        } finally {
            $registrar->forgetCachedPermissions();
        }

        $this->command?->info(
            "Roles web sincronizados. Usuarios procesados: {$processed}."
        );
    }
}
