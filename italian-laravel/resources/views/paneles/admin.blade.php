<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel temporal</title>
</head>
<body>
<h1>Login correcto</h1>

<p>Usuario autenticado: {{ auth()->user()->Usuario }}</p>

<form method="POST" action="{{ route('logout') }}">
    @csrf

    <button type="submit">
        Cerrar sesión
    </button>
</form>
</body>
</html>
<?php
