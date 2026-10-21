export function startActionLoading(action, label) {
    const originalLabel = action.getAttribute('aria-label');
    const wasDisabled = action instanceof HTMLButtonElement && action.disabled;

    action.classList.add('is-loading');
    action.setAttribute('aria-busy', 'true');
    action.setAttribute('aria-disabled', 'true');
    action.setAttribute('aria-label', label);

    if (action instanceof HTMLButtonElement) action.disabled = true;

    return () => {
        action.classList.remove('is-loading');
        action.removeAttribute('aria-busy');

        if (originalLabel === null) action.removeAttribute('aria-label');
        else action.setAttribute('aria-label', originalLabel);

        if (wasDisabled) action.setAttribute('aria-disabled', 'true');
        else action.removeAttribute('aria-disabled');

        if (action instanceof HTMLButtonElement) action.disabled = wasDisabled;
    };
}
