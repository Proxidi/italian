<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">

    <title>{{ $title }}</title>

    <style>
        @page {
            margin: 30px;
        }

        body {
            color: #222;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 9px;
        }

        h1 {
            margin: 0 0 14px;
            text-align: center;
            font-size: 17px;
        }

        .date {
            margin-bottom: 12px;
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }

        th,
        td {
            padding: 6px;
            border: 1px solid #ddd;
            overflow-wrap: break-word;
            text-align: left;
        }

        th {
            background: #eaeaea;
        }
    </style>
</head>
<body>
<h1>{{ $title }}</h1>

@if ($date !== null)
    <p class="date">Fecha: {{ $date }}</p>
@endif

<table>
    <thead>
    <tr>
        @foreach ($columns as $column)
            <th>{{ $column['label'] }}</th>
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
