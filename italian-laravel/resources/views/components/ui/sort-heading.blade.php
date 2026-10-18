@props(['label', 'url', 'selected' => false, 'direction' => 'asc'])
<th scope="col" aria-sort="{{ $selected ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}">
    <a href="{{ $url }}" aria-label="Ordenar por {{ $label }} {{ $selected && $direction === 'asc' ? 'descendente' : 'ascendente' }}">
        <span data-copy-value>{{ $label }}</span>
        <span class="ui-list__sort" aria-hidden="true">{{ $selected ? ($direction === 'asc' ? '↑' : '↓') : '↕' }}</span>
    </a>
</th>
