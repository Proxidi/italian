@props(['title', 'description' => null, 'variant' => 'banner', 'position' => 'center', 'duration' => 0, 'redirectUrl' => null, 'id' => 'success-feedback'])
@if ($variant === 'dialog')
    <dialog {{ $attributes->class('ui-feedback-dialog ui-feedback-dialog--'.$position) }} data-feedback-dialog data-duration="{{ $duration }}" @if ($redirectUrl) data-redirect-url="{{ $redirectUrl }}" @endif aria-labelledby="{{ $id }}-title" @if ($description) aria-describedby="{{ $id }}-description" @endif>
        <div class="ui-feedback-dialog__symbol" aria-hidden="true">
            <svg viewBox="0 0 80 80"><circle cx="40" cy="40" r="34"/><path d="m24 40 11 11 22-24"/></svg>
        </div>
        <h2 id="{{ $id }}-title" tabindex="-1" autofocus>{{ $title }}</h2>
        @if ($description)<p id="{{ $id }}-description">{{ $description }}</p>@endif
    </dialog>
@else
    <div {{ $attributes->class('ui-feedback ui-feedback--'.$variant) }} role="status" data-feedback>
        <svg class="ui-feedback__icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
        <strong>{{ $title }}</strong>
        <button class="ui-feedback__close" type="button" data-dismiss-feedback aria-label="Cerrar mensaje">×</button>
    </div>
@endif
