<?php

namespace App\Services\Legacy;

use App\Rules\ExactProductDecimal;
use Brick\Math\BigDecimal;
use Carbon\CarbonImmutable;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Validator;

class RemittancesImporter
{
    public const MAP = [
        'tbl_familia_gastos' => ['familias_gastos', ['id' => 'id', 'Clave' => 'clave', 'Nombre' => 'nombre']],
        'tbl_grupo_gastos' => ['grupos_gastos', ['id' => 'id', 'familia_id' => 'familia_id', 'Nombre' => 'nombre']],
        'tbl_grupos_roles' => ['grupos_captura_gastos', ['id' => 'id', 'Nombre' => 'nombre']],
        'tbl_unidades_pago' => ['formas_pago', ['id' => 'id', 'Nombre' => 'nombre']],
        'tbl_insumos_gastos' => ['conceptos_gastos', ['id' => 'id', 'grupo_id' => 'grupo_id', 'insumo_id' => 'insumo_id', 'unidad_id' => 'unidad_id', 'grupo_rol_id' => 'grupo_captura_id', 'Nombre' => 'nombre', 'stock_minimo' => 'stock_minimo', 'stock_maximo' => 'stock_maximo', 'bandera_stocks' => 'bandera_stocks']],
        'tbl_gastos' => ['gastos', ['id' => 'id', 'usuario_id' => 'usuario_id', 'plaza_id' => 'plaza_id', 'insumo_gasto_id' => 'concepto_gasto_id', 'unidad_pago_id' => 'forma_pago_id', 'Cantidad' => 'cantidad', 'Importe' => 'importe', 'Modificacion' => 'modificacion', 'Nueva_Cantidad' => 'nueva_cantidad', 'Antigua_Cantidad' => 'antigua_cantidad', 'Fecha' => 'fecha', 'Estado' => 'estado', 'Notas' => 'notas', 'Corte' => 'corte', 'Archivos' => 'archivos', 'Folio' => 'folio']],
    ];

    private const REFERENCES = [
        'grupos_gastos' => ['familia_id' => 'familias_gastos'],
        'conceptos_gastos' => ['grupo_id' => 'grupos_gastos', 'insumo_id' => 'insumos', 'unidad_id' => 'medidas', 'grupo_captura_id' => 'grupos_captura_gastos'],
        'gastos' => ['usuario_id' => 'usuarios', 'plaza_id' => 'plazas', 'concepto_gasto_id' => 'conceptos_gastos', 'forma_pago_id' => 'formas_pago'],
    ];

    private const DECIMALS = ['cantidad', 'importe', 'nueva_cantidad', 'antigua_cantidad', 'stock_minimo', 'stock_maximo'];

    private const MARKER = 'remesas_importacion_v1';

    public function run(bool $dryRun, int $batch = 500): array
    {
        if ($batch < 1 || $batch > 2000) {
            throw new ImportSafetyException('--lote debe estar entre 1 y 2000.');
        }
        if (! $dryRun && ! app()->environment('testing') && ! app()->isDownForMaintenance()) {
            throw new ImportSafetyException('Active artisan down y detenga los demás escritores antes de importar Remesas.');
        }
        [$source, $target] = app(ImportConnections::class)->check();
        $marker = $target->table('inicializaciones_sistema')->where('clave', self::MARKER)->value('version');
        [$reports, $fingerprint] = $this->scan($source, $target, $batch, $marker !== null);
        if ($marker !== null && ! hash_equals($marker, $fingerprint)) {
            throw new ImportSafetyException('El origen difiere del historial reservado. Revise el cambio antes de reanudar; no borre la reserva para forzar una sincronización.');
        }
        if (! hash_equals($fingerprint, $this->fingerprint($source, $batch))) {
            throw new ImportSafetyException('El origen cambió durante la prevalidación. Detenga los escritores CodeIgniter y vuelva a validar.');
        }
        if ($dryRun || collect($reports)->contains(fn ($report) => $report->hasProblems())) {
            if (! collect($reports)->contains(fn ($report) => $report->hasProblems() || $report->counts['pendientes'] > 0)) {
                $reports['gastos']->counts['agregados_verificados'] = $this->verifyAggregates($source, $target);
            }

            return $reports;
        }
        if ($marker === null) {
            // Durable reservation survives AUTO_INCREMENT consumption after rollback.
            $target->table('inicializaciones_sistema')->insert(['clave' => self::MARKER, 'version' => $fingerprint, 'created_at' => now()]);
        }
        $verified = $target->transaction(function () use ($source, $target, $batch, $fingerprint): int {
            [$checked, $again] = $this->scan($source, $target, $batch, true, true);
            if (! hash_equals($fingerprint, $again) || ! hash_equals($fingerprint, $this->fingerprint($source, $batch))
                || collect($checked)->contains(fn ($report) => $report->hasProblems())) {
                throw new ImportSafetyException('El origen o destino cambió, o existen filas inválidas. El historial completo se revierte; corrija el conflicto y reejecute.');
            }

            return $this->verifyAggregates($source, $target);
        });
        $reports['gastos']->counts['agregados_verificados'] = $verified;
        foreach (self::MAP as $from => [$to]) {
            // MySQL DDL implicitly commits: reserve sequences AFTER the row transaction.
            $minimum = $reports[$to]->sequence['origen_siguiente'];
            if ($this->nextId($target, $to) < $minimum) {
                $target->statement('ALTER TABLE `'.$to.'` AUTO_INCREMENT = '.$minimum);
            }
            $reports[$to]->counts['insertados'] = $reports[$to]->counts['pendientes'];
            $reports[$to]->counts['pendientes'] = 0;
            $reports[$to]->targetCount = $target->table($to)->count();
            $reports[$to]->sequence['destino_siguiente'] = $this->nextId($target, $to);
        }

        return $reports;
    }

    private function fingerprint(Connection $source, int $batch): string
    {
        $digest = hash_init('sha256');
        foreach (self::MAP as $from => [$table, $map]) {
            hash_update($digest, $from.':'.$this->nextId($source, $from).';');
            $this->sourceQuery($source, $from, $map)->chunkById($batch, function ($rows) use ($digest, $map): void {
                foreach ($rows as $raw) {
                    $row = [];
                    foreach ($map as $legacy => $native) {
                        $row[$native] = $raw->$legacy;
                    }
                    hash_update($digest, json_encode($row, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE).'\n');
                }
            });
        }

        return hash_final($digest);
    }

    private function verifyAggregates(Connection $source, Connection $target): int
    {
        // Representative weeks. Restrict destination to the source IDs, allowing
        // future native expenses without treating them as imported duplicates.
        $samples = $source->table('tbl_gastos as g')->join('tbl_insumos_gastos as c', 'c.id', '=', 'g.insumo_gasto_id')
            ->selectRaw('g.plaza_id, c.insumo_id, MIN(g.Fecha) AS fecha')
            ->groupBy('g.plaza_id', 'c.insumo_id')->orderBy('g.plaza_id')->orderBy('c.insumo_id')->limit(3)->get();
        foreach ($samples as $sample) {
            $day = CarbonImmutable::parse($sample->fecha);
            $from = $day->startOfWeek(CarbonImmutable::MONDAY)->toDateString();
            $to = $day->endOfWeek(CarbonImmutable::SUNDAY)->toDateString();
            $query = $source->table('tbl_gastos as g')->join('tbl_insumos_gastos as c', 'c.id', '=', 'g.insumo_gasto_id')
                ->where('g.plaza_id', $sample->plaza_id)->where('c.insumo_id', $sample->insumo_id)->whereBetween('g.Fecha', [$from, $to]);
            $expected = (clone $query)->selectRaw('SUM(CAST(CAST(g.Importe AS CHAR) AS DECIMAL(65,24))) AS importe, SUM(CAST(CAST(g.Cantidad AS CHAR) AS DECIMAL(65,24))) AS cantidad')->first();
            $actual = ['importe' => BigDecimal::zero(), 'cantidad' => BigDecimal::zero()];
            foreach (array_chunk($query->pluck('g.id')->all(), 2000) as $ids) {
                $totals = $target->table('gastos')->whereIn('id', $ids)->selectRaw('SUM(importe) AS importe, SUM(cantidad) AS cantidad')->first();
                foreach (array_keys($actual) as $field) {
                    $actual[$field] = $actual[$field]->plus($totals->$field ?? '0');
                }
            }
            foreach ($actual as $field => $sum) {
                if (! $sum->isEqualTo($expected->$field)) {
                    throw new ImportSafetyException('La comprobación de agregados históricos difiere entre origen y destino; revise la importación.');
                }
            }
        }

        return $samples->count();
    }

    private function scan(Connection $source, Connection $target, int $batch, bool $reserved, bool $write = false): array
    {
        $digest = hash_init('sha256');
        $references = [];
        foreach (['insumos', 'medidas', 'usuarios', 'plazas'] as $parent) {
            $query = $target->table($parent)->orderBy('id');
            if ($write) {
                $query->lockForUpdate();
            }
            $references[$parent] = array_fill_keys($query->pluck('id')->all(), true);
        }
        $reports = [];
        foreach (self::MAP as $from => [$table, $map]) {
            $report = $reports[$table] = new ImportReport;
            $report->targetCount = $target->table($table)->count();
            $next = $this->nextId($target, $table);
            $report->sequence = ['origen_siguiente' => $this->nextId($source, $from), 'destino_siguiente' => $next];
            hash_update($digest, $from.':'.$report->sequence['origen_siguiente'].';');
            $references[$table] = [];
            $duplicates = $this->duplicates($source, $target, $from, $table);
            $this->sourceQuery($source, $from, $map)->chunkById($batch, function ($rawRows) use ($target, $table, $map, $report, $next, $reserved, $write, $digest, &$references, $duplicates): void {
                $query = $target->table($table)->whereIn('id', $rawRows->pluck('id')->all());
                if ($write) {
                    $query->lockForUpdate();
                }
                $existing = $query->get()->keyBy('id');
                $insert = [];
                foreach ($rawRows as $raw) {
                    $row = [];
                    foreach ($map as $legacy => $native) {
                        $row[$native] = $raw->$legacy;
                    }
                    hash_update($digest, json_encode($row, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE).'\n');
                    $report->sourceCount++;
                    $id = (int) $row['id'];
                    try {
                        $row = $this->normalize($table, $row);
                    } catch (\Throwable) {
                        $report->record('rechazados', $id, 'tipo_fecha_texto_o_decimal_incompatible');

                        continue;
                    }
                    $reason = null;
                    foreach (self::REFERENCES[$table] ?? [] as $column => $parent) {
                        if (! isset($references[$parent][$row[$column]])) {
                            $reason = 'referencia_huerfana_'.$column;
                        }
                    }
                    if (isset($duplicates[$id])) {
                        $reason = 'duplicado_segun_collation_destino';
                    }
                    if ($reason !== null) {
                        $report->record('rechazados', $id, $reason);

                        continue;
                    }
                    $references[$table][$id] = true;
                    if ($existing->has($id)) {
                        try {
                            $same = $this->normalize($table, (array) $existing[$id]) === $row;
                        } catch (\Throwable) {
                            $same = false;
                        }
                        $report->record($same ? 'identicos' : 'conflictos', $id, $same ? '' : 'datos_distintos');

                        continue;
                    }
                    if (! $reserved && $id < $next) {
                        $report->record('conflictos', $id, 'identificador_consumido_destino');

                        continue;
                    }
                    foreach ($this->uniqueColumns($table) as $field => $_) {
                        if ($target->table($table)->where($field, $row[$field])->where('id', '<>', $id)->exists()) {
                            $reason = 'unicidad_destino';
                        }
                    }
                    $report->record($reason ? 'conflictos' : 'pendientes', $id, $reason ?? '');
                    if (! $reason) {
                        $insert[] = $row;
                    }
                }
                if ($write && $insert !== []) {
                    $target->table($table)->insert($insert);
                }
            });
        }

        return [$reports, hash_final($digest)];
    }

    private function sourceQuery(Connection $source, string $from, array $map): Builder
    {
        $query = $source->table($from);
        foreach ($map as $legacy => $native) {
            if (in_array($native, self::DECIMALS)) {
                // Let MySQL represent FLOAT as decimal text. Never pass it through PHP float.
                $query->selectRaw('CAST(`'.$legacy.'` AS CHAR) AS `'.$legacy.'`');
            } else {
                $query->addSelect($legacy);
            }
        }

        return $query;
    }

    private function uniqueColumns(string $table): array
    {
        return match ($table) {
            'familias_gastos' => ['clave' => 'Clave'],
            'grupos_captura_gastos', 'formas_pago' => ['nombre' => 'Nombre'],
            default => [],
        };
    }

    private function duplicates(Connection $source, Connection $target, string $from, string $table): array
    {
        $duplicates = [];
        foreach ($this->uniqueColumns($table) as $field => $legacy) {
            $collation = $target->selectOne('SELECT COLLATION_NAME AS c FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=?', [$table, $field])->c ?? '';
            if (! preg_match('/^utf8mb4_[a-z0-9_]+$/D', $collation)) {
                throw new ImportSafetyException('Collation destino no verificable: '.$table);
            }
            $expression = 'CONVERT(`'.$legacy.'` USING utf8mb4) COLLATE '.$collation;
            $groups = $source->table($from)->selectRaw($expression.' AS value')->groupBy('value')->havingRaw('COUNT(*) > 1');
            $original = 'CONVERT(original.`'.$legacy.'` USING utf8mb4) COLLATE '.$collation;
            foreach ($source->table($from.' AS original')->joinSub($groups, 'dup', fn ($join) => $join->on($source->raw($original), '=', 'dup.value'))->pluck('original.id') as $id) {
                $duplicates[(int) $id] = true;
            }
        }

        return $duplicates;
    }

    public function normalize(string $table, array $raw): array
    {
        $map = collect(self::MAP)->first(fn ($definition) => $definition[0] === $table)[1];
        $row = [];
        $rules = [];
        foreach ($map as $field) {
            $row[$field] = $raw[$field] ?? null;
            $rules[$field] = match (true) {
                $field === 'id', str_ends_with($field, '_id') => ['required', 'integer', 'between:1,2147483647'],
                in_array($field, self::DECIMALS) => ['required', new ExactProductDecimal],
                $field === 'fecha' => ['required', 'date_format:Y-m-d', 'after_or_equal:1000-01-01', 'before_or_equal:9999-12-31'],
                in_array($field, ['modificacion', 'estado', 'corte', 'bandera_stocks']) => ['required', 'integer', 'between:-128,127'],
                default => ['present', 'string', 'max:'.match ($field) {
                    'clave' => 5, 'notas', 'archivos' => 300, 'folio' => 20, default => 50,
                }],
            };
            if (is_string($row[$field]) && ! mb_check_encoding($row[$field], 'UTF-8')) {
                throw new \InvalidArgumentException('Texto no UTF-8');
            }
        }
        Validator::make($row, $rules)->validate();
        foreach ($rules as $field => $rule) {
            if (in_array($field, self::DECIMALS)) {
                $row[$field] = ExactProductDecimal::normalize($row[$field]);
            } elseif (in_array('integer', $rule, true)) {
                $row[$field] = (int) $row[$field];
            }
        }
        ksort($row);

        return $row;
    }

    private function nextId(Connection $connection, string $table): int
    {
        $ddl = ((array) $connection->selectOne('SHOW CREATE TABLE `'.$table.'`'))['Create Table'] ?? '';
        if ($ddl === '') {
            throw new ImportSafetyException('No se pudo verificar la estructura de '.$table);
        }
        $next = preg_match('/\bAUTO_INCREMENT=(\d+)/', $ddl, $matches) ? (int) $matches[1] : 1;

        return max($next, ((int) $connection->table($table)->max('id')) + 1);
    }
}
