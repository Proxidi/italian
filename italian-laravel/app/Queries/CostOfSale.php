<?php

namespace App\Queries;

use App\Models\Ingredient;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class CostOfSale
{
    public static function orders(): EloquentBuilder
    {
        return Ingredient::query()->where('analisis_cv', 1)->where(function (EloquentBuilder $query): void {
            $query->where('pedido', 1)->orWhere('pedido_pasteleria', 1);
        });
    }

    public function week(?string $date): array
    {
        $day = $date ? CarbonImmutable::parse($date) : CarbonImmutable::today();

        return [$day->startOfWeek(CarbonImmutable::MONDAY)->toDateString(), $day->endOfWeek(CarbonImmutable::SUNDAY)->toDateString()];
    }

    public function query(array $filters): Builder
    {
        $remittances = ($filters['mode'] ?? 'orders') === 'remittances';
        if ($remittances) {
            [$from, $to] = $this->week($filters['date'] ?? null);
            $totals = DB::table('gastos as g')
                ->join('conceptos_gastos as c', 'c.id', '=', 'g.concepto_gasto_id')
                ->whereBetween('g.fecha', [$from, $to])
                ->when(filled($filters['plaza'] ?? null), fn (Builder $q) => $q->where('g.plaza_id', $filters['plaza']))
                ->selectRaw('g.plaza_id, c.insumo_id, SUM(g.importe) AS importe, SUM(g.cantidad) AS cantidad')
                ->groupBy('g.plaza_id', 'c.insumo_id');
            $numerator = DB::connection()->getDriverName() === 'sqlite' ? 't.importe * 1.0' : 't.importe';
            $base = DB::table('insumos as i')->crossJoin('plazas as p')
                ->leftJoinSub($totals, 't', fn ($join) => $join->on('t.insumo_id', '=', 'i.id')->on('t.plaza_id', '=', 'p.id'))
                ->where('i.analisis_cv', 1)->where('i.pedido', 0)->where('i.pedido_pasteleria', 0)
                ->when(filled($filters['plaza'] ?? null), fn (Builder $q) => $q->where('p.id', $filters['plaza']))
                ->select(['i.id', 'i.nombre as Nombre', 'p.id as plaza_id', 'p.nombre as Plaza'])
                ->selectRaw('CASE WHEN COALESCE(t.importe, 0) = 0 AND COALESCE(t.cantidad, 0) = 0 THEN 0 WHEN t.cantidad = 0 THEN NULL ELSE '.$numerator.' / NULLIF(t.cantidad, 0) END AS Cost');
        } else {
            $base = self::orders()->select(['id', 'nombre as Nombre', 'costo_venta as Cost'])->toBase();
        }
        // Apply filtering/ordering to the computed cost, without N+1 queries or PHP aggregation.
        $query = DB::query()->fromSub($base, 'costs')->select('costs.*');
        if (filled($search = $filters['q'] ?? null)) {
            $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';
            $decimals = $remittances ? 3 : 2;
            $formatted = DB::connection()->getDriverName() === 'sqlite'
                ? "printf('$%,.".$decimals."f', Cost)"
                : "CONCAT('$', FORMAT(Cost, ".$decimals.", 'en_US'))";
            $query->where(function (Builder $query) use ($pattern, $remittances, $formatted): void {
                foreach ($remittances ? ['id', 'Nombre', 'Plaza', 'Cost'] : ['id', 'Nombre', 'Cost'] as $column) {
                    $query->orWhereRaw($column." LIKE ? ESCAPE '!'", [$pattern]);
                }
                $query->orWhereRaw('(Cost IS NOT NULL AND '.$formatted." LIKE ? ESCAPE '!')", [$pattern]);
            });
        }
        $sort = $filters['sort'] ?? 'id';
        if (! $remittances && $sort === 'Plaza') {
            $sort = 'id';
        }

        return $query->orderBy($sort, $filters['direction'] ?? 'asc')->orderBy('id')
            ->when($remittances, fn (Builder $query) => $query->orderBy('plaza_id'));
    }
}
