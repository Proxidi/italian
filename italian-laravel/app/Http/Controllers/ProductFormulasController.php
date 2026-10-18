<?php

namespace App\Http\Controllers;

use App\Actions\Products\SaveFormula;
use App\Http\Requests\Products\FormulasReadRequest;
use App\Http\Requests\Products\SaveFormulaRequest;
use App\Models\Product;
use App\Models\ProductIngredient;
use App\Queries\ProductFormulas;
use App\Support\AuthenticatedNavigation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class ProductFormulasController extends Controller
{
    public function index(FormulasReadRequest $request, Product $product, AuthenticatedNavigation $navigation, ProductFormulas $query, ?ProductIngredient $formula = null): View
    {
        abort_unless($product->family()->exists(), 404);
        $lines = $query->lines($product);
        if ($formula !== null) {
            abort_unless($lines->firstWhere('id', $formula->id)?->editable, 409, 'La relación requiere revisión antes de editar.');
        }

        return view('products.formulas', [
            'layout' => $navigation->forUser($request->user('web')), 'product' => $product,
            'lines' => $lines, 'editing' => $formula, 'filters' => $request->filters(),
            'ingredients' => $query->eligibleIngredients()->with('initialUnit')->orderBy('id')->get(),
        ]);
    }

    public function store(SaveFormulaRequest $request, Product $product, SaveFormula $action): JsonResponse|RedirectResponse
    {
        $action->handle($product, $request->validated());

        return $this->saved($request, $product, 'Fórmula registrada correctamente!!');
    }

    public function update(SaveFormulaRequest $request, Product $product, ProductIngredient $formula, SaveFormula $action): JsonResponse|RedirectResponse
    {
        $action->handle($product, $request->validated(), $formula);

        return $this->saved($request, $product, 'Fórmula actualizada correctamente!!');
    }

    private function saved(SaveFormulaRequest $request, Product $product, string $message): JsonResponse|RedirectResponse
    {
        $url = route('products.formulas.index', ['product' => $product->id, ...$request->filters()]);

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'panel' => $url])
            : redirect($url)->with('catalog-success', $message);
    }
}
