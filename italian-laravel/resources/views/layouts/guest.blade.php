<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'The Italian Coffee Company')</title>

    <link rel="icon"
          type="image/x-icon"
          href="{{ asset('images/auth/favicon.ico') }}">

    @vite([
        'resources/css/auth/login.css',
        'resources/js/auth/login.js',
    ])
</head>
<body class="guest-page"
      style="--login-background: url('{{ asset('images/auth/team2.jpg') }}')">
<main class="guest-main">
    @yield('content')
</main>
</body>
</html>
