const daysDialog = document.querySelector('[data-days-dialog]');
document.querySelector('[data-open-days]')?.addEventListener('click', () => daysDialog?.showModal());
document.querySelectorAll('[data-close-days]').forEach((button) => {
    button.addEventListener('click', () => daysDialog?.close());
});
if (daysDialog?.hasAttribute('data-reopen')) daysDialog.showModal();

document.querySelectorAll('[data-configuration-form]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (form.dataset.submitting === 'true') {
            event.preventDefault();
            return;
        }
        if (form.checkValidity()) {
            form.dataset.submitting = 'true';
            form.setAttribute('aria-busy', 'true');
            form.querySelector('[type="submit"]').disabled = true;
        }
    });
});

// Restore controls when returning through the browser's back/forward cache.
window.addEventListener('pageshow', () => {
    document.querySelectorAll('[data-configuration-form][data-submitting]').forEach((form) => {
        delete form.dataset.submitting;
        form.removeAttribute('aria-busy');
        form.querySelector('[type="submit"]').disabled = false;
    });
});
