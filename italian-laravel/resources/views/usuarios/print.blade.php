<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">

    <title>{{ $title }} — The Italian Coffee Company</title>

    @vite([
        'resources/css/users-read.css',
        'resources/js/users-read.js',
    ])
</head>
<body class="users-print">
<header>
    <h1>{{ $title }}</h1>

    @if ($date)
        <p>Fecha de accesos: {{ $date }}</p>
    @endif

    <p>Registros que coinciden con los filtros de la consulta.</p>

    <button class="users-tool users-print-control"
            type="button"
            data-print-page>
        Imprimir / Guardar como PDF
    </button>
</header>

<table class="users-table">
    <caption class="users-print-caption">
        The Italian Coffee Company
    </caption>

    <thead>
    <tr>
        @foreach ($columns as $column)
            <th scope="col">{{ $column['label'] }}</th>
        @endforeach
    </tr>
    </thead>

    <tbody>
    @forelse ($records as $record)
        <tr>
            @foreach ($columns as $column)
                <td>{{ $record->{$column['field']} }}</td>
            @endforeach
        </tr>
    @empty
        <tr>
            <td colspan="{{ count($columns) }}">
                No se encontraron registros.
            </td>
        </tr>
    @endforelse
    </tbody>
</table>
</body>
</html>
