<?php

namespace Database\Seeders;

use App\Services\Legacy\ImportConnections;
use App\Services\Legacy\ImportSafetyException;
use App\Support\CorePermissions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

abstract class ModulePermissionsSeeder extends Seeder
{
    protected string $group;

    public function run(): void
    {
        $connection = DB::connection();
        $isolated = app()->environment('testing') && $connection->getDriverName() === 'sqlite' && $connection->getDatabaseName() === ':memory:';
        if (! $isolated) {
            if ($connection->getName() !== 'mysql') {
                throw new ImportSafetyException('Los seeders de permisos requieren el destino mysql verificado.');
            }
            app(ImportConnections::class)->target();
        }
        ImportConnections::assertPermissionCacheTarget();
        DB::transaction(function (): void {
            foreach (CorePermissions::GROUPS[$this->group] as $name) {
                $roles = array_map(fn ($role) => Role::findByName($role, 'web'), CorePermissions::roleNamesFor($name));
                $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
                if ($permission->name !== $name || $permission->guard_name !== 'web') {
                    throw new ImportSafetyException('Conflicto en el nombre/guard del permiso; no se sobrescribió.');
                }
                // New catalog entries receive their initial grants once. A
                // routine rerun never restores revocations or removes grants.
                if ($permission->wasRecentlyCreated) {
                    $permission->roles()->attach(array_map(fn ($role) => $role->id, $roles));
                }
            }
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
