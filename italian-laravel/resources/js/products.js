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

const conversionsDialog = document.querySelector('[data-ingredient-conversions]');
const conversionEditDialog = document.querySelector('[data-conversion-edit]');
if (conversionsDialog) {
    conversionsDialog.showModal();
    conversionsDialog.addEventListener('close', () => window.location.assign(conversionsDialog.dataset.returnUrl));
    document.querySelectorAll('[data-close-conversions]').forEach(button => button.addEventListener('click', () => conversionsDialog.close()));
    conversionEditDialog?.addEventListener('close', () => conversionsDialog.querySelector('[data-edit-conversion]')?.focus());
    document.querySelectorAll('[data-edit-conversion]').forEach(button => button.addEventListener('click', () => {
        const form = conversionEditDialog.querySelector('form');
        form.action = button.dataset.action;
        form.elements.bodega.value = button.dataset.bodega;
        form.elements.pedido.value = button.dataset.pedido;
        form.elements._conversion_id.value = button.dataset.id;
        conversionEditDialog.showModal();
        form.elements.bodega.focus();
    }));
    document.querySelectorAll('[data-close-conversion-edit]').forEach(button => button.addEventListener('click', () => conversionEditDialog.close()));
    if (conversionEditDialog?.hasAttribute('data-reopen')) conversionEditDialog.showModal();
}
document.querySelectorAll('[data-conversion-form]').forEach(form => {
    form.addEventListener('submit', event => {
        if (form.dataset.submitting) {
            event.preventDefault();
            return;
        }
        if (!form.checkValidity()) return;
        form.dataset.submitting = 'true';
        form.setAttribute('aria-busy', 'true');
        form.querySelector('[type="submit"]').disabled = true;
    });
    window.addEventListener('pageshow', () => {
        delete form.dataset.submitting;
        form.removeAttribute('aria-busy');
        form.querySelector('[type="submit"]').disabled = false;
    });
});
