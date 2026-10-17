<?php

namespace App\Http\Controllers;

use App\Exports\TabularSpreadsheet;
use App\Http\Requests\Configuration\ConfigurationReadRequest;
use App\Http\Requests\Configuration\UpdateNotificationDateRequest;
use App\Http\Requests\Configuration\UpdateUploadWindowRequest;
use App\Queries\ConfigurationSettings;
use App\Support\AuthenticatedNavigation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class ConfigurationController extends Controller
{
    public function __construct(private ConfigurationSettings $settings) {}

    public function index(Request $request, AuthenticatedNavigation $navigation): View
    {
        $cards = [];

        foreach ([
            ['configuration.upload-window.view', 'configuration.dates', 'CORTE & DLC', 'CORTE & DLC', 'calendar.png'],
            ['configuration.notifications.view', 'configuration.notifications', 'Notificaciones', 'Editar Notificaciones', 'notification.png'],
        ] as [$permission, $route, $label, $subtitle, $image]) {
            if ($request->user('web')->can($permission)) {
                $cards[] = compact('route', 'label', 'subtitle', 'image');
            }
        }

        return view('configuration.index', [
            'layout' => $navigation->forUser($request->user('web')),
            'cards' => $cards,
        ]);
    }

    public function dates(ConfigurationReadRequest $request, AuthenticatedNavigation $navigation): View
    {
        return $this->listing($request, $navigation, 'dates');
    }

    public function notifications(ConfigurationReadRequest $request, AuthenticatedNavigation $navigation): View
    {
        return $this->listing($request, $navigation, 'notifications');
    }

    public function updateDates(UpdateUploadWindowRequest $request): RedirectResponse
    {
        $this->settings->updateDays($request->integer('setting_id'), $request->integer('numero_dias_valor'));

        return to_route('configuration.dates')->with('configuration-success', 'Rango correctamente actualizado!!!');
    }

    public function updateNotifications(UpdateNotificationDateRequest $request): RedirectResponse
    {
        $this->settings->updateNotificationDate($request->integer('setting_id'), $request->validated('fecha'));

        return to_route('configuration.notifications')->with('configuration-success', 'Fecha de notificaciones actualizada.');
    }

    public function exportDates(ConfigurationReadRequest $request): Response
    {
        return $this->export($request, 'dates');
    }

    public function exportNotifications(ConfigurationReadRequest $request): Response
    {
        return $this->export($request, 'notifications');
    }

    private function listing(ConfigurationReadRequest $request, AuthenticatedNavigation $navigation, string $section): View
    {
        $search = $request->validated('q') ?? '';

        return view('configuration.list', [
            'layout' => $navigation->forUser($request->user('web')),
            'section' => $section,
            'columns' => ConfigurationSettings::SECTIONS[$section]['columns'],
            'records' => $this->settings->query($section, $search, $request->validated('sort') ?? 'id', $request->validated('direction') ?? 'asc')->paginate(80)->withQueryString(),
            // Read the whole configuration even when the displayed rows are filtered.
            'singleton' => $this->settings->singleton($section),
            'search' => $search,
            'sort' => $request->validated('sort') ?? 'id',
            'direction' => $request->validated('direction') ?? 'asc',
        ]);
    }

    private function export(ConfigurationReadRequest $request, string $section): Response
    {
        $query = $this->settings->query($section, $request->validated('q') ?? '', $request->validated('sort') ?? 'id', $request->validated('direction') ?? 'asc');
        $columns = ConfigurationSettings::SECTIONS[$section]['columns'];
        $filename = $section === 'dates' ? 'corte-dlc' : 'notificaciones';

        if ($request->validated('format') === 'pdf') {
            return Pdf::loadView('exports.table', [
                'title' => 'Datos', 'date' => null, 'columns' => $columns, 'records' => $query->lazy(80),
            ])->setPaper('a4')->setOptions([
                'isRemoteEnabled' => false, 'isPhpEnabled' => false, 'isJavascriptEnabled' => false,
                'defaultFont' => 'DejaVu Sans',
            ])->download($filename.'.pdf')->header('Cache-Control', 'private, no-store');
        }

        return Excel::download(new TabularSpreadsheet($query, $columns, 'Datos'), $filename.'.xlsx', headers: [
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
