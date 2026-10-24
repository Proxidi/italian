<?php

namespace App\Http\Controllers;

use App\Actions\Products\UpdateIngredientConversion;
use App\Exports\TabularSpreadsheet;
use App\Http\Requests\Products\IngredientsReadRequest;
use App\Http\Requests\Products\SaveIngredientConversionRequest;
use App\Models\Ingredient;
use App\Models\IngredientConversion;
use App\Queries\IngredientsListing;
use App\Support\AuthenticatedNavigation;
use App\Support\ListingResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class IngredientsController extends Controller
{
    public function index(IngredientsReadRequest $request, AuthenticatedNavigation $navigation, IngredientsListing $listing, ?Ingredient $ingredient = null): View|HttpResponse
    {
        if ($ingredient !== null) {
            abort_unless(Ingredient::query()->withCatalogs()->where('insumos.id', $ingredient->id)->exists(), 404);
        }
        $filters = $request->safe()->only(['q', 'sort', 'direction', 'page']);

        return ListingResponse::render($request, 'products.ingredients', [
            'layout' => $navigation->forUser($request->user('web')),
            'records' => $listing->query($filters)->paginate(300)->withQueryString(),
            'filters' => $filters, 'columns' => IngredientsListing::COLUMNS,
            'ingredient' => $ingredient,
            'conversions' => $ingredient?->conversions()->orderBy('id')->get() ?? collect(),
        ]);
    }

    public function update(SaveIngredientConversionRequest $request, Ingredient $ingredient, IngredientConversion $conversion, UpdateIngredientConversion $action): RedirectResponse|JsonResponse
    {
        $action->handle($ingredient, $conversion, $request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Insumo actualizado!!']);
        }

        return to_route('products.ingredients.index', $request->safe()->only(['q', 'sort', 'direction', 'page']))->with('ingredient-success', 'Insumo actualizado!!');
    }

    public function export(IngredientsReadRequest $request, IngredientsListing $listing): Response
    {
        $query = $listing->query($request->validated());
        if ($request->validated('format') === 'pdf') {
            return Pdf::loadView('exports.table', [
                'title' => 'Datos Insumos', 'date' => null, 'columns' => IngredientsListing::COLUMNS, 'records' => $query->lazy(300),
            ])->setPaper('a4')->setOptions([
                'isRemoteEnabled' => false, 'isPhpEnabled' => false, 'isJavascriptEnabled' => false, 'defaultFont' => 'DejaVu Sans',
            ])->download('insumos.pdf')->header('Cache-Control', 'private, no-store');
        }

        return Excel::download(new TabularSpreadsheet($query, IngredientsListing::COLUMNS, 'Datos Insumos'), 'insumos.xlsx', headers: ['Cache-Control' => 'private, no-store']);
    }
}
