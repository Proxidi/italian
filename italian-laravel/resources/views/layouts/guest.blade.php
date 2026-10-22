<!DOCTYPE html>
<html lang="es">
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
      style="--login-background: url('{{ asset('assets/images/auth/bg-ticc.webp') }}'); --login-background-mobile: url('{{ asset('assets/images/auth/bg-ticc-mobile.webp') }}')">
<main class="guest-main">
    @yield('content')
</main>
</body>
</html>
