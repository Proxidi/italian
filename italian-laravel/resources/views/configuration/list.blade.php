@extends('configuration.layout')

@section('configuration-content')
    @php
        $isDates = $section === 'dates';
        $route = $isDates ? 'configuration.dates' : 'configuration.notifications';
        $updatePermission = $isDates ? 'configuration.upload-window.update' : 'configuration.notifications.update';
    @endphp
    <header class="configuration-heading">
        <h1>{{ $isDates ? 'Subida de archivos Corte & DLC' : 'Sistema de Notificaciones' }} <small>{{ $isDates ? 'Corte & DLC' : 'Notificaciones' }}</small></h1>
        @can('configuration.view')
            <a href="{{ route('configuration.index') }}">Volver al Panel de Configuración <span aria-hidden="true">➜</span></a>
        @else
            <a href="{{ route('inicio') }}">Volver al Panel de Inicio <span aria-hidden="true">➜</span></a>
        @endcan
    </header>

    @if (session('configuration-success') && ! $errors->any())
        <div class="configuration-success" role="status" data-configuration-success><span aria-hidden="true">✓</span> {{ session('configuration-success') }}</div>
    @endif
    <section class="configuration-box" aria-label="{{ $isDates ? 'Corte & DLC' : 'Notificaciones' }}">
        @if ($errors->any())
            <div class="configuration-errors" role="alert"><p>No se guardaron los cambios:</p><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        @if ($singleton === null)
            <p class="configuration-warning" role="status">La actualización no está disponible: debe existir un único registro de configuración. Consulta al equipo antes de modificar estos datos.</p>
        @endif

        @unless ($isDates)
            <form class="configuration-date-form" action="{{ route($route.'.update') }}" method="POST" data-configuration-form>
                @csrf @method('PUT')
                <input type="hidden" name="setting_id" value="{{ $singleton->id ?? '' }}">
                <label for="notification-date">Fecha:</label>
                <input class="configuration-input" id="notification-date" name="fecha" type="date" min="1000-01-01" max="9999-12-31" value="{{ old('fecha') }}" required @disabled($singleton === null || !auth('web')->user()->can($updatePermission))>
                <span>Actualizar Fecha:</span>
                <button class="configuration-button configuration-button--green" type="submit" @disabled($singleton === null || !auth('web')->user()->can($updatePermission))>Actualizar</button>
            </form>
        @endunless

        <div class="configuration-toolbar">
            <div class="configuration-tools" aria-label="Exportar datos">
                <a class="configuration-button" href="{{ route($route.'.export', ['q' => $search, 'sort' => $sort, 'direction' => $direction, 'format' => 'xlsx']) }}">Excel</a>
                <a class="configuration-button" href="{{ route($route.'.export', ['q' => $search, 'sort' => $sort, 'direction' => $direction, 'format' => 'pdf']) }}">PDF</a>
                <button class="configuration-button" type="button" data-copy-configuration>Copy</button>
                <span class="configuration-sr-only" role="status" data-copy-configuration-status></span>
            </div>
            <form class="configuration-search" action="{{ route($route) }}" method="GET">
                <input type="hidden" name="sort" value="{{ $sort }}"><input type="hidden" name="direction" value="{{ $direction }}">
                <label for="configuration-search">Buscar:</label>
                <input class="configuration-input" id="configuration-search" name="q" type="search" maxlength="100" value="{{ $search }}" placeholder="Buscar registros" aria-describedby="configuration-search-help">
                <span class="configuration-sr-only" id="configuration-search-help">Presiona Enter para buscar.</span>
                <button class="configuration-sr-only" type="submit">Buscar</button>
            </form>
        </div>
        <div class="configuration-scroll" tabindex="0" role="region" aria-label="Datos de configuración">
            <table class="configuration-table" data-configuration-table>
                <thead><tr>@foreach ($columns as $column)<th scope="col" aria-sort="{{ $sort === $column['field'] ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}"><a href="{{ route($route, ['q' => $search, 'sort' => $column['field'], 'direction' => $sort === $column['field'] && $direction === 'asc' ? 'desc' : 'asc']) }}">{{ $column['label'] }}</a></th>@endforeach @if ($isDates)<th scope="col" data-copy-exclude>Editar</th>@endif</tr></thead>
                <tbody>
                @forelse ($records as $record)
                    <tr data-configuration-row>
                        @foreach ($columns as $column)<td>{{ $record->{$column['field']} }}</td>@endforeach
                        @if ($isDates)
                            <td data-copy-exclude><button class="configuration-button configuration-button--green" type="button" data-open-days aria-label="Editar rango de días" @disabled($singleton === null || !auth('web')->user()->can($updatePermission))><span aria-hidden="true">✎</span></button></td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ count($columns) + ($isDates ? 1 : 0) }}">No se encontraron registros.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <footer class="configuration-pagination">
            <p role="status">@if ($records->total())Mostrando registros de {{ $records->firstItem() ?? 0 }} al {{ $records->lastItem() ?? 0 }} de un total de {{ $records->total() }} registros @else No existen registros. @endif</p>
            <nav aria-label="Paginación">@if ($records->previousPageUrl())<a href="{{ $records->previousPageUrl() }}">Anterior</a>@else<span>Anterior</span>@endif <strong aria-current="page">{{ $records->currentPage() }}</strong> @if ($records->nextPageUrl())<a href="{{ $records->nextPageUrl() }}">Siguiente</a>@else<span>Siguiente</span>@endif</nav>
        </footer>
    </section>

    @if ($isDates && $singleton !== null)
        @can($updatePermission)
            <dialog class="configuration-dialog" data-days-dialog @if ($errors->hasAny(['setting_id', 'numero_dias_valor', 'configuration'])) data-reopen @endif aria-labelledby="days-title">
                <header><h2 id="days-title">Edición del rango de días para la subida de Corte & DLC</h2><button type="button" data-close-days aria-label="Cerrar">×</button></header>
                <form action="{{ route($route.'.update') }}" method="POST" data-configuration-form>
                    @csrf @method('PUT')
                    <input type="hidden" name="setting_id" value="{{ $singleton->id }}">
                    <div class="configuration-dialog__field"><label for="days-value">Días:</label><input class="configuration-input" id="days-value" name="numero_dias_valor" type="number" min="1" max="127" step="1" value="{{ old('numero_dias_valor', $singleton->Numero_Dias) }}" required aria-describedby="days-error">@error('numero_dias_valor')<p id="days-error" role="alert">{{ $message }}</p>@else<span id="days-error" class="configuration-sr-only">Número entero entre 1 y 127.</span>@enderror</div>
                    <footer><button class="configuration-button" type="button" data-close-days>Cerrar</button><button class="configuration-button configuration-button--blue" type="submit">Guardar datos</button></footer>
                </form>
            </dialog>
        @endcan
    @endif
@endsection
