<div class="users-read__tools">
    <a
        class="users-read__export"
        href="{{ route($exportRoute, [...$filters, 'format' => 'xlsx']) }}"
        aria-label="Descargar listado en Excel"
    >
        Excel
    </a>

    <a
        class="users-read__export"
        href="{{ route($exportRoute, [...$filters, 'format' => 'pdf']) }}"
        aria-label="Descargar listado en PDF"
    >
        PDF
    </a>

    <button
        class="users-read__export"
        type="button"
        data-copy-page
        @disabled($records->isEmpty())
        aria-label="Copiar los registros de esta página"
    >
        Copy
    </button>

    <span
        class="users-read__copy-status"
        role="status"
        aria-live="polite"
        data-copy-status
    ></span>
</div>
