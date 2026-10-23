<?php

namespace App\Services\Legacy;

use App\Models\Usuario;
use App\Support\CorePermissions;
use DateTimeImmutable;
use Illuminate\Database\Connection;
use Illuminate\Support\Collection;

class CoreImporter
{
    private const MAPS = [
        'plazas' => ['tbl_plazas', 'plazas', ['id' => 'id', 'Nombre' => 'nombre', 'Acronimo' => 'acronimo']],
        'usuarios' => ['tbl_usuarios', 'usuarios', ['id' => 'id', 'Nombre' => 'nombre', 'Apepat' => 'apellido_paterno', 'Apemat' => 'apellido_materno', 'Edad' => 'edad', 'Sexo' => 'sexo', 'Email' => 'email', 'Usuario' => 'usuario', 'Password' => 'password', 'plaza_id' => 'plaza_id']],
        'accesos' => ['tbl_bitacora_accesos', 'bitacora_accesos', ['id' => 'id', 'Fecha' => 'fecha', 'Hora' => 'hora', 'usuario_id' => 'usuario_id', 'plaza_id' => 'plaza_id']],
    ];

    public function validateTargetIntegrity(): void
    {
        [, $target] = app(ImportConnections::class)->check();
        $assignments = $target->table('model_has_roles AS m')->leftJoin('roles AS r', 'r.id', '=', 'm.role_id')
            ->where('m.model_type', 'usuario')->select('m.model_id')->selectRaw('COUNT(*) AS total, SUM(r.guard_name = ?) AS web_total', ['web'])->groupBy('m.model_id');
        $badUsers = $target->table('usuarios AS u')->leftJoinSub($assignments, 'a', 'a.model_id', '=', 'u.id')
            ->leftJoin('plazas AS p', 'p.id', '=', 'u.plaza_id')
            ->where(fn ($query) => $query->whereNull('a.model_id')->orWhere('a.total', '<>', 1)->orWhere('a.web_total', '<>', 1)->orWhereNull('p.id'))->count();
        if ($badUsers) {
            throw new ImportSafetyException('Integridad destino: '.$badUsers.' usuarios sin plaza o sin un único rol web válido.');
        }
        foreach (['model_has_roles', 'model_has_permissions'] as $table) {
            $orphans = $target->table($table.' AS m')->leftJoin('usuarios AS u', 'u.id', '=', 'm.model_id')
                ->where('m.model_type', 'usuario')->whereNull('u.id')->count();
            $wrongType = $target->table($table)->where('model_type', Usuario::class)->count();
            if ($orphans || $wrongType) {
                throw new ImportSafetyException('Integridad destino: asociaciones Spatie huérfanas o con model_type incompatible.');
            }
        }
        $badAccesses = $target->table('bitacora_accesos AS a')->leftJoin('usuarios AS u', 'u.id', '=', 'a.usuario_id')
            ->leftJoin('plazas AS p', 'p.id', '=', 'a.plaza_id')->where(fn ($query) => $query->whereNull('u.id')->orWhereNull('p.id'))->count();
        if ($badAccesses) {
            throw new ImportSafetyException('Integridad destino: '.$badAccesses.' accesos con referencias huérfanas.');
        }
    }

    public function run(string $entity, bool $dryRun = false, int $batchSize = 500, bool $plannedDependencies = false): ImportReport
    {
        if ($plannedDependencies) {
            $dryRun = true;
        }
        if (! isset(self::MAPS[$entity]) || $batchSize < 1 || $batchSize > 2000) {
            throw new ImportSafetyException('Entidad o tamaño de lote inválido (1–2000).');
        }
        [$source, $target] = app(ImportConnections::class)->check();
        [$from, $to, $map] = self::MAPS[$entity];
        $columns = array_keys($map);
        if ($entity === 'usuarios') {
            $columns[] = 'rol_id';
        }
        $report = new ImportReport;
        $report->sourceCount = $source->table($from)->count();
        $report->targetCount = $target->table($to)->count();
        $maxId = (int) $source->table($from)->max('id');
        $duplicates = $entity === 'usuarios' ? $this->duplicateUserIds($source, $target) : [];

        // Validate the complete dataset before writing even the first row. Every
        // insert is then checked again within its own atomic, resumable batch.
        $source->table($from)->select($columns)->where('id', '<=', $maxId)->orderBy('id')->chunkById($batchSize,
            function (Collection $rows) use ($entity, $source, $target, $duplicates, $report, $plannedDependencies): void {
                foreach ($this->classifyBatch($entity, $rows, $source, $target, $duplicates, $plannedDependencies) as [$row, $status, $reason]) {
                    $report->record($status, (int) $row['id'], $reason);
                }
            });
        $report->sequence = $this->sequence($source, $target, $from, $to, $maxId);
        if ($dryRun || $report->hasProblems()) {
            return $report;
        }

        $inserted = 0;
        try {
            $source->table($from)->select($columns)->where('id', '<=', $maxId)->orderBy('id')->chunkById($batchSize,
                function (Collection $rows) use ($entity, $source, $target, $duplicates, $to, &$inserted): void {
                    $inserted += $target->transaction(function () use ($entity, $rows, $source, $target, $duplicates, $to): int {
                        $classified = $this->classifyBatch($entity, $rows, $source, $target, $duplicates);
                        $new = [];
                        foreach ($classified as [$row, $status]) {
                            if (in_array($status, ['rechazados', 'conflictos'], true)) {
                                throw new ImportSafetyException('Los datos cambiaron o entraron en conflicto durante la importación. Lote revertido; puede reejecutarse sin sobrescribir filas.');
                            }
                            if ($status === 'pendientes') {
                                $new[] = $row;
                            }
                        }
                        if ($new) {
                            $roles = $entity === 'usuarios' ? $this->roles($source, $target) : [];
                            $pivots = [];
                            foreach ($new as &$row) {
                                if ($entity === 'usuarios') {
                                    $pivots[] = ['role_id' => $roles[$row['_rol_id']], 'model_id' => $row['id'], 'model_type' => (new Usuario)->getMorphClass()];
                                    unset($row['_rol_id']);
                                    $row += ['created_at' => null, 'updated_at' => null, 'remember_token' => null];
                                }
                            }
                            unset($row);
                            $target->table($to)->insert($new);
                            if ($pivots) {
                                $target->table('model_has_roles')->insert($pivots);
                            }
                        }

                        return count($new);
                    });
                });
            $report->counts['insertados'] = $inserted;
            $report->counts['identicos'] += $report->counts['pendientes'] - $inserted;
            $report->counts['pendientes'] = 0;
            // Explicit-ID inserts advance the normal sequence; also reserve holes
            // already consumed/deleted in legacy. DDL is outside row transactions.
            $current = $this->sequence($source, $target, $from, $to, $maxId);
            if ($current['destino_siguiente'] < $current['minimo_requerido']) {
                $target->statement('ALTER TABLE `'.$to.'` AUTO_INCREMENT = '.$current['minimo_requerido']);
            }
            $report->sequence = $this->sequence($source, $target, $from, $to, $maxId);
            $report->targetCount = $target->table($to)->count();
        } catch (\Throwable) {
            throw new ImportSafetyException('Importación interrumpida. Filas confirmadas antes del fallo: '.$inserted.'. Revise conexión, grants, esquema y concurrencia. El lote activo se revierte; reejecute para reanudar y comprobar también AUTO_INCREMENT.');
        }

        return $report;
    }

    private function classifyBatch(string $entity, Collection $rows, Connection $source, Connection $target, array $duplicates, bool $planned = false): array
    {
        [, $table, $map] = self::MAPS[$entity];
        $existing = $target->table($table)->whereIn('id', $rows->pluck('id'))->get()->keyBy('id');
        $plazas = $entity !== 'plazas' ? $target->table('plazas')->whereIn('id', $rows->pluck('plaza_id'))->pluck('id')->all() : [];
        $sourcePlazas = $entity !== 'plazas' ? $source->table('tbl_plazas')->whereIn('id', $rows->pluck('plaza_id'))->pluck('id')->all() : [];
        $users = $entity === 'accesos' ? $target->table('usuarios')->whereIn('id', $rows->pluck('usuario_id'))->pluck('id')->all() : [];
        $sourceUsers = $entity === 'accesos' ? $source->table('tbl_usuarios')->whereIn('id', $rows->pluck('usuario_id'))->pluck('id')->all() : [];
        if ($planned) {
            $plazas = array_unique([...$plazas, ...$sourcePlazas]);
            $users = array_unique([...$users, ...$sourceUsers]);
        }
        $roles = $entity === 'usuarios' ? $this->roles($source, $target, $planned) : [];
        $pivots = $entity === 'usuarios' ? $target->table('model_has_roles')->whereIn('model_type', [(new Usuario)->getMorphClass(), Usuario::class])->whereIn('model_id', $rows->pluck('id'))->get()->groupBy('model_id') : collect();
        $direct = $entity === 'usuarios' ? $target->table('model_has_permissions')->whereIn('model_type', [(new Usuario)->getMorphClass(), Usuario::class])->whereIn('model_id', $rows->pluck('id'))->pluck('model_id')->all() : [];
        $result = [];
        foreach ($rows as $raw) {
            $row = [];
            foreach ($map as $legacy => $native) {
                $row[$native] = $raw->$legacy;
            }
            $reason = $this->invalid($entity, $row);
            if (! $reason && $entity !== 'plazas' && (! in_array($row['plaza_id'], $plazas) || ! in_array($row['plaza_id'], $sourcePlazas))) {
                $reason = 'plaza_inexistente_en_origen_o_destino';
            }
            if (! $reason && $entity === 'accesos' && (! in_array($row['usuario_id'], $users) || ! in_array($row['usuario_id'], $sourceUsers))) {
                $reason = 'usuario_inexistente_en_origen_o_destino';
            }
            if ($entity === 'usuarios') {
                $row['_rol_id'] = (int) $raw->rol_id;
                if (! $reason && ! isset($roles[$row['_rol_id']])) {
                    $reason = 'rol_inexistente_o_catalogo_legacy_incompatible';
                }
                if (! $reason && isset($duplicates[(int) $row['id']])) {
                    $reason = 'duplicado_origen_segun_collation_destino';
                }
            }
            if ($reason) {
                $result[] = [$row, 'rechazados', $reason];

                continue;
            }
            $old = $existing[$row['id']] ?? null;
            $conflict = '';
            if ($old) {
                foreach ($map as $column) {
                    if ((string) $old->$column !== (string) $row[$column]) {
                        $conflict = $column === 'password' ? 'password_destino_protegido' : 'fila_destino_diferente';
                        break;
                    }
                }
            }
            if ($entity === 'usuarios') {
                $assigned = $pivots->get($row['id'], collect());
                if ($old && ($assigned->count() !== 1 || (int) $assigned->first()->role_id !== $roles[$row['_rol_id']] || $assigned->first()->model_type !== (new Usuario)->getMorphClass())) {
                    $conflict = 'rol_destino_diferente_o_multiple';
                }
                if (! $old && ($assigned->isNotEmpty() || in_array($row['id'], $direct))) {
                    $conflict = 'asignaciones_polimorficas_sin_usuario';
                }
                foreach (['email', 'usuario'] as $column) {
                    // Let MySQL apply the destination column's real collation,
                    // including accent/case/trailing-space equivalence.
                    if ($target->table('usuarios')->where($column, $row[$column])->where('id', '<>', $row['id'])->exists()) {
                        $conflict = 'unicidad_destino_'.$column;
                    }
                }
            }
            $result[] = [$row, $conflict ? 'conflictos' : ($old ? 'identicos' : 'pendientes'), $conflict];
        }

        return $result;
    }

    private function invalid(string $entity, array $row): ?string
    {
        foreach (['id', ...($entity === 'plazas' ? [] : ['plaza_id']), ...($entity === 'accesos' ? ['usuario_id'] : [])] as $column) {
            if (filter_var($row[$column], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => PHP_INT_MAX - 1]]) === false) {
                return 'identificador_invalido_'.$column;
            }
        }
        $limits = match ($entity) {
            'plazas' => ['nombre' => 50, 'acronimo' => 20],
            'usuarios' => ['nombre' => 25, 'apellido_paterno' => 25, 'apellido_materno' => 25, 'sexo' => 1, 'email' => 75, 'usuario' => 50],
            default => [],
        };
        foreach ($limits as $column => $length) {
            $value = $row[$column];
            if (! is_string($value) || ! mb_check_encoding($value, 'UTF-8') || trim($value) === '' || mb_strlen($value) > $length || preg_match('/[\x00-\x1F\x7F]/', $value)) {
                return 'texto_vacio_largo_o_codificacion_'.$column;
            }
        }
        if ($entity === 'usuarios') {
            if (filter_var($row['edad'], FILTER_VALIDATE_INT, ['options' => ['min_range' => -128, 'max_range' => 127]]) === false) {
                return 'edad_fuera_de_tipo';
            }
            if (! is_string($row['password']) || ! preg_match('/^[a-f0-9]{40}$/iD', $row['password'])) {
                return 'password_origen_no_sha1';
            }
        }
        if ($entity === 'accesos') {
            $date = is_string($row['fecha']) ? DateTimeImmutable::createFromFormat('!Y-m-d', $row['fecha']) : false;
            if (! $date || $date->format('Y-m-d') !== $row['fecha'] || (int) substr($row['fecha'], 0, 4) < 1000) {
                return 'fecha_invalida';
            }
            if (! is_string($row['hora']) || ! preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/D', $row['hora'])) {
                return 'hora_no_representa_hora_de_acceso';
            }
        }

        return null;
    }

    private function roles(Connection $source, Connection $target, bool $planned = false): array
    {
        $legacy = $source->table('tbl_roles')->pluck('Nombre', 'id');
        $native = $target->table('roles')->where('guard_name', 'web')->get()->filter(fn ($role) => $role->guard_name === 'web')->keyBy('name');
        $roles = [];
        foreach (CorePermissions::ROLES as $id => $role) {
            if (($legacy[$id] ?? null) === $role['label'] && isset($native[$role['name']])) {
                $roles[$id] = (int) $native[$role['name']]->id;
            } elseif ($planned && ($legacy[$id] ?? null) === $role['label'] && ! isset($native[$role['name']])) {
                $roles[$id] = $id;
            }
        }

        return $roles;
    }

    private function duplicateUserIds(Connection $source, Connection $target): array
    {
        $duplicates = [];
        foreach (['Email' => 'email', 'Usuario' => 'usuario'] as $legacy => $native) {
            $column = $target->selectOne('SELECT COLLATION_NAME AS collation_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?', ['usuarios', $native]);
            $collation = $column?->collation_name ?? '';
            if (! preg_match('/^utf8mb4_[a-z0-9_]+$/D', $collation)) {
                throw new ImportSafetyException('La unicidad requiere una collation utf8mb4 verificable en usuarios.');
            }
            $expression = 'CONVERT(`'.$legacy.'` USING utf8mb4) COLLATE '.$collation;
            $groups = $source->table('tbl_usuarios')->selectRaw($expression.' AS valor')->groupBy('valor')->havingRaw('COUNT(*) > 1');
            $original = 'CONVERT(original.`'.$legacy.'` USING utf8mb4) COLLATE '.$collation;
            foreach ($source->table('tbl_usuarios AS original')->joinSub($groups, 'duplicados', fn ($join) => $join->on($source->raw($original), '=', 'duplicados.valor'))->pluck('original.id') as $id) {
                $duplicates[(int) $id] = true;
            }
        }

        return $duplicates;
    }

    private function sequence(Connection $source, Connection $target, string $from, string $to, int $maxId): array
    {
        // SHOW CREATE bypasses cached information_schema statistics (which may
        // keep an obsolete AUTO_INCREMENT for up to a day on MySQL 8.4).
        $fromNext = $this->nextId($source, $from);
        $toNext = $this->nextId($target, $to);

        return ['origen_siguiente' => (int) $fromNext, 'destino_siguiente' => (int) $toNext, 'minimo_requerido' => max($maxId + 1, (int) $fromNext)];
    }

    private function nextId(Connection $connection, string $table): int
    {
        $definition = (array) $connection->selectOne('SHOW CREATE TABLE `'.$table.'`');
        $ddl = $definition['Create Table'] ?? '';
        if (! str_contains($ddl, 'AUTO_INCREMENT')) {
            throw new ImportSafetyException('No se pudo comprobar AUTO_INCREMENT de origen/destino.');
        }

        return preg_match('/\bAUTO_INCREMENT=(\d+)/', $ddl, $matches) ? (int) $matches[1] : 1;
    }
}
