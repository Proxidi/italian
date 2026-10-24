<?php

namespace App\Services\Legacy;

use App\Rules\ExactProductDecimal;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Validator;

class ProductsImporter
{
    public const MAP = [
        'tbl_unidades' => ['medidas', ['id' => 'id', 'Nombre' => 'nombre']],
        'tbl_categorias_insumos' => ['categorias_insumos', ['id' => 'id', 'Nombre' => 'nombre']],
        'tbl_familia_productos' => ['familias_productos', ['id' => 'id', 'Nombre' => 'nombre']],
        'tbl_insumos' => ['insumos', ['id' => 'id', 'Nombre' => 'nombre', 'clave_pedido' => 'clave_pedido', 'categorias_insumos_id' => 'categorias_insumos_id', 'unidad_id' => 'unidad_id', 'unidad_pedido' => 'unidad_pedido', 'unidad_final' => 'unidad_final', 'inv_produccion' => 'inv_produccion', 'inv_fisico' => 'inv_fisico', 'pedido' => 'pedido', 'Pedido_Pasteleria' => 'pedido_pasteleria', 'categoria_pedido_id' => 'categoria_pedido_id', 'categoria_quebranto_id' => 'categoria_quebranto_id', 'Analisis_CV' => 'analisis_cv', 'Costo_Venta' => 'costo_venta', 'Orden_Captura_Inventario' => 'orden_captura_inventario']],
        'tbl_insumos_inventario' => ['conversiones_insumos', ['id' => 'id', 'insumo_id' => 'insumo_id', 'Nombre' => 'nombre', 'Bodega' => 'bodega', 'Piso' => 'piso', 'Total' => 'total', 'DLC' => 'dlc', 'Pedido' => 'pedido', 'CVI' => 'cvi', 'CVG' => 'cvg']],
        'tbl_productos' => ['productos', ['id' => 'id', 'Nombre' => 'nombre', 'Clave' => 'clave', 'familia_id' => 'familia_id', 'Estado' => 'estado']],
        'tbl_productos_insumos' => ['productos_insumos', ['id' => 'id', 'producto_id' => 'producto_id', 'insumo_id' => 'insumo_id', 'Cantidad' => 'cantidad']],
    ];

    private const REFERENCES = [
        'insumos' => ['categorias_insumos_id' => 'categorias_insumos', 'unidad_id' => 'medidas', 'unidad_pedido' => 'medidas', 'unidad_final' => 'medidas'],
        'conversiones_insumos' => ['insumo_id' => 'insumos'],
        'productos' => ['familia_id' => 'familias_productos'],
        'productos_insumos' => ['producto_id' => 'productos', 'insumo_id' => 'insumos'],
    ];

    private const MARKER = 'productos_importacion_v1';

    public function run(bool $dryRun, int $batch = 500): array
    {
        if ($batch < 1 || $batch > 2000) {
            throw new ImportSafetyException('--lote debe estar entre 1 y 2000.');
        }
        if (! $dryRun && ! app()->environment('testing') && ! app()->isDownForMaintenance()) {
            throw new ImportSafetyException('Active artisan down antes de importar Productos; los escritores operativos deben estar detenidos para reservar IDs y catálogos.');
        }
        [$source, $target] = app(ImportConnections::class)->check();
        $data = $this->snapshot($source);
        $fingerprint = hash('sha256', json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        $marker = $target->table('inicializaciones_sistema')->where('clave', self::MARKER)->value('version');
        if ($marker !== null && $marker !== $fingerprint) {
            throw new ImportSafetyException('El origen difiere del conjunto reservado por una importación anterior; requiere revisión, sin sobrescribir ni reutilizar IDs.');
        }
        $reports = $this->validate($source, $target, $data, $marker !== null);
        if ($dryRun || collect($reports)->contains(fn ($report) => $report->hasProblems())) {
            return $reports;
        }
        // A durable intent permits retry after MySQL consumes AUTO_INCREMENT IDs
        // on rollback. This is not the Spatie bootstrap marker. Run in maintenance.
        if ($marker === null) {
            $target->table('inicializaciones_sistema')->insert(['clave' => self::MARKER, 'version' => $fingerprint, 'created_at' => now()]);
        }
        $target->transaction(function () use ($source, $target, $data, $fingerprint, $batch): void {
            if (hash('sha256', json_encode($this->snapshot($source), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)) !== $fingerprint) {
                throw new ImportSafetyException('El origen cambió; no se confirmó el catálogo.');
            }
            foreach ($data as $table => $rows) {
                $target->table($table)->orderBy('id')->lockForUpdate()->get();
            }
            $checked = $this->validate($source, $target, $data, true);
            if (collect($checked)->contains(fn ($report) => $report->hasProblems())) {
                throw new ImportSafetyException('Conflicto concurrente: catálogo revertido; revise y reejecute.');
            }
            foreach ($data as $table => $rows) {
                $ids = $target->table($table)->pluck('id')->all();
                $new = array_map(fn ($row) => $this->normalize($table, $row), array_values(array_filter($rows, fn ($row) => ! in_array($row['id'], $ids))));
                foreach (array_chunk($new, $batch) as $chunk) {
                    $target->table($table)->insert($chunk);
                }
            }
            if (hash('sha256', json_encode($this->snapshot($source), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)) !== $fingerprint) {
                throw new ImportSafetyException('El origen cambió durante las escrituras; catálogo revertido. Detenga los escritores del origen antes de reintentar.');
            }
        });
        foreach (self::MAP as $from => [$to]) {
            // DDL implicitly commits in MySQL. Only after all catalog rows commit.
            $minimum = max($this->nextId($source, $from), ((int) $source->table($from)->max('id')) + 1);
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

    private function snapshot(Connection $source): array
    {
        $data = [];
        foreach (self::MAP as $from => [$to, $map]) {
            $data[$to] = [];
            $query = $source->table($from)->select(array_values(array_diff(array_keys($map), ['Costo_Venta', 'Cantidad'])));
            foreach (['Costo_Venta', 'Cantidad'] as $decimal) {
                if (isset($map[$decimal])) {
                    $query->selectRaw('CAST(`'.$decimal.'` AS CHAR) AS `'.$decimal.'`');
                }
            }
            foreach ($query->orderBy('id')->cursor() as $raw) {
                $row = [];
                foreach ($map as $legacy => $native) {
                    $row[$native] = $raw->$legacy;
                }
                $data[$to][] = $row;
            }
        }

        return $data;
    }

    private function validate(Connection $source, Connection $target, array $data, bool $reserved): array
    {
        $reports = [];
        foreach (self::MAP as $from => [$table]) {
            $report = new ImportReport;
            $reports[$table] = $report;
            $report->sourceCount = count($data[$table]);
            $existing = $target->table($table)->get()->keyBy('id');
            $report->targetCount = $existing->count();
            $next = $this->nextId($target, $table);
            $report->sequence = ['origen_siguiente' => $this->nextId($source, $from), 'destino_siguiente' => $next];
            $unique = $table === 'productos' ? ['nombre' => 'Nombre', 'clave' => 'Clave'] : ($table === 'familias_productos' ? ['nombre' => 'Nombre'] : []);
            $duplicates = [];
            foreach ($unique as $field => $legacy) {
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
            foreach ($data[$table] as $raw) {
                $id = (int) $raw['id'];
                try {
                    $row = $this->normalize($table, $raw);
                } catch (\Throwable) {
                    $report->record('rechazados', $id, 'valor_o_decimal_incompatible');

                    continue;
                }
                $reason = null;
                foreach (self::REFERENCES[$table] ?? [] as $column => $parent) {
                    if (! in_array($row[$column], array_column($data[$parent], 'id'))) {
                        $reason = 'referencia_huerfana_'.$column;
                    }
                }
                if (isset($duplicates[$id])) {
                    $reason = 'duplicado_segun_collation_destino';
                }
                if ($reason) {
                    $report->record('rechazados', $id, $reason);

                    continue;
                }
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
                foreach ($unique as $field => $_) {
                    if ($target->table($table)->where($field, $row[$field])->where('id', '<>', $id)->exists()) {
                        $reason = 'unicidad_destino';
                    }
                }
                $report->record($reason ? 'conflictos' : 'pendientes', $id, $reason ?? '');
            }
        }

        return $reports;
    }

    public function normalize(string $table, array $row): array
    {
        $rules = ['id' => ['required', 'integer', 'between:1,2147483647']];
        foreach ($row as $field => $value) {
            if ($field === 'id') {
                continue;
            }
            if (in_array($field, ['costo_venta', 'cantidad'])) {
                $rules[$field] = ['required', 'numeric', new ExactProductDecimal];
            } elseif (in_array($field, ['nombre', 'clave', 'clave_pedido', 'bodega', 'piso', 'total', 'dlc', 'pedido', 'cvi', 'cvg']) && ! ($table === 'insumos' && $field === 'pedido')) {
                $max = match ($field) {
                    'nombre' => match ($table) {
                        'insumos', 'productos' => 100, 'conversiones_insumos' => 150, default => 50
                    },
                    'bodega', 'piso' => 200, 'cvi', 'cvg' => 20, 'clave_pedido' => 45, default => 50,
                };
                $rules[$field] = ['present', 'string', 'max:'.$max];
                if (! mb_check_encoding((string) $value, 'UTF-8')) {
                    throw new \InvalidArgumentException;
                }
            } else {
                $range = str_ends_with($field, '_id') || in_array($field, ['unidad_pedido', 'unidad_final']) ? '1,2147483647' : '-128,127';
                if (in_array($field, ['categoria_pedido_id', 'orden_captura_inventario'])) {
                    $range = '-32768,32767';
                }
                if ($field === 'categoria_quebranto_id') {
                    $range = '-128,127';
                }
                $rules[$field] = ['required', 'integer', 'between:'.$range];
            }
        }
        Validator::make($row, $rules)->validate();
        $result = [];
        foreach ($rules as $field => $rule) {
            $result[$field] = in_array($field, ['costo_venta', 'cantidad']) ? ExactProductDecimal::normalize($row[$field]) : (in_array('integer', $rule) ? (int) $row[$field] : $row[$field]);
        }

        ksort($result);

        return $result;
    }

    private function nextId(Connection $connection, string $table): int
    {
        $ddl = ((array) $connection->selectOne('SHOW CREATE TABLE `'.$table.'`'))['Create Table'] ?? '';
        if (! str_contains($ddl, 'AUTO_INCREMENT')) {
            throw new ImportSafetyException('No se pudo verificar AUTO_INCREMENT de '.$table);
        }

        return preg_match('/\bAUTO_INCREMENT=(\d+)/', $ddl, $matches) ? (int) $matches[1] : 1;
    }
}
