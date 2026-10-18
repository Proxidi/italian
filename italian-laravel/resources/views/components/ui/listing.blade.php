@props(['label'])
<section {{ $attributes->class('ui-list') }} aria-label="{{ $label }}">
    {{ $slot }}
</section>
