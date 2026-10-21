<?php

namespace App\Http\Controllers;

use App\Http\Requests\Products\CostOfSaleReadRequest;
use App\Http\Requests\Products\UpdateCostOfSaleRequest;
use App\Models\Ingredient;
use App\Queries\CostOfSale;
use App\Support\AuthenticatedNavigation;
use App\Support\ListingResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class CostOfSaleController extends Controller
{
    public function index(CostOfSaleReadRequest $request, AuthenticatedNavigation $navigation, CostOfSale $costs, ?Ingredient $ingredient = null): View|Response
    {
        if ($ingredient !== null) {
            abort_unless(CostOfSale::orders()->withCatalogs()->where('tbl_insumos.id', $ingredient->id)->exists(), 404);
        }
        $filters = $request->filters();
        [$from, $to] = $costs->week($filters['date'] ?? null);
        $filters['date'] = $from;

        return ListingResponse::render($request, 'products.cost-of-sale', [
            'layout' => $navigation->forUser($request->user('web')), 'filters' => $filters,
            'records' => $costs->query($filters)->get(), 'ingredient' => $ingredient,
            'plazas' => DB::table('tbl_plazas')->orderBy('id')->get(['id', 'Nombre']),
            'from' => $from, 'to' => $to,
        ]);
    }

    public function update(UpdateCostOfSaleRequest $request, Ingredient $ingredient): JsonResponse|RedirectResponse
    {
        DB::transaction(function () use ($request, $ingredient): void {
            $current = CostOfSale::orders()->whereKey($ingredient->id)->lockForUpdate()->firstOrFail();
            // Do not allow an inconsistent catalog record through a forged/stale edit URL.
            abort_unless(Ingredient::query()->withCatalogs()->where('tbl_insumos.id', $current->id)->exists(), 409);
            $current->forceFill(['Costo_Venta' => $request->validated('cost')])->save();
        });
        $url = route('products.cost-of-sale.index', $request->filters());

        return $request->expectsJson()
            ? response()->json(['message' => 'Costo actualizado.', 'listing' => $url])
            : redirect($url)->with('cost-success', 'Costo actualizado.');
    }
}
