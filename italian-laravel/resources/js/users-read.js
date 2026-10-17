const copyButton = document.querySelector('[data-copy-page]');
const copyStatus = document.querySelector('[data-copy-status]');

copyButton?.addEventListener('click', async () => {
    const table = document.querySelector('[data-read-table]');

    if (!table || !copyStatus) {
        return;
    }

    const rows = table.querySelectorAll(
        'thead tr, tbody tr[data-record-row]',
    );

    const text = Array.from(rows, (row) =>
        Array.from(row.cells)
            .filter((cell) => !cell.hasAttribute('data-copy-exclude'))
            .map((cell) => {
                const source = cell.querySelector('[data-copy-value]') ?? cell;
                const value = source.textContent
                    .trim()
                    .replace(/[\t\r\n]+/g, ' ');

                return /^\s*[=+\-@]/u.test(value) ? `'${value}` : value;
            })
            .join('\t'),
    ).join('\n');

    try {
        await navigator.clipboard.writeText(text);
        copyStatus.textContent = 'Página copiada.';
    } catch {
        copyStatus.textContent =
            'No se pudo copiar. Puedes descargar el archivo Excel.';
    }
});

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

document.querySelector('[data-dismiss-flash]')?.addEventListener('click', (event) => {
    event.currentTarget.closest('.users-flash')?.remove();
});

const createdDialog = document.querySelector('[data-user-created-dialog]');
if (createdDialog) {
    createdDialog.showModal();

    window.setTimeout(() => {
        window.location.assign(createdDialog.dataset.redirectUrl);
    }, 2200);
}
