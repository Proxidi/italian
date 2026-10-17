<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ConfigurationPermissionsSeeder extends Seeder
{
    public const PERMISSIONS = [
        'configuration.view',
        'configuration.upload-window.view',
        'configuration.upload-window.update',
        'configuration.notifications.view',
        'configuration.notifications.update',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        try {
            DB::transaction(function (): void {
                $root = Role::findByName('root', 'web');

                foreach (self::PERMISSIONS as $name) {
                    Permission::findOrCreate($name, 'web')->roles()->sync([$root->getKey()]);
                }
            });
        } finally {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
}
