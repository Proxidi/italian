<?php

namespace App\Services\Legacy;

use App\Models\Usuario;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

class ImportConnections
{
    public function check(): array
    {
        $source = DB::connection('legacy');
        $target = $this->target();
        if ($source->getDriverName() !== 'mysql' || ! $source->getConfig('username')) {
            throw new ImportSafetyException('Configure una cuenta MySQL legacy independiente y de solo lectura.');
        }
        if ($source->getConfig('username') === $target->getConfig('username')) {
            throw new ImportSafetyException('Origen y destino deben utilizar cuentas independientes.');
        }
        if ($source->getConfig('read') || $target->getConfig('read') || $source->getTablePrefix() || $target->getTablePrefix()) {
            throw new ImportSafetyException('Los importadores requieren conexiones directas, sin replicas ni prefijos.');
        }
        $from = $source->selectOne('SELECT DATABASE() AS db, CURRENT_USER() AS account, CURRENT_ROLE() AS active_roles, @@character_set_connection AS charset', [], false);
        $to = $target->selectOne('SELECT DATABASE() AS db, CURRENT_USER() AS account', [], false);
        if (! $from->db || $from->db === $to->db || $from->account === $to->account) {
            throw new ImportSafetyException('Origen y destino efectivos deben ser bases y cuentas distintas.');
        }
        $testSource = app()->environment('testing') && config('legacy_import.allow_test_databases') === true
            && $from->db === 'italian_stage02_source_tests';
        if ($from->db !== 'italian' && ! $testSource) {
            throw new ImportSafetyException('Origen rechazado: se requiere italian o la fuente descartable autorizada de etapa 2.');
        }
        if ($from->active_roles !== 'NONE') {
            throw new ImportSafetyException('La cuenta legacy tiene roles activos; se requiere SELECT directo sin herencia.');
        }
        if ($from->charset !== 'utf8mb4') {
            throw new ImportSafetyException('La conexión legacy debe negociar utf8mb4 para convertir los textos correctamente.');
        }
        $grants = $source->select('SHOW GRANTS FOR CURRENT_USER', [], false);
        self::assertReadOnlyGrants(array_map(fn ($row) => (string) array_values((array) $row)[0], $grants));

        return [$source, $target];
    }

    public function target(): Connection
    {
        $target = DB::connection('mysql');
        if ($target->getDriverName() !== 'mysql' || $target->getConfig('read') || $target->getTablePrefix()) {
            throw new ImportSafetyException('El destino debe ser la conexión mysql directa, sin prefijos.');
        }
        $effective = $target->selectOne('SELECT DATABASE() AS db, @@foreign_key_checks AS foreign_keys, @@character_set_connection AS charset, @@sql_mode AS sql_mode', [], false);
        $name = $effective->db;
        $test = app()->environment('testing') && config('legacy_import.allow_test_databases') === true
            && $name === 'italian_stage02_target_tests';
        if ($name !== 'italian_laravel' && ! $test) {
            throw new ImportSafetyException('Destino rechazado: se requiere italian_laravel o la BD descartable autorizada de etapa 2.');
        }
        if ((int) $effective->foreign_keys !== 1 || $effective->charset !== 'utf8mb4'
            || ! preg_match('/STRICT_(TRANS|ALL)_TABLES/', $effective->sql_mode)) {
            throw new ImportSafetyException('El destino requiere FKs activas, modo estricto y conexión utf8mb4.');
        }
        if (config('permission.teams') || (new Usuario)->getMorphClass() !== 'usuario'
            || (config('permission.column_names.role_pivot_key') ?? 'role_id') !== 'role_id'
            || (config('permission.column_names.permission_pivot_key') ?? 'permission_id') !== 'permission_id'
            || config('permission.column_names.model_morph_key') !== 'model_id'
            || config('permission.table_names') !== [
                'roles' => 'roles', 'permissions' => 'permissions', 'model_has_permissions' => 'model_has_permissions',
                'model_has_roles' => 'model_has_roles', 'role_has_permissions' => 'role_has_permissions',
            ]) {
            throw new ImportSafetyException('La configuración Spatie/morph map difiere del contrato del núcleo.');
        }

        return $target;
    }

    public static function assertPermissionCacheTarget(): void
    {
        $store = config('permission.cache.store');
        $store = $store === 'default' ? config('cache.default') : $store;
        $driver = config('cache.stores.'.$store.'.driver');
        if ($driver === 'database') {
            $connection = config('cache.stores.'.$store.'.connection') ?: config('database.default');
            if ($connection !== 'mysql' || config('cache.stores.'.$store.'.table') !== 'cache') {
                throw new ImportSafetyException('La caché Spatie debe usar la tabla cache del destino mysql.');
            }
        } elseif (! in_array($driver, ['array', 'file', 'null'], true)) {
            throw new ImportSafetyException('Caché Spatie no admitida por el bootstrap; revise su destino antes de inicializar.');
        }
    }

    public static function assertReadOnlyGrants(array $grants): void
    {
        if ($grants === []) {
            throw new ImportSafetyException('No se pudieron verificar los grants legacy.');
        }
        foreach ($grants as $grant) {
            // Fail closed: inherited roles, dynamic privileges, column grants,
            // GRANT OPTION and unfamiliar formats require an explicit review.
            if (! preg_match('/^GRANT (USAGE|SELECT) ON .+ TO .+$/iD', $grant)
                || preg_match('/WITH GRANT OPTION|WITH ADMIN OPTION/i', $grant)) {
                throw new ImportSafetyException('La cuenta legacy tiene privilegios no admitidos o roles heredados; use SELECT directo, sin GRANT OPTION.');
            }
        }
    }
}
