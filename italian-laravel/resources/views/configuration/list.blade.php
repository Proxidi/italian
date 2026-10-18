@extends('configuration.layout')

@section('configuration-content')
    @php
        $isDates = $section === 'dates';
        $route = $isDates ? 'configuration.dates' : 'configuration.notifications';
        $updatePermission = $isDates ? 'configuration.upload-window.update' : 'configuration.notifications.update';
    @endphp
    <header class="ui-list-heading">
        <h1>{{ $isDates ? 'Subida de archivos Corte & DLC' : 'Sistema de Notificaciones' }} <small>{{ $isDates ? 'Corte & DLC' : 'Notificaciones' }}</small></h1>
        @can('configuration.view')
            <a href="{{ route('configuration.index') }}">Volver al Panel de Configuración <span aria-hidden="true">➜</span></a>
        @else
            <a href="{{ route('inicio') }}">Volver al Panel de Inicio <span aria-hidden="true">➜</span></a>
        @endcan
    </header>

    @if (session('configuration-success') && ! $errors->any())
        @if ($isDates)
            <x-ui.feedback variant="dialog" position="corner" :title="session('configuration-success')" :duration="1500" id="configuration-success" data-configuration-success />
        @else
            <x-ui.feedback variant="notice" :title="session('configuration-success')" data-configuration-success />
        @endif
    @endif
    <x-ui.listing :label="$isDates ? 'Corte & DLC' : 'Notificaciones'">
        @if ($errors->any())
            <div class="ui-list__errors" role="alert"><p>No se guardaron los cambios:</p><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
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

        <form action="{{ route($route) }}" method="GET" class="ui-list__filters">
            <input type="hidden" name="sort" value="{{ $sort }}"><input type="hidden" name="direction" value="{{ $direction }}">
            <x-ui.toolbar :export-route="$route.'.export'" :filters="['q' => $search, 'sort' => $sort, 'direction' => $direction]" :records="$records" search-id="configuration-search" />
        </form>
        <x-ui.table :label="$isDates ? 'Corte & DLC' : 'Notificaciones'">
            <x-slot:head>
                @foreach ($columns as $column)
                    <x-ui.sort-heading :label="$column['label']" :selected="$sort === $column['field']" :direction="$direction"
                        :url="route($route, ['q' => $search, 'sort' => $column['field'], 'direction' => $sort === $column['field'] && $direction === 'asc' ? 'desc' : 'asc'])" />
                @endforeach
                @if ($isDates)<th scope="col" class="ui-list__action-column" data-copy-exclude>Editar</th>@endif
            </x-slot:head>
            @forelse ($records as $record)
                <tr data-record-row>
                    @foreach ($columns as $column)<td>{{ $record->{$column['field']} }}</td>@endforeach
                    @if ($isDates)
                        <td data-copy-exclude><x-ui.table-action color="green" data-open-days aria-label="Editar rango de días" :disabled="$singleton === null || !auth('web')->user()->can($updatePermission)" /></td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="{{ count($columns) + ($isDates ? 1 : 0) }}">No se encontraron registros.</td></tr>
            @endforelse
        </x-ui.table>
        <x-ui.pagination :records="$records" />
    </x-ui.listing>

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
