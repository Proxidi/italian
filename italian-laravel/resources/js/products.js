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
