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
        return Ingredient::query()->where('Analisis_CV', 1)->where(function (EloquentBuilder $query): void {
            $query->where('pedido', 1)->orWhere('Pedido_Pasteleria', 1);
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
            $totals = DB::table('tbl_gastos as expense')
                ->join('tbl_insumos_gastos as item', 'item.id', '=', 'expense.insumo_gasto_id')
                ->whereBetween('expense.Fecha', [$from, $to])
                ->selectRaw('expense.plaza_id, item.insumo_id, SUM(expense.Importe) as amount, SUM(expense.Cantidad) as quantity')
                ->groupBy('expense.plaza_id', 'item.insumo_id');
            if (filled($filters['plaza'] ?? null)) {
                $totals->where('expense.plaza_id', $filters['plaza']);
            }
            $base = Ingredient::query()->from('tbl_insumos as ingredient')->toBase()
                ->crossJoin('tbl_plazas as plaza')
                ->leftJoinSub($totals, 'totals', function ($join): void {
                    $join->on('totals.insumo_id', '=', 'ingredient.id')->on('totals.plaza_id', '=', 'plaza.id');
                })
                ->where('ingredient.pedido', 0)->where('ingredient.Pedido_Pasteleria', 0)->where('ingredient.Analisis_CV', 1)
                ->select(['ingredient.id', 'ingredient.Nombre', 'plaza.Nombre as Plaza', 'plaza.id as plaza_id'])
                ->selectRaw('CASE WHEN COALESCE(totals.amount, 0) = 0 AND COALESCE(totals.quantity, 0) = 0 THEN 0 ELSE totals.amount / NULLIF(totals.quantity, 0) END as Cost');
            if (filled($filters['plaza'] ?? null)) {
                $base->where('plaza.id', $filters['plaza']);
            }
        } else {
            $base = self::orders()->select(['id', 'Nombre', 'Costo_Venta as Cost'])->toBase();
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
