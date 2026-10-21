<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CostOfSalePermissionsSeeder extends Seeder
{
    public const PERMISSIONS = ['products.cost-of-sale.view', 'products.cost-of-sale.update'];

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
