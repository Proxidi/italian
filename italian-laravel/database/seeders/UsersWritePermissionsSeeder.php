<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UsersWritePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        try {
            DB::transaction(function (): void {
                $root = Role::findByName('root', 'web');
                // Matches Usuarios/store, editAdmin and update in the supplied dump.
                foreach (['users.create', 'users.update'] as $name) {
                    Permission::findOrCreate($name, 'web')->roles()->sync([$root->getKey()]);
                }
            });
        } finally {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
}
