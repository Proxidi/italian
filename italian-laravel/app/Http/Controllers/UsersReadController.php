<?php

namespace App\Http\Controllers;

use App\Actions\Users\SaveUser;
use App\Exports\UsersReadSpreadsheet;
use App\Http\Requests\Users\AccessLogRequest;
use App\Http\Requests\Users\DirectoryRequest;
use App\Models\User;
use App\Support\AuthenticatedNavigation;
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
            'field' => 'Nombre',
            'label' => 'Nombre',
            'sql' => 'tbl_usuarios.Nombre',
        ],
        'age' => [
            'field' => 'Edad',
            'label' => 'Edad',
            'sql' => 'tbl_usuarios.Edad',
        ],
        'email' => [
            'field' => 'Email',
            'label' => 'Email',
            'sql' => 'tbl_usuarios.Email',
        ],
        'username' => [
            'field' => 'Usuario',
            'label' => 'Usuario',
            'sql' => 'tbl_usuarios.Usuario',
        ],
        'plaza' => [
            'field' => 'Plaza',
            'label' => 'Plaza',
            'sql' => 'p.Nombre',
        ],
    ];

    private const ACCESS_COLUMNS = [
        'date' => [
            'field' => 'Fecha',
            'label' => 'Fecha',
            'sql' => 'ba.Fecha',
        ],
        'time' => [
            'field' => 'Hora',
            'label' => 'Hora',
            'sql' => 'ba.Hora',
        ],
        'plaza' => [
            'field' => 'Plaza',
            'label' => 'Plaza',
            'sql' => 'p.Nombre',
        ],
        'username' => [
            'field' => 'Usuario',
            'label' => 'Usuario',
            'sql' => 'u.Nombre',
        ],
        'role' => [
            'field' => 'Rol',
            'label' => 'Rol',
            'sql' => 'r.Nombre',
        ],
    ];

    public function directory(
        DirectoryRequest $request,
        AuthenticatedNavigation $navigation,
    ): View {
        $filters = $request->filters();

        return view('usuarios.table', [
            'layout' => $navigation->forUser($request->user('web')),
            'title' => 'Usuarios',
            'description' => 'Directorio de consulta',
            'columns' => self::DIRECTORY_COLUMNS,
            'filters' => $filters,
            'indexRoute' => 'usuarios.index',
            'exportRoute' => 'usuarios.export',
            'roles' => DB::table('tbl_roles')->whereIn('id', array_keys(SaveUser::ROLES))->get(['id', 'Nombre']),
            'plazas' => DB::table('tbl_plazas')->get(['id', 'Nombre']),
            'records' => $this->directoryQuery($filters)
                ->addSelect('tbl_usuarios.id')
                ->paginate(100)
                ->appends($filters),
        ]);
    }

    public function accessLog(
        AccessLogRequest $request,
        AuthenticatedNavigation $navigation,
    ): View {
        $filters = $request->filters();

        return view('usuarios.table', [
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
        /*
         * Conserva los joins internos del legacy. El rol no se muestra,
         * pero un usuario sin plaza o rol legacy relacionado tampoco aparece.
         *
         * Selecciona los cinco datos exportables. El listado añade el ID para editar.
         */
        $query = User::query()
            ->join('tbl_roles as r', 'tbl_usuarios.rol_id', '=', 'r.id')
            ->join('tbl_plazas as p', 'tbl_usuarios.plaza_id', '=', 'p.id')
            ->select([
                'tbl_usuarios.Nombre',
                'tbl_usuarios.Edad',
                'tbl_usuarios.Email',
                'tbl_usuarios.Usuario',
                'p.Nombre as Plaza',
            ])
            ->toBase();

        $this->applySearchAndSort(
            $query,
            $filters,
            self::DIRECTORY_COLUMNS,
        );

        /*
         * El legacy ordena por u.id ASC. Si el usuario solicita una columna,
         * el ID conserva un desempate estable sin mostrarse en la consulta.
         */
        return $query->orderBy(
            'tbl_usuarios.id',
            $filters['sort'] === 'default'
                ? $filters['direction']
                : 'asc',
        );
    }

    private function accessQuery(array $filters): Builder
    {
        $query = DB::table('tbl_bitacora_accesos as ba')
            ->join('tbl_usuarios as u', 'ba.usuario_id', '=', 'u.id')
            ->join('tbl_plazas as p', 'ba.plaza_id', '=', 'p.id')
            ->join('tbl_roles as r', 'u.rol_id', '=', 'r.id')
            ->select([
                'ba.Fecha',
                'ba.Hora',
                'p.Nombre as Plaza',
                'u.Nombre as Usuario',
                'r.Nombre as Rol',
            ])
            /*
             * Conserva la semántica legacy: fecha exacta, sin rango,
             * y plaza tomada de la fila de acceso.
             */
            ->where('ba.Fecha', $filters['date']);

        $this->applySearchAndSort(
            $query,
            $filters,
            self::ACCESS_COLUMNS,
        );

        $direction = $filters['sort'] === 'default'
            ? $filters['direction']
            : 'desc';

        /*
         * Legacy: Fecha DESC, Hora DESC.
         * El ID es un desempate para paginación estable.
         */
        return $query
            ->orderBy('ba.Fecha', $direction)
            ->orderBy('ba.Hora', $direction)
            ->orderBy('ba.id', 'desc');
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
            $query->orderBy(
                $columns[$filters['sort']]['sql'],
                $filters['direction'],
            );
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
