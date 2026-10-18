@props(['icon' => 'edit'])
<svg viewBox="0 0 24 24" aria-hidden="true">
    @if ($icon === 'delete')<path d="m7 5 5 5 5-5 2 2-5 5 5 5-2 2-5-5-5 5-2-2 5-5-5-5 2-2Z"/>
    @elseif ($icon === 'add')<path d="M10 3h4v7h7v4h-7v7h-4v-7H3v-4h7Z"/>
    @else<path d="m16 3 5 5-12 12-6 1 1-6L16 3Zm-11 13-1 3 3-1 10-10-2-2L5 16Z"/>@endif
</svg>
