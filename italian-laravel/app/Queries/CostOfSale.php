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
            // Stage 6 must supply migrated expense history before reactivating
            // SUM(importe)/SUM(cantidad). Never substitute stored order costs.
            return DB::table('insumos')->select(['id', 'nombre as Nombre'])->whereRaw('1 = 0');
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
