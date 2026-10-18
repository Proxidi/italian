@props(['label', 'wide' => false])
<div class="ui-list__scroll" role="region" aria-label="Tabla de {{ $label }}" tabindex="0">
    <table {{ $attributes->class(['ui-list__table', 'ui-list__table--wide' => $wide]) }} data-read-table>
        <caption class="ui-list__sr-only">{{ $label }}</caption>
        <thead><tr>{{ $head }}</tr></thead>
        <tbody>{{ $slot }}</tbody>
    </table>
</div>
