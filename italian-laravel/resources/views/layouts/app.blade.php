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
        'resources/css/authenticated.css',
        'resources/js/authenticated.js',
        'resources/css/ui-list.css',
        'resources/css/ui-feedback.css',
        'resources/js/ui.js',
    ])

    @stack('styles')
</head>
<body class="authenticated-page">
<a href="#main-content" class="skip-link">Ir al contenido</a>

<div class="app-shell" data-app-shell>
    <x-layout.header :profile="$layout['profile']" />

    <x-layout.sidebar :items="$layout['sidebar']" />

    <button class="sidebar-backdrop"
            type="button"
            aria-label="Cerrar navegación"
            data-sidebar-backdrop
            hidden></button>

    <main id="main-content" class="app-main" tabindex="-1">
        @yield('content')
    </main>

    <x-layout.footer />
</div>

@stack('scripts')
</body>
</html>
