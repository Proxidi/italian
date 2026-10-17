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

const success = document.querySelector('[data-configuration-success]');
if (success) window.setTimeout(() => success.remove(), 1500);

document.querySelector('[data-copy-configuration]')?.addEventListener('click', async () => {
    const table = document.querySelector('[data-configuration-table]');
    const status = document.querySelector('[data-copy-configuration-status]');
    const text = Array.from(table.querySelectorAll('thead tr, tbody tr[data-configuration-row]'), (row) =>
        Array.from(row.cells).filter((cell) => !cell.hasAttribute('data-copy-exclude')).map((cell) => {
            const value = cell.textContent.trim().replace(/[\t\r\n]+/g, ' ');
            return /^\s*[=+\-@]/u.test(value) ? `'${value}` : value;
        }).join('\t'),
    ).join('\n');
    try {
        await navigator.clipboard.writeText(text);
        status.textContent = 'Página copiada.';
    } catch {
        status.textContent = 'No se pudo copiar. Puedes descargar el archivo Excel.';
    }
});
