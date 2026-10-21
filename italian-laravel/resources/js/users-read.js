import './async-list.js';

// Mantiene operativo el endpoint de impresión anterior, si todavía se utiliza.
document.addEventListener('click', (event) => {
    if (!event.target.closest('[data-print-page]')) return;
    window.print();
});

const userDialog = document.querySelector('[data-user-create]');
document.addEventListener('click', (event) => {
    if (!event.target.closest('[data-open-user-create]')) return;
    userDialog?.showModal();
});
document.querySelectorAll('[data-close-user-create]').forEach((button) => {
    button.addEventListener('click', () => userDialog?.close());
});
if (userDialog?.hasAttribute('data-reopen')) {
    userDialog.showModal();
}
userDialog?.querySelector('form')?.addEventListener('submit', (event) => {
    if (event.currentTarget.checkValidity()) {
        event.currentTarget.querySelector('[type="submit"]').disabled = true;
    }
});
