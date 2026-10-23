<?php

namespace App\Services\Legacy;

use App\Support\CorePermissions;
use Illuminate\Database\Connection;
use Spatie\Permission\PermissionRegistrar;

class CoreBootstrap
{
    private const KEY = 'permisos_nucleo_v1';

    public function run(bool $dryRun = false): string
    {
        [$source, $target] = app(ImportConnections::class)->check();
        $legacyRoles = $source->table('tbl_roles')->pluck('Nombre', 'id');
        if ($legacyRoles->count() !== count(CorePermissions::ROLES)) {
            throw new ImportSafetyException('El catálogo legacy no contiene los cinco roles confirmados; requiere revisión.');
        }
        foreach (CorePermissions::ROLES as $id => $role) {
            if (($legacyRoles[$id] ?? null) !== $role['label']) {
                throw new ImportSafetyException('El catálogo legacy difiere del mapeo confirmado de roles; no se inicializó.');
            }
        }
        $message = $this->inspect($target);
        if ($dryRun || $message === 'ya_inicializado') {
            return $message;
        }
        ImportConnections::assertPermissionCacheTarget();
        $target->transaction(function () use ($target): void {
            // The marker is inserted first: its unique key serializes concurrent
            // bootstraps. Any failure rolls back catalog, grants and marker.
            $target->table('inicializaciones_sistema')->insert([
                'clave' => self::KEY, 'version' => $this->version(), 'created_at' => now(),
            ]);
            $this->assertEmptyInstallation($target);
            $now = now();
            foreach (CorePermissions::ROLES as $id => $role) {
                $target->table('roles')->insert(['id' => $id, 'name' => $role['name'], 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now]);
            }
            $roleIds = $target->table('roles')->pluck('id', 'name');
            foreach (CorePermissions::names() as $name) {
                $id = $target->table('permissions')->insertGetId(['name' => $name, 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now]);
                foreach (CorePermissions::roleNamesFor($name) as $roleName) {
                    $target->table('role_has_permissions')->insert(['role_id' => $roleIds[$roleName], 'permission_id' => $id]);
                }
            }
        });
        // No cache call whatsoever during dry-run, including DB-backed caches.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return 'inicializado';
    }

    private function inspect(Connection $target): string
    {
        if (! $target->getSchemaBuilder()->hasTable('inicializaciones_sistema')) {
            throw new ImportSafetyException('Falta la migration de etapa 2: revise el destino y ejecute migrate --database=mysql antes del bootstrap.');
        }
        $marker = $target->table('inicializaciones_sistema')->where('clave', self::KEY)->first();
        if ($marker) {
            if ($marker->version !== $this->version()) {
                throw new ImportSafetyException('La versión del bootstrap difiere; requiere revisión explícita.');
            }
            foreach (CorePermissions::ROLES as $id => $role) {
                $current = $target->table('roles')->where('id', $id)->first();
                if (! $current || $current->name !== $role['name'] || $current->guard_name !== 'web') {
                    throw new ImportSafetyException('Conflicto en el catálogo de roles existente; no se sobrescribió.');
                }
            }
            foreach (CorePermissions::names() as $name) {
                $current = $target->table('permissions')->where(['name' => $name, 'guard_name' => 'web'])->first();
                if (! $current || $current->name !== $name || $current->guard_name !== 'web') {
                    throw new ImportSafetyException('Falta un permiso del catálogo inicial; revise la administración existente.');
                }
            }

            return 'ya_inicializado';
        }
        $this->assertEmptyInstallation($target);

        return 'pendiente';
    }

    private function assertEmptyInstallation(Connection $target): void
    {
        foreach (['roles', 'permissions', 'role_has_permissions', 'model_has_roles', 'model_has_permissions', 'usuarios'] as $table) {
            if ($target->table($table)->exists()) {
                throw new ImportSafetyException('Sin marcador y con catálogo/asignaciones/usuarios existentes: bootstrap en conflicto. No se adoptan ni sobrescriben automáticamente.');
            }
        }
    }

    private function version(): string
    {
        return hash('sha256', json_encode([CorePermissions::ROLES, CorePermissions::GROUPS]));
    }
}
