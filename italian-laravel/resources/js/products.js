const dialogs = new Map(Array.from(document.querySelectorAll('[data-unit-dialog]'), dialog => [dialog.dataset.unitDialog, dialog]));
document.querySelector('[data-new-unit]')?.addEventListener('click', () => {
    const dialog = dialogs.get('create');
    dialog.querySelector('form').reset();
    dialog.showModal();
});
document.querySelectorAll('[data-edit-unit]').forEach(button => button.addEventListener('click', () => {
    const dialog = dialogs.get('update');
    const form = dialog.querySelector('form');
    form.action = button.dataset.action;
    form.elements.nombre.value = button.dataset.name;
    form.elements._unit_id.value = button.dataset.id;
    dialog.showModal();
}));
document.querySelectorAll('[data-close-unit]').forEach(button => button.addEventListener('click', () => button.closest('dialog').close()));
document.querySelector('[data-unit-dialog][data-reopen]')?.showModal();
document.querySelectorAll('[data-unit-form]').forEach(form => form.addEventListener('submit', event => {
    if (form.dataset.submitting) {
        event.preventDefault();
        return;
    }
    if (!form.checkValidity()) return;
    form.dataset.submitting = 'true';
    form.setAttribute('aria-busy', 'true');
    form.querySelectorAll('button[type="submit"]').forEach(button => { button.disabled = true; });
}));
window.addEventListener('pageshow', () => document.querySelectorAll('[data-unit-form]').forEach(form => {
    delete form.dataset.submitting;
    form.removeAttribute('aria-busy');
    form.querySelectorAll('button[type="submit"]').forEach(button => { button.disabled = false; });
}));

// Enhance the existing HTML routes; ordinary links/forms remain the no-JS fallback.
let loadingConversions = false;
let savingConversion = false;
const loadStatus = document.querySelector('[data-ingredient-load-status]');

function initializeConversions(dialog, opener = null) {
    let saved = false;
    const editDialog = dialog.nextElementSibling?.matches('[data-conversion-edit]') ? dialog.nextElementSibling : document.querySelector('[data-conversion-edit]');
    dialog.showModal();
    dialog.addEventListener('cancel', event => {
        if (savingConversion) event.preventDefault();
    });
    dialog.addEventListener('close', () => {
        if (opener || saved) {
            editDialog?.remove();
            dialog.remove();
            (opener ?? document.querySelector('[data-open-conversions]'))?.focus();
        } else {
            window.location.assign(dialog.dataset.returnUrl);
        }
    });
    dialog.querySelectorAll('[data-close-conversions]').forEach(button => button.addEventListener('click', event => {
        event.preventDefault();
        if (!savingConversion) dialog.close();
    }));
    if (!editDialog) return;
    editDialog.addEventListener('cancel', event => {
        if (savingConversion) event.preventDefault();
    });
    editDialog.addEventListener('close', () => dialog.querySelector('[data-edit-conversion]')?.focus());
    dialog.querySelectorAll('[data-edit-conversion]').forEach(button => button.addEventListener('click', () => {
        const form = editDialog.querySelector('form');
        form.action = button.dataset.action;
        form.elements.bodega.value = button.dataset.bodega;
        form.elements.pedido.value = button.dataset.pedido;
        form.elements._conversion_id.value = button.dataset.id;
        editDialog.showModal();
        form.elements.bodega.focus();
    }));
    editDialog.querySelectorAll('[data-close-conversion-edit]').forEach(button => button.addEventListener('click', () => {
        if (!savingConversion) editDialog.close();
    }));
    if (editDialog.hasAttribute('data-reopen')) editDialog.showModal();
    const form = editDialog.querySelector('form');
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (savingConversion || !form.reportValidity()) return;
        savingConversion = true;
        form.setAttribute('aria-busy', 'true');
        const submit = form.querySelector('[type="submit"]');
        submit.disabled = true;
        const errors = form.querySelector('[data-conversion-errors]');
        errors.hidden = true;
        form.querySelectorAll('[aria-invalid]').forEach(input => input.removeAttribute('aria-invalid'));
        try {
            const response = await fetch(form.action, {
                method: 'POST', body: new FormData(form), credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) {
                if (response.status === 422) {
                    const data = await response.json();
                    errors.textContent = Object.values(data.errors ?? {}).flat().join(' ');
                    Object.keys(data.errors ?? {}).forEach(name => form.elements.namedItem(name)?.setAttribute('aria-invalid', 'true'));
                } else {
                    errors.textContent = response.status === 419 || response.status === 401
                        ? 'La sesión expiró. Recarga la página e inicia sesión antes de volver a guardar.'
                        : 'No se pudo guardar. Revisa tus permisos y que la conversión siga disponible.';
                }
                errors.hidden = false;
                errors.focus();
                return;
            }
            if (!response.headers.get('content-type')?.includes('application/json')) throw new Error('Unexpected response');
            const result = await response.json();
            // Keep the displayed values current, including subsequent edits in this dialog.
            const row = dialog.querySelector('[data-edit-conversion]');
            row.dataset.bodega = form.elements.bodega.value.trim();
            row.dataset.pedido = form.elements.pedido.value.trim();
            row.closest('tr').cells[0].textContent = row.dataset.bodega;
            row.closest('tr').cells[1].textContent = row.dataset.pedido;
            const success = document.querySelector('[data-ingredient-success-template]').content.firstElementChild.cloneNode(true);
            success.querySelector('h2').textContent = result.message;
            document.body.append(success);
            success.showModal();
            const finish = () => {
                window.clearTimeout(timer);
                success.remove();
                editDialog.close();
                saved = true;
                dialog.close();
            };
            const timer = window.setTimeout(() => success.close(), 1500);
            success.addEventListener('close', finish, { once: true });
        } catch {
            errors.textContent = 'No se pudo confirmar el guardado. Comprueba la conexión y consulta los datos antes de reintentarlo.';
            errors.hidden = false;
            errors.focus();
        } finally {
            savingConversion = false;
            form.removeAttribute('aria-busy');
            submit.disabled = false;
        }
    });
}

const initialConversions = document.querySelector('[data-ingredient-conversions]');
if (initialConversions) initializeConversions(initialConversions);

document.querySelectorAll('[data-open-conversions]').forEach(link => link.addEventListener('click', async event => {
    if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
    event.preventDefault();
    if (loadingConversions) return;
    loadingConversions = true;
    link.setAttribute('aria-busy', 'true');
    loadStatus.hidden = false;
    loadStatus.textContent = 'Cargando operaciones…';
    try {
        const response = await fetch(link.href, { credentials: 'same-origin', headers: { Accept: 'text/html' } });
        if (!response.ok) throw new Error('Cannot load');
        const page = new DOMParser().parseFromString(await response.text(), 'text/html');
        const dialog = page.querySelector('[data-ingredient-conversions]');
        if (!dialog) throw new Error('Session expired or unavailable');
        const editDialog = page.querySelector('[data-conversion-edit]');
        document.body.append(document.importNode(dialog, true));
        const inserted = document.body.lastElementChild;
        if (editDialog) document.body.append(document.importNode(editDialog, true));
        initializeConversions(inserted, link);
        loadStatus.hidden = true;
    } catch {
        loadStatus.textContent = 'No se pudieron cargar las operaciones. Revisa la conexión o recarga la página para comprobar tu sesión.';
    } finally {
        loadingConversions = false;
        link.removeAttribute('aria-busy');
    }
}));
