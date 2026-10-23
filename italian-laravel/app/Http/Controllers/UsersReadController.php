<?php

namespace App\Http\Controllers;

use App\Exports\UsersReadSpreadsheet;
use App\Http\Requests\Users\AccessLogRequest;
use App\Http\Requests\Users\DirectoryRequest;
use App\Models\Acceso;
use App\Models\Usuario;
use App\Support\AuthenticatedNavigation;
use App\Support\CorePermissions;
use App\Support\ListingResponse;
use App\Support\UserCatalogs;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UsersReadController extends Controller
{
    private const DIRECTORY_COLUMNS = [
        'name' => [
            'field' => 'nombre',
            'label' => 'Nombre',
            'sql' => 'usuarios.nombre',
        ],
        'age' => [
            'field' => 'edad',
            'label' => 'Edad',
            'sql' => 'usuarios.edad',
        ],
        'email' => [
            'field' => 'email',
            'label' => 'Email',
            'sql' => 'usuarios.email',
        ],
        'username' => [
            'field' => 'usuario',
            'label' => 'Usuario',
            'sql' => 'usuarios.usuario',
        ],
        'plaza' => [
            'field' => 'plaza',
            'label' => 'Plaza',
            'sql' => 'p.nombre',
        ],
    ];

    private const ACCESS_COLUMNS = [
        'date' => [
            'field' => 'fecha',
            'label' => 'Fecha',
            'sql' => 'ba.fecha',
        ],
        'time' => [
            'field' => 'hora',
            'label' => 'Hora',
            'sql' => 'ba.hora',
        ],
        'plaza' => [
            'field' => 'plaza',
            'label' => 'Plaza',
            'sql' => 'p.nombre',
        ],
        'username' => [
            'field' => 'usuario',
            'label' => 'Usuario',
            'sql' => 'u.nombre',
        ],
        'role' => [
            'field' => 'rol',
            'label' => 'Rol',
            'sql' => "COALESCE(actual.rol, 'Sin rol único')",
        ],
    ];

    public function directory(
        DirectoryRequest $request,
        AuthenticatedNavigation $navigation,
    ): Response {
        $filters = $request->filters();

        return ListingResponse::render($request, 'usuarios.table', [
            'layout' => $navigation->forUser($request->user('web')),
            'title' => 'Usuarios',
            'description' => 'Directorio de consulta',
            'columns' => self::DIRECTORY_COLUMNS,
            'filters' => $filters,
            'indexRoute' => 'usuarios.index',
            'exportRoute' => 'usuarios.export',
            ...UserCatalogs::forForm(),
            'records' => $this->directoryQuery($filters)
                ->addSelect('usuarios.id')
                ->paginate(100)
                ->appends($filters),
        ]);
    }

    public function accessLog(
        AccessLogRequest $request,
        AuthenticatedNavigation $navigation,
    ): Response {
        $filters = $request->filters();

        return ListingResponse::render($request, 'usuarios.table', [
            'layout' => $navigation->forUser($request->user('web')),
            'title' => 'Bitácora de Accesos',
            'description' => 'Accesos registrados en la fecha seleccionada',
            'columns' => self::ACCESS_COLUMNS,
            'filters' => $filters,
            'indexRoute' => 'usuarios.accesos',
            'exportRoute' => 'usuarios.accesos.export',
            'records' => $this->accessQuery($filters)
                ->paginate(200)
                ->appends($filters),
        ]);
    }

    public function exportDirectory(
        DirectoryRequest $request,
    ): View|StreamedResponse|Response|BinaryFileResponse {
        return $this->export(
            $this->directoryQuery($request->filters()),
            self::DIRECTORY_COLUMNS,
            'Usuarios',
            'usuarios.csv',
            $request->validated('format', 'csv'),
            null,
        );
    }

    public function exportAccessLog(
        AccessLogRequest $request,
    ): View|StreamedResponse|Response|BinaryFileResponse {
        $filters = $request->filters();

        return $this->export(
            $this->accessQuery($filters),
            self::ACCESS_COLUMNS,
            'Bitácora de Accesos',
            'bitacora-accesos-'.$filters['date'].'.csv',
            $request->validated('format', 'csv'),
            $filters['date'],
        );
    }

    private function directoryQuery(array $filters): Builder
    {
        // Administrative listing includes accounts without a role, so their
        // assignment can be corrected instead of silently hiding the account.
        $query = Usuario::query()->leftJoin('plazas as p', 'usuarios.plaza_id', '=', 'p.id')
            ->select(['usuarios.nombre', 'usuarios.edad', 'usuarios.email', 'usuarios.usuario', 'p.nombre as plaza'])->toBase();
        $this->applySearchAndSort($query, $filters, self::DIRECTORY_COLUMNS);

        return $query->orderBy('usuarios.id', $filters['sort'] === 'default' ? $filters['direction'] : 'asc');
    }

    private function accessQuery(array $filters): Builder
    {
        $labels = CorePermissions::labels();
        $case = 'CASE r.name '.implode(' ', array_fill(0, count($labels), 'WHEN ? THEN ?')).' ELSE r.name END';
        $bindings = [];
        foreach ($labels as $name => $label) {
            array_push($bindings, $name, $label);
        }
        // One row per user even if malformed role assignments exist. No
        // historical role is invented and an inconsistent account is visible.
        $roles = DB::table('model_has_roles as m')->join('roles as r', 'r.id', '=', 'm.role_id')
            ->where('m.model_type', 'usuario')->where('r.guard_name', 'web')->select('m.model_id')
            ->selectRaw('CASE WHEN COUNT(*) = 1 THEN MAX('.$case.') ELSE ? END AS rol', [...$bindings, 'Sin rol único'])
            ->groupBy('m.model_id');
        $query = Acceso::query()->from('bitacora_accesos as ba')
            ->join('usuarios as u', 'ba.usuario_id', '=', 'u.id')
            ->join('plazas as p', 'ba.plaza_id', '=', 'p.id')
            ->leftJoinSub($roles, 'actual', 'actual.model_id', '=', 'u.id')
            ->select(['ba.fecha', 'ba.hora', 'p.nombre as plaza', 'u.nombre as usuario'])
            ->selectRaw("COALESCE(actual.rol, 'Sin rol único') AS rol")
            ->where('ba.fecha', $filters['date'])->toBase();
        $this->applySearchAndSort($query, $filters, self::ACCESS_COLUMNS);
        $direction = $filters['sort'] === 'default' ? $filters['direction'] : 'desc';

        return $query->orderBy('ba.fecha', $direction)->orderBy('ba.hora', $direction)->orderBy('ba.id', 'desc');
    }

    private function applySearchAndSort(
        Builder $query,
        array $filters,
        array $columns,
    ): void {
        if ($filters['q'] !== '') {
            /*
             * %, _ y ! se interpretan literalmente para que la búsqueda no
             * genere patrones SQL inesperados.
             */
            $pattern = '%'.str_replace(
                ['!', '%', '_'],
                ['!!', '!%', '!_'],
                $filters['q'],
            ).'%';

            $query->where(function (Builder $search) use (
                $columns,
                $pattern,
            ): void {
                foreach ($columns as $column) {
                    /*
                     * Los identificadores SQL vienen exclusivamente de
                     * constantes internas, nunca de parámetros del usuario.
                     */
                    $search->orWhereRaw(
                        $column['sql']." LIKE ? ESCAPE '!'",
                        [$pattern],
                    );
                }
            });
        }

        if ($filters['sort'] !== 'default') {
            $query->orderByRaw($columns[$filters['sort']]['sql'].' '.$filters['direction']);
        }
    }

    private function export(
        Builder $query,
        array $columns,
        string $title,
        string $filename,
        string $format,
        ?string $date,
    ): View|StreamedResponse|Response|BinaryFileResponse {
        $baseFilename = pathinfo($filename, PATHINFO_FILENAME);

        if ($format === 'xlsx') {
            return Excel::download(
                new UsersReadSpreadsheet($query, $columns, $title),
                $baseFilename.'.xlsx',
                \Maatwebsite\Excel\Excel::XLSX,
                ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'],
            );
        }

        if ($format === 'pdf') {
            return Pdf::loadView('usuarios.pdf', [
                'title' => $title,
                'date' => $date,
                'columns' => $columns,
                'records' => $query->lazy(500),
            ])->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isRemoteEnabled' => false,
                'isPhpEnabled' => false,
                'isJavascriptEnabled' => false,
            ])->setPaper('a4', 'portrait')
                ->download($baseFilename.'.pdf')
                ->withHeaders([
                    'Cache-Control' => 'private, no-store',
                    'X-Content-Type-Options' => 'nosniff',
                ]);
        }

        if ($format === 'print') {
            return view('usuarios.print', [
                'title' => $title,
                'date' => $date,
                'columns' => $columns,
                'records' => $query->cursor(),
            ]);
        }

        return response()->streamDownload(
            function () use ($query, $columns): void {
                $output = fopen('php://output', 'wb');

                try {
                    /*
                     * BOM UTF-8 y punto y coma: compatibles con hojas de
                     * cálculo configuradas para español.
                     */
                    fwrite($output, "\xEF\xBB\xBF");

                    fputcsv(
                        $output,
                        array_column($columns, 'label'),
                        ';',
                        '"',
                        '',
                    );

                    foreach ($query->cursor() as $record) {
                        $row = [];

                        foreach ($columns as $column) {
                            $value = (string) (
                                $record->{$column['field']} ?? ''
                            );

                            /*
                             * Evita que valores de base de datos se interpreten
                             * como fórmulas al abrir el CSV en una hoja.
                             */
                            $row[] = preg_match(
                                '/^[\s]*[=+\-@]/u',
                                $value,
                            )
                                ? "'".$value
                                : $value;
                        }

                        fputcsv($output, $row, ';', '"', '');
                    }
                } finally {
                    fclose($output);
                }
            },
            $filename,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'private, no-store',
            ],
        );
    }
}
