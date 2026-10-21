// Opt-in enhancement of authorized GET routes. All controls retain HTTP fallbacks.
let pending;
let request;
let sequence = 0;
const initial = document.querySelector('[data-async-list]');
if (initial && new URL(initial.querySelector('form[method="GET"]').action).pathname === location.pathname) {
    const path = window.location.pathname;
    const status = document.createElement('p');
    status.className = 'ui-list__copy-status ui-list__sr-only';
    status.setAttribute('role', 'status');
    status.setAttribute('aria-live', 'polite');
    initial.before(status);
    history.replaceState({ ...history.state, asyncListing: true }, '', window.location.href);

    const load = async (url, historyMode = 'push', focus = null) => {
        clearTimeout(pending);
        const current = document.querySelector('[data-async-list]');
        if (!current || url.origin !== location.origin || url.pathname !== path) return;
        request?.abort();
        request = new AbortController();
        const id = ++sequence;
        current.setAttribute('aria-busy', 'true');
        status.classList.add('ui-list__sr-only');
        status.textContent = 'Cargando registros…';
        try {
            const response = await fetch(url, {
                credentials: 'same-origin', signal: request.signal,
                headers: { Accept: 'text/html', 'X-Listing-Fragment': 'listing' },
            });
            if (!response.ok || response.redirected || response.headers.get('X-Listing-Fragment') !== 'listing') throw new Error('Unavailable listing');
            const html = new DOMParser().parseFromString(await response.text(), 'text/html');
            const listing = html.querySelector('[data-async-list]');
            if (!listing) throw new Error('Missing listing');
            if (id !== sequence) return;
            current.replaceWith(document.importNode(listing, true));
            if (historyMode === 'push') history.pushState({ asyncListing: true }, '', url);
            if (historyMode === 'replace') history.replaceState({ asyncListing: true }, '', url);
            status.textContent = 'Listado actualizado.';
            if (focus) {
                const field = document.getElementById(focus.id);
                field?.focus({ preventScroll: true });
                if (field && focus.start !== null && /^(search|text)$/.test(field.type)) field.setSelectionRange(focus.start, focus.end);
            }
            document.dispatchEvent(new CustomEvent('listing:updated', { detail: { url: url.href } }));
        } catch (error) {
            if (error.name === 'AbortError' || id !== sequence) return;
            status.classList.remove('ui-list__sr-only');
            status.textContent = 'No se pudo actualizar el listado. Comprueba la conexión y la sesión, o recarga la página.';
            if (historyMode === 'none') window.location.assign(url);
        } finally {
            if (id === sequence) document.querySelector('[data-async-list]')?.removeAttribute('aria-busy');
        }
    };
    function formUrl(form) {
        const url = new URL(form.action);
        url.search = new URLSearchParams(new FormData(form)).toString();
        const search = form.closest('[data-async-list]').querySelector('input[type="search"]');
        if (search) url.searchParams.set('q', search.value);
        url.searchParams.delete('page');
        return url;
    }
    document.addEventListener('submit', event => {
        const form = event.target;
        if (!form.matches('[data-async-list] form[method="GET"]')) return;
        event.preventDefault();
        load(formUrl(form));
    });
    document.addEventListener('input', event => {
        const field = event.target;
        if (!field.matches('[data-async-list] input[type="search"]')) return;
        clearTimeout(pending);
        // Invalidate an older response immediately, even during the debounce interval.
        request?.abort();
        sequence++;
        pending = setTimeout(() => load(formUrl(field.form), 'replace', { id: field.id, start: field.selectionStart, end: field.selectionEnd }), 300);
    });
    document.addEventListener('change', event => {
        const field = event.target;
        if (field.matches('[data-async-list] [data-auto-filter]')) load(formUrl(field.form));
    });
    document.addEventListener('click', event => {
        const link = event.target.closest('[data-async-list] th a, [data-async-list] .ui-list__pagination a');
        if (!link || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        const url = new URL(link.href);
        const search = document.querySelector('[data-async-list] input[type="search"]');
        if (search && search.value !== (url.searchParams.get('q') ?? '')) {
            url.searchParams.set('q', search.value);
            url.searchParams.delete('page');
        }
        load(url);
    });
    document.addEventListener('listing:refresh', () => load(new URL(location.href), 'none'));
    window.addEventListener('popstate', () => load(new URL(location.href), 'none'));
}
