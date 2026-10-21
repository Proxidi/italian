// Shared list interactions: scoped to the owning listing, never its write forms.
document.addEventListener('click', async event => {
        const button = event.target.closest('[data-copy-page]');
        if (!button) return;
        const listing = button.closest('.ui-list');
        const table = listing?.querySelector('[data-read-table]');
        const status = listing?.querySelector('[data-copy-status]');
        if (!table || !status) return;
        const rows = table.querySelectorAll('thead tr, tbody tr[data-record-row]');
        const values = Array.from(rows, row => Array.from(row.cells)
            .filter(cell => !cell.hasAttribute('data-copy-exclude'))
            .map(cell => {
                const value = (cell.querySelector('[data-copy-value]') ?? cell).textContent.trim().replace(/[\t\r\n]+/g, ' ');
                return /^\s*[=+\-@]/u.test(value) ? `'${value}` : value;
            }).join('\t')).join('\n');
        const text = table.dataset.copyTitle ? table.dataset.copyTitle + '\n' + values : values;
        try {
            await navigator.clipboard.writeText(text);
            status.textContent = 'Página copiada.';
        } catch {
            status.textContent = 'No se pudo copiar. Puedes descargar el archivo Excel.';
        }
});

document.querySelectorAll('[data-dismiss-feedback]').forEach(button => {
    button.addEventListener('click', () => button.closest('[data-feedback]')?.remove());
});

document.querySelectorAll('[data-feedback-dialog]').forEach(dialog => {
    // Timed success exists only where the caller explicitly requests it.
    const previousFocus = document.activeElement;
    dialog.showModal();
    const duration = Number(dialog.dataset.duration);
    const timer = duration > 0 ? window.setTimeout(() => {
        if (dialog.dataset.redirectUrl) {
            window.location.assign(dialog.dataset.redirectUrl);
        } else {
            dialog.close();
        }
    }, duration) : null;
    dialog.addEventListener('close', () => {
        if (!dialog.dataset.redirectUrl) window.clearTimeout(timer);
        if (previousFocus instanceof HTMLElement) previousFocus.focus();
    }, {once: true});
});
