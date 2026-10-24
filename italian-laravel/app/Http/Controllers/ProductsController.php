<?php

namespace App\Http\Controllers;

use App\Exports\TabularSpreadsheet;
use App\Http\Requests\Products\SaveUnitRequest;
use App\Http\Requests\Products\UnitsReadRequest;
use App\Models\MeasurementUnit;
use App\Support\AuthenticatedNavigation;
use App\Support\ListingResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class ProductsController extends Controller
{
    public function index(Request $request, AuthenticatedNavigation $navigation): View
    {
        return view('products.index', ['layout' => $navigation->forUser($request->user('web'))]);
    }

    public function units(UnitsReadRequest $request, AuthenticatedNavigation $navigation): View|HttpResponse
    {
        return ListingResponse::render($request, 'products.units', [
            'layout' => $navigation->forUser($request->user('web')),
            'records' => $this->query($request)->paginate(50)->withQueryString(),
            'search' => $request->validated('q') ?? '',
            'direction' => $request->validated('direction') ?? 'asc',
        ]);
    }

    public function store(SaveUnitRequest $request): RedirectResponse
    {
        MeasurementUnit::create(['nombre' => $request->validated('nombre')]);

        return to_route('products.units.index')->with('unit-success', 'Unidad registrada correctamente!!');
    }

    public function update(SaveUnitRequest $request, MeasurementUnit $unit): RedirectResponse
    {
        // One atomic row update. References and conversion data remain untouched.
        $unit->update(['nombre' => $request->validated('nombre')]);

        return to_route('products.units.index')->with('unit-success', 'Unidad actualizada correctamente!!')->with('unit-updated', true);
    }

    public function export(UnitsReadRequest $request): Response
    {
        $columns = [['field' => 'Nombre', 'label' => 'Nombre']];
        if ($request->validated('format') === 'pdf') {
            return Pdf::loadView('exports.table', [
                'title' => 'Unidades Insumos', 'date' => null, 'columns' => $columns,
                'records' => $this->query($request)->lazy(50),
            ])->setPaper('a4')->setOptions([
                'isRemoteEnabled' => false, 'isPhpEnabled' => false, 'isJavascriptEnabled' => false,
                'defaultFont' => 'DejaVu Sans',
            ])->download('unidades.pdf')->header('Cache-Control', 'private, no-store');
        }

        return Excel::download(new TabularSpreadsheet($this->query($request), $columns, 'Unidades Insumos'), 'unidades.xlsx', headers: ['Cache-Control' => 'private, no-store']);
    }

    private function query(UnitsReadRequest $request): Builder
    {
        $query = MeasurementUnit::query()->select(['id', 'nombre as Nombre'])->toBase();
        if (filled($search = $request->validated('q'))) {
            $query->whereRaw("nombre LIKE ? ESCAPE '!'", ['%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%']);
        }

        return $query->orderBy('nombre', $request->validated('direction') ?? 'asc')->orderBy('id');
    }
}
