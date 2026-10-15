@props(['name' => 'circle'])

@php
    $paths = [
        'menu' => 'M4 6h16M4 12h16M4 18h16',
        'home' => 'm3 10 9-7 9 7M5 9v12h5v-7h4v7h5V9',
        'bell' => 'M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4',
        'money' => 'M3 5h18v14H3zM7 9h.01M17 15h.01M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0',
        'cart' => 'M3 3h2l3 12h10l3-9H6M9 20h.01M17 20h.01',
        'box' => 'm3 7 9-4 9 4-9 4-9-4M3 7v10l9 4 9-4V7M12 11v10',
        'plus' => 'M12 4v16M4 12h16',
        'users' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M20 21v-2a4 4 0 0 0-3-4M13 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0M17 3a4 4 0 0 1 0 8',
        'cup' => 'M3 7h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V7M17 8h2a3 3 0 0 1 0 6h-2M6 3v1M10 3v1M14 3v1',
        'lock' => 'M5 10h14v11H5zM8 10V6a4 4 0 0 1 8 0v4M12 14v3',
        'settings' => 'M12 3v3M12 18v3M3 12h3M18 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M5.6 18.4l2.1-2.1M16.3 7.7l2.1-2.1M17 12a5 5 0 1 1-10 0 5 5 0 0 1 10 0',
        'logout' => 'M9 3H3v18h6M9 12h12m-4-4 4 4-4 4',
        'chevron' => 'm9 5 7 7-7 7',
        'circle' => 'M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0',
        'arrow' => 'M4 12h16m-6-6 6 6-6 6',
    ];
@endphp

<svg {{ $attributes->class(['layout-icon']) }}
     viewBox="0 0 24 24"
     fill="none"
     stroke="currentColor"
     stroke-width="1.8"
     stroke-linecap="round"
     stroke-linejoin="round"
     aria-hidden="true"
     focusable="false">
    <path d="{{ $paths[$name] ?? $paths['circle'] }}" />
</svg>
