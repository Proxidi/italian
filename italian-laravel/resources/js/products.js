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
const success = document.querySelector('[data-unit-success]');
if (success) window.setTimeout(() => success.remove(), 1000);
document.querySelector('[data-copy-units]')?.addEventListener('click', async () => {
    const status = document.querySelector('[data-copy-status]');
    const names = Array.from(document.querySelectorAll('[data-unit-name]'), cell => cell.textContent.trim());
    try {
        await navigator.clipboard.writeText(['Unidades Insumos', 'Nombre', ...names].join('\n'));
        status.textContent = 'Datos copiados.';
    } catch {
        status.textContent = 'No se pudo copiar. Utiliza Excel o PDF.';
    }
});
