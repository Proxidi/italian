import { startActionLoading } from './action-loading.js';

const status = document.querySelector('[data-cost-status]');
if (status) {
    let busy = false;
    function mount(dialog, opener = null) {
        document.body.append(dialog);
        dialog.showModal();
        const form = dialog.querySelector('form');
        const errors = dialog.querySelector('[data-cost-errors]');
        dialog.addEventListener('cancel', event => { if (busy) event.preventDefault(); });
        dialog.addEventListener('close', () => {
            if (opener) { dialog.remove(); opener.focus(); }
            else window.location.assign(dialog.querySelector('a[data-close-cost]').href);
        });
        dialog.querySelectorAll('[data-close-cost]').forEach(button => button.addEventListener('click', event => {
            event.preventDefault();
            if (!busy) dialog.close();
        }));
        form.addEventListener('submit', async event => {
            event.preventDefault();
            if (busy || !form.reportValidity()) return;
            busy = true;
            const button = form.querySelector('[type="submit"]');
            button.disabled = true;
            form.setAttribute('aria-busy', 'true');
            errors.hidden = true;
            try {
                const response = await fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: { Accept: 'application/json' } });
                if (!response.ok) {
                    errors.textContent = response.status === 422
                        ? Object.values((await response.json()).errors ?? {}).flat().join(' ')
                        : 'No se guardaron los cambios. Comprueba tu sesión, permisos y la disponibilidad del insumo.';
                    errors.hidden = false;
                    errors.focus();
                    return;
                }
                if (!response.headers.get('content-type')?.includes('application/json')) throw new Error('Unconfirmed write');
                const data = await response.json();
                const success = document.querySelector('[data-cost-success-template]').content.firstElementChild.cloneNode(true);
                success.querySelector('h2').textContent = data.message;
                document.body.append(success);
                success.showModal();
                const finish = () => {
                    window.clearTimeout(timer);
                    success.remove();
                    dialog.close();
                    document.dispatchEvent(new CustomEvent('listing:refresh'));
                };
                const timer = window.setTimeout(() => success.close(), 1000);
                success.addEventListener('close', finish, { once: true });
            } catch {
                errors.textContent = 'No se pudo confirmar el guardado. Consulta los datos antes de reintentar.';
                errors.hidden = false;
                errors.focus();
            } finally {
                busy = false;
                button.disabled = false;
                form.removeAttribute('aria-busy');
            }
        });
    }
    const initial = document.querySelector('[data-cost-dialog]');
    if (initial) mount(initial);
    document.addEventListener('click', async event => {
        const link = event.target.closest('[data-edit-cost]');
        if (!link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        if (busy) return;
        busy = true;
        const stopLoading = startActionLoading(link, 'Cargando costo…');
        status.textContent = '';
        try {
            const response = await fetch(link.href, { credentials: 'same-origin', headers: { Accept: 'text/html' } });
            if (!response.ok || response.redirected) throw new Error('Unavailable cost');
            const page = new DOMParser().parseFromString(await response.text(), 'text/html');
            const dialog = page.querySelector('[data-cost-dialog]');
            if (!dialog) throw new Error('Unavailable dialog');
            mount(document.importNode(dialog, true), link);
            status.textContent = '';
        } catch { status.textContent = 'No se pudo consultar el costo. Comprueba la conexión, tu sesión y permisos.'; }
        finally {
            busy = false;
            stopLoading();
        }
    });
}
