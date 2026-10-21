@extends('layouts.app')

@section('title', $title.' — The Italian Coffee Company')
@push('styles')
    @vite('resources/css/users-read.css')
@endpush
@push('scripts')
    @vite('resources/js/users-read.js')
@endpush

@section('content')
    @php
        $isDirectory = $indexRoute === 'usuarios.index';
        $columnCount = count($columns) + ($isDirectory ? 2 : 0);
    @endphp
    <header class="ui-list-heading">
        <h1>{{ $title }} <small>Listado</small></h1>
        <a href="{{ route('inicio') }}">Volver al Panel de Inicio <span aria-hidden="true">➜</span></a>
    </header>

    @if(session('message') && !session('user-created') && !$errors->any())
        <x-ui.feedback :title="session('message')" />
    @endif
    @fragment('listing')
    <x-ui.listing data-async-list :label="$title">
        @if ($errors->any())
            <div class="ui-list__errors" role="alert">
                <p>Revisa los datos:</p><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        @if ($isDirectory)
            <div class="ui-list__create">
                <button class="ui-list__button ui-list__button--blue" type="button" data-open-user-create @cannot('create', \App\Models\User::class) disabled aria-disabled="true" @endcannot>
                    <span aria-hidden="true">✚</span> Agregar Usuario
                </button>
            </div>
        @endif

        <form id="users-read-filters" action="{{ route($indexRoute) }}" method="GET" class="ui-list__filters">
            <input type="hidden" name="sort" value="{{ $filters['sort'] }}">
            <input type="hidden" name="direction" value="{{ $filters['direction'] }}">
            @unless ($isDirectory)
                <div class="ui-list__date-row">
                    <label for="filter-date">Fecha:</label>
                    <input class="ui-list__input ui-list__date" id="filter-date" name="date" type="date" value="{{ $filters['date'] }}" required>
                    <button class="ui-list__button ui-list__button--green" type="submit">Buscar</button>
                    <a class="ui-list__button ui-list__button--orange" href="{{ route($indexRoute) }}">Restablecer</a>
                </div>
            @endunless
            <x-ui.toolbar :export-route="$exportRoute" :filters="$filters" :records="$records" search-id="filter-search" />
        </form>

        <x-ui.table :label="$title" :wide="true">
            <x-slot:head>
                @foreach ($columns as $key => $column)
                    @php
                        $selected = $filters['sort'] === $key;
                        $nextDirection = $selected && $filters['direction'] === 'asc' ? 'desc' : 'asc';
                        $label = $isDirectory && $key === 'username' ? 'User Name' : $column['label'];
                    @endphp
                    <x-ui.sort-heading :label="$label" :selected="$selected" :direction="$filters['direction']"
                        :url="route($indexRoute, [...$filters, 'sort' => $key, 'direction' => $nextDirection])" />
                @endforeach
                @if ($isDirectory)
                    <th scope="col" class="ui-list__action-column" data-copy-exclude>Editar</th>
                    <th scope="col" class="ui-list__action-column" data-copy-exclude>Eliminar</th>
                @endif
            </x-slot:head>
            @forelse ($records as $record)
                <tr data-record-row>
                    @foreach ($columns as $column)<td>{{ $record->{$column['field']} }}</td>@endforeach
                    @if ($isDirectory)
                        <td data-copy-exclude>
                            @if(auth('web')->user()->can('users.update'))
                                <x-ui.table-action :href="route('usuarios.edit', $record->id)" aria-label="Editar usuario {{ $record->Nombre }}" />
                            @else
                                <x-ui.table-action disabled aria-disabled="true" aria-label="Editar usuario: sin permiso" />
                            @endif
                        </td>
                        <td data-copy-exclude>
                            <x-ui.table-action color="red" icon="delete" disabled aria-disabled="true" aria-label="Eliminar usuario: no disponible" />
                        </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="{{ $columnCount }}">No se encontraron registros.</td></tr>
            @endforelse
        </x-ui.table>
        <x-ui.pagination :records="$records" />
    </x-ui.listing>
    @endfragment
    @if($isDirectory)
        @can('create', \App\Models\User::class)
            @include('usuarios.partials.create')
        @endcan
    @endif

    @if(session('user-created') && !$errors->any())
        <x-ui.feedback variant="dialog" title="Usuario Ingresado!" description="Registro Exitoso!" id="user-created"
            :duration="2200" :redirect-url="route('usuarios.index')" data-user-created-dialog />
    @endif
@endsection
