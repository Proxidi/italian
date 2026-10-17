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

    <header class="users-read-heading">
        <h1>{{ $title }} <small>Listado</small></h1>

        <a href="{{ route('inicio') }}">
            Volver al Panel de Inicio
            <span aria-hidden="true">➜</span>
        </a>
    </header>

    @if(session('message'))
        <div class="users-flash users-flash--success" role="status">
            <svg class="users-flash__icon" viewBox="0 0 24 24" aria-hidden="true">
                <path d="m5 12 4 4L19 6" />
            </svg>
            <strong>{{ session('message') }}</strong>
            <button class="users-flash__close" type="button" data-dismiss-flash aria-label="Cerrar mensaje">×</button>
        </div>
    @endif
    <section class="users-read" aria-label="{{ $title }}">
        @if ($errors->any())
            <div class="users-read__errors" role="alert">
                <p>Revisa los datos:</p>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($isDirectory)
            <div class="users-read__create">
                <button
                    class="users-read__button users-read__button--blue"
                    type="button"
                    data-open-user-create
                    @cannot('create', \App\Models\User::class) disabled aria-disabled="true" @endcannot
                >
                    <span aria-hidden="true">✚</span>
                    Agregar Usuario
                </button>
            </div>
        @endif

        <form
            id="users-read-filters"
            action="{{ route($indexRoute) }}"
            method="GET"
            class="users-read__filters"
        >
            <input type="hidden" name="sort" value="{{ $filters['sort'] }}">
            <input type="hidden" name="direction" value="{{ $filters['direction'] }}">

            @unless ($isDirectory)
                <div class="users-read__date-row">
                    <label for="filter-date">Fecha:</label>

                    <input
                        class="users-read__input users-read__date"
                        id="filter-date"
                        name="date"
                        type="date"
                        value="{{ $filters['date'] }}"
                        required
                    >

                    <button
                        class="users-read__button users-read__button--green"
                        type="submit"
                    >
                        Buscar
                    </button>

                    <a
                        class="users-read__button users-read__button--orange"
                        href="{{ route($indexRoute) }}"
                    >
                        Restablecer
                    </a>
                </div>
            @endunless

            <div class="users-read__toolbar">
                @include('usuarios.partials.tools')

                <div class="users-read__search">
                    <label for="filter-search">Buscar:</label>

                    <input
                        class="users-read__input"
                        id="filter-search"
                        name="q"
                        type="search"
                        value="{{ $filters['q'] }}"
                        maxlength="100"
                        placeholder="Buscar registros"
                        aria-describedby="users-search-help"
                    >

                    <span id="users-search-help" class="users-read__sr-only">
                        Presiona Enter para aplicar la búsqueda.
                    </span>

                    <button class="users-read__sr-only" type="submit">
                        Aplicar búsqueda
                    </button>
                </div>
            </div>
        </form>

        <div
            class="users-read__scroll"
            role="region"
            aria-label="Tabla de {{ $title }}"
            tabindex="0"
        >
            <table class="users-read__table" data-read-table>
                <caption class="users-read__sr-only">{{ $title }}</caption>

                <thead>
                <tr>
                    @foreach ($columns as $key => $column)
                        @php
                            $selected = $filters['sort'] === $key;

                            $nextDirection = $selected && $filters['direction'] === 'asc'
                                ? 'desc'
                                : 'asc';

                            $ariaSort = $selected
                                ? ($filters['direction'] === 'asc' ? 'ascending' : 'descending')
                                : 'none';

                            $label = $isDirectory && $key === 'username'
                                ? 'User Name'
                                : $column['label'];
                        @endphp

                        <th scope="col" aria-sort="{{ $ariaSort }}">
                            <a
                                href="{{ route($indexRoute, [
                                        ...$filters,
                                        'sort' => $key,
                                        'direction' => $nextDirection,
                                    ]) }}"
                                aria-label="Ordenar por {{ $label }} {{ $nextDirection === 'asc' ? 'ascendente' : 'descendente' }}"
                            >
                                <span data-copy-value>{{ $label }}</span>

                                <span
                                    class="users-read__sort"
                                    aria-hidden="true"
                                >
                                        {{ $selected ? ($filters['direction'] === 'asc' ? '↑' : '↓') : '↕' }}
                                    </span>
                            </a>
                        </th>
                    @endforeach

                    @if ($isDirectory)
                        <th scope="col" class="users-read__action-column" data-copy-exclude>
                            Editar
                        </th>

                        <th scope="col" class="users-read__action-column" data-copy-exclude>
                            Eliminar
                        </th>
                    @endif
                </tr>
                </thead>

                <tbody>
                @forelse ($records as $record)
                    <tr data-record-row>
                        @foreach ($columns as $column)
                            <td>{{ $record->{$column['field']} }}</td>
                        @endforeach

                        @if ($isDirectory)
                            <td data-copy-exclude>
                                @if(auth('web')->user()->can('users.update'))
                                    <a class="users-read__action users-read__action--edit" href="{{ route('usuarios.edit', $record->id) }}" aria-label="Editar usuario {{ $record->Nombre }}">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m16 3 5 5-12 12-6 1 1-6L16 3Zm-11 13-1 3 3-1 10-10-2-2L5 16Z"/></svg>
                                    </a>
                                @else
                                    <button class="users-read__action users-read__action--edit" type="button" disabled aria-disabled="true" aria-label="Editar usuario: sin permiso">✎</button>
                                @endif
                            </td>

                            <td data-copy-exclude>
                                <button
                                    class="users-read__action users-read__action--delete"
                                    type="button"
                                    disabled
                                    aria-disabled="true"
                                    aria-label="Eliminar usuario: no disponible"
                                >
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="m7 5 5 5 5-5 2 2-5 5 5 5-2 2-5-5-5 5-2-2 5-5-5-5 2-2Z"/>
                                    </svg>
                                </button>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $columnCount }}">
                            No se encontraron registros.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <footer class="users-read__pagination">
            <p role="status">
                @if ($records->total() > 0)
                    Mostrando registros de {{ $records->firstItem() ?? 0 }}
                    al {{ $records->lastItem() ?? 0 }}
                    de un total de {{ $records->total() }} registros
                @else
                    No existen registros.
                @endif
            </p>

            <nav aria-label="Paginación del listado">
                @if ($records->onFirstPage())
                    <span class="users-read__page is-disabled" aria-disabled="true">
                        Anterior
                    </span>
                @else
                    <a class="users-read__page" href="{{ $records->previousPageUrl() }}">
                        Anterior
                    </a>
                @endif

                <span class="users-read__page is-current" aria-current="page">
                    {{ $records->currentPage() }}
                </span>

                @if ($records->hasMorePages())
                    <a class="users-read__page" href="{{ $records->nextPageUrl() }}">
                        Siguiente
                    </a>
                @else
                    <span class="users-read__page is-disabled" aria-disabled="true">
                        Siguiente
                    </span>
                @endif
            </nav>
        </footer>
    </section>
    @if($isDirectory)
        @can('create', \App\Models\User::class)
            @include('usuarios.partials.create')
        @endcan
    @endif

    @if(session('user-created'))
        <dialog
            class="users-success-dialog"
            data-user-created-dialog
            data-redirect-url="{{ route('usuarios.index') }}"
            aria-labelledby="user-created-title"
            aria-describedby="user-created-description"
        >
            <div class="users-success-dialog__symbol" aria-hidden="true">
                <svg viewBox="0 0 80 80"><circle cx="40" cy="40" r="34" /><path d="m24 40 11 11 22-24" /></svg>
            </div>
            <h2 id="user-created-title">Usuario Ingresado!</h2>
            <p id="user-created-description">Registro Exitoso!</p>
        </dialog>
    @endif
@endsection
