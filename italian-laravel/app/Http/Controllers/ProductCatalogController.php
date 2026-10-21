<?php

namespace App\Http\Controllers;

use App\Exports\TabularSpreadsheet;
use App\Http\Requests\Products\ProductsReadRequest;
use App\Http\Requests\Products\SaveProductRequest;
use App\Models\Product;
use App\Models\ProductFamily;
use App\Queries\ProductsListing;
use App\Support\AuthenticatedNavigation;
use App\Support\ListingResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class ProductCatalogController extends Controller
{
    public function index(ProductsReadRequest $request, AuthenticatedNavigation $navigation, ProductsListing $listing): View|HttpResponse
    {
        return ListingResponse::render($request, 'products.catalog', [
            'layout' => $navigation->forUser($request->user('web')),
            'records' => $listing->query($request->filters())->paginate(80)->withQueryString(),
            'columns' => ProductsListing::COLUMNS, 'filters' => $request->filters(),
            'families' => ProductFamily::query()->orderBy('id')->get(),
        ]);
    }

    public function store(SaveProductRequest $request): JsonResponse|RedirectResponse
    {
        try {
            DB::transaction(function () use ($request): void {
                ProductFamily::query()->lockForUpdate()->findOrFail($request->validated('familia_id'));
                Product::create(['Nombre' => $request->validated('nombre'), 'Clave' => $request->validated('clave'), 'familia_id' => $request->validated('familia_id'), 'Estado' => 1]);
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['nombre' => 'El nombre o la clave ya se encuentran registrados.']);
        }
        $message = 'Producto registrado correctamente!!';
        $url = route('products.catalog.index', $request->filters());

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'redirect' => $url])
            : redirect($url)->with('catalog-success', $message);
    }

    public function export(ProductsReadRequest $request, ProductsListing $listing): Response
    {
        $query = $listing->query($request->filters());
        if ($request->validated('format') === 'pdf') {
            return Pdf::loadView('exports.table', [
                'title' => 'Datos', 'date' => null, 'columns' => ProductsListing::COLUMNS, 'records' => $query->lazy(300),
            ])->setPaper('a4')->setOptions(['isRemoteEnabled' => false, 'isPhpEnabled' => false, 'isJavascriptEnabled' => false, 'defaultFont' => 'DejaVu Sans'])
                ->download('productos.pdf')->header('Cache-Control', 'private, no-store');
        }

        return Excel::download(new TabularSpreadsheet($query, ProductsListing::COLUMNS, 'Datos'), 'productos.xlsx', headers: ['Cache-Control' => 'private, no-store']);
    }
}
