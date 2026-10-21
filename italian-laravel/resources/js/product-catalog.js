import { startActionLoading } from './action-loading.js';

function currentFilters(form) {
    const data = new FormData(form);
    if (document.querySelector('[data-async-list]')) {
        for (const key of ['q', 'family', 'sort', 'direction', 'page']) {
            data.delete(key);
            const value = new URL(location.href).searchParams.get(key);
            if (value !== null) data.set(key, value);
        }
    }
    return data;
}
// Progressive enhancement of the existing authorized HTML routes.
const status = document.querySelector('[data-catalog-status]');
if (status) {
    let busy = false;
    const report = message => {
        status.textContent = message;
        status.hidden = !message;
    };
    const success = message => new Promise(resolve => {
        const dialog = document.querySelector('[data-catalog-success]').content.firstElementChild.cloneNode(true);
        dialog.querySelector('h2').textContent = message;
        document.body.append(dialog);
        dialog.showModal();
        const timer = window.setTimeout(() => dialog.close(), 1000);
        dialog.addEventListener('close', () => {
            window.clearTimeout(timer);
            dialog.remove();
            resolve();
        }, { once: true });
    });
    const getPanel = async url => {
        const response = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'text/html' } });
        if (!response.ok) throw new Error('Cannot load formula');
        const page = new DOMParser().parseFromString(await response.text(), 'text/html');
        const panel = page.querySelector('[data-formula-panel]');
        if (!panel) throw new Error('Session expired or panel unavailable');
        return document.importNode(panel, true);
    };
    function bindForm(form, afterSave) {
        form.addEventListener('submit', async event => {
            event.preventDefault();
            if (busy || !form.reportValidity()) return;
            busy = true;
            const submit = form.querySelector('[type="submit"]');
            const errors = form.querySelector('[data-catalog-errors]');
            errors.hidden = true;
            form.querySelectorAll('[aria-invalid]').forEach(field => field.removeAttribute('aria-invalid'));
            form.setAttribute('aria-busy', 'true');
            submit.disabled = true;
            try {
                const response = await fetch(form.action, {
                    method: 'POST', body: currentFilters(form), credentials: 'same-origin', headers: { Accept: 'application/json' },
                });
                if (!response.ok) {
                    if (response.status === 422) {
                        const data = await response.json();
                        errors.textContent = Object.values(data.errors ?? {}).flat().join(' ');
                        Object.keys(data.errors ?? {}).forEach(name => form.elements.namedItem(name)?.setAttribute('aria-invalid', 'true'));
                    } else {
                        errors.textContent = [401, 419].includes(response.status)
                            ? 'La sesión expiró. Recarga e inicia sesión antes de guardar.'
                            : 'No se guardaron los cambios. Revisa tus permisos y que los registros sigan disponibles.';
                    }
                    errors.hidden = false;
                    errors.focus();
                    return;
                }
                if (!response.headers.get('content-type')?.includes('application/json')) throw new Error('Unexpected response');
                const result = await response.json();
                await success(result.message);
                await afterSave(result);
            } catch {
                errors.textContent = 'No se pudo confirmar la operación. Consulta los datos antes de reintentar; no se reenviará automáticamente.';
                errors.hidden = false;
                errors.focus();
            } finally {
                busy = false;
                form.removeAttribute('aria-busy');
                submit.disabled = false;
            }
        });
    }
    function mountPanel(panel, opener = null) {
        const main = panel.querySelector('[data-formula-dialog]');
        const add = panel.querySelector('[data-formula-editor="create"]');
        const edit = panel.querySelector('[data-formula-editor="update"]');
        let replacing = false;
        main.showModal();
        main.addEventListener('close', () => {
            if (replacing) return;
            if (opener) {
                panel.remove();
                opener.focus();
            } else window.location.assign(main.dataset.returnUrl);
        });
        panel.querySelectorAll('dialog').forEach(dialog => dialog.addEventListener('cancel', event => {
            if (busy) event.preventDefault();
        }));
        panel.querySelectorAll('[data-close-formulas]').forEach(button => button.addEventListener('click', event => {
            event.preventDefault();
            if (!busy) main.close();
        }));
        panel.querySelector('[data-add-formula]')?.addEventListener('click', () => add.showModal());
        panel.querySelectorAll('[data-close-formula-editor]').forEach(button => button.addEventListener('click', () => {
            if (!busy) button.closest('dialog').close();
        }));
        panel.querySelectorAll('[data-formula-editor]').forEach(dialog => dialog.addEventListener('close', () => {
            if (!replacing) main.querySelector('[data-add-formula], [data-edit-formula]')?.focus();
        }));
        panel.querySelectorAll('[data-edit-formula]').forEach(link => link.addEventListener('click', event => {
            if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
            event.preventDefault();
            const form = edit.querySelector('form');
            form.action = link.dataset.action;
            form.elements.cantidad.value = link.dataset.quantity;
            form.elements.unit_id.value = link.dataset.unit;
            form.querySelector('[data-formula-ingredient]').value = link.dataset.ingredient;
            form.querySelector('[data-catalog-errors]').hidden = true;
            form.querySelector('[type="submit"]').disabled = false;
            edit.showModal();
            form.elements.cantidad.focus();
        }));
        const picker = panel.querySelector('[data-formula-picker]');
        const updateUnit = () => {
            const option = picker?.selectedOptions[0];
            const unit = panel.querySelector('[data-formula-unit]');
            if (unit) unit.replaceChildren(new Option(option?.dataset.unitName ?? 'Unidad del insumo', option?.dataset.unit ?? ''));
        };
        picker?.addEventListener('change', updateUnit);
        updateUnit();
        panel.querySelector('[data-formula-editor][data-reopen]')?.showModal();
        panel.querySelectorAll('[data-catalog-form]').forEach(form => bindForm(form, async result => {
            try {
                const updated = await getPanel(result.panel);
                const selected = picker?.value;
                replacing = true;
                panel.replaceWith(updated);
                mountPanel(updated, opener);
                // Legacy keeps the add form open for the next ingredient.
                if (form.dataset.formulaOperation === 'create') {
                    const newPicker = updated.querySelector('[data-formula-picker]');
                    newPicker.value = selected;
                    newPicker.dispatchEvent(new Event('change'));
                    updated.querySelector('[data-formula-editor="create"]').showModal();
                    updated.querySelector('#formula-create-quantity').focus();
                }
            } catch {
                report('Los cambios se guardaron, pero no se pudo actualizar la vista. Consulta de nuevo la fórmula antes de continuar.');
            }
        }));
    }
    const initial = document.querySelector('[data-formula-panel]');
    if (initial) mountPanel(initial);
    document.addEventListener('click', async event => {
        const link = event.target.closest('[data-open-formulas]');
        if (!link) return;
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
        event.preventDefault();
        if (busy) return;
        busy = true;
        const stopLoading = startActionLoading(link, 'Cargando fórmula…');
        report('');
        try {
            const panel = await getPanel(link.href);
            document.body.append(panel);
            mountPanel(panel, link);
        } catch {
            report('No se pudo consultar la fórmula. Revisa la conexión, la sesión y tus permisos.');
        } finally {
            busy = false;
            stopLoading();
        }
    });
    const create = document.querySelector('[data-product-create]');
    if (create) {
        document.addEventListener('click', event => {
            if (!event.target.closest('[data-open-product]')) return;
            if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
            event.preventDefault();
            create.showModal();
        });
        if (create.hasAttribute('data-reopen')) create.showModal();
        create.addEventListener('cancel', event => { if (busy) event.preventDefault(); });
        create.querySelectorAll('[data-close-product]').forEach(button => button.addEventListener('click', () => { if (!busy) create.close(); }));
        create.addEventListener('close', () => document.querySelector('[data-open-product]')?.focus());
        bindForm(create.querySelector('form'), result => window.location.assign(result.redirect));
    }
}
