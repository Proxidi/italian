// Mantiene operativo el endpoint de impresión anterior, si todavía se utiliza.
document.querySelector('[data-print-page]')?.addEventListener('click', () => {
    window.print();
});

const userDialog = document.querySelector('[data-user-create]');
document.querySelector('[data-open-user-create]')?.addEventListener('click', () => userDialog?.showModal());
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
