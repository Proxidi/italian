@props(['href' => null, 'color' => 'orange', 'icon' => 'edit'])
@if ($href)
    <a {{ $attributes->class('ui-list__action ui-list__action--'.$color) }} href="{{ $href }}">
        <x-ui.action-icon :icon="$icon" />
    </a>
@else
    <button {{ $attributes->class('ui-list__action ui-list__action--'.$color) }} type="button">
        <x-ui.action-icon :icon="$icon" />
    </button>
@endif
