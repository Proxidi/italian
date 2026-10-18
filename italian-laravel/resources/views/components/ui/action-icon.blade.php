@props(['icon' => 'edit'])
<svg viewBox="0 0 24 24" aria-hidden="true">
    @if ($icon === 'delete')<path d="m7 5 5 5 5-5 2 2-5 5 5 5-2 2-5-5-5 5-2-2 5-5-5-5 2-2Z"/>
    @else<path d="m16 3 5 5-12 12-6 1 1-6L16 3Zm-11 13-1 3 3-1 10-10-2-2L5 16Z"/>@endif
</svg>
