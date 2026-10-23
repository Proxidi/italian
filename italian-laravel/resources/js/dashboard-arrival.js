const home = document.querySelector('[data-dashboard-home]');
if (home) {
    const welcome = document.querySelector('[data-welcome-dialog]');
    const pending = document.querySelector('[data-pending-demo]');
    const accept = pending.querySelector('[data-pending-accept]');
    const timerStatus = pending.querySelector('[data-pending-timer]');
    // Per tab and login session; no backend notification state or business data.
    const key = `italian-arrival:${home.dataset.welcomeKey}`;
    let seen = false;
    try { seen = sessionStorage.getItem(key) === 'seen'; } catch { /* Storage may be unavailable. */ }
    if (!seen) {
        let ready = false;
        let timer;
        welcome.addEventListener('close', () => {
            pending.showModal();
            timer = window.setTimeout(() => {
                ready = true;
                accept.disabled = false;
                accept.textContent = 'Entendido, continuar';
                timerStatus.textContent = 'Ya puedes continuar al panel.';
            }, 1000);
        }, { once: true });
        // Escape also advances the welcome, preserving the two-layer sequence.
        welcome.querySelector('[data-welcome-continue]').addEventListener('click', () => welcome.close());
        pending.addEventListener('cancel', event => { if (!ready) event.preventDefault(); });
        pending.addEventListener('close', () => {
            window.clearTimeout(timer);
            try { sessionStorage.setItem(key, 'seen'); } catch { /* No persistent requirement. */ }
            document.getElementById('main-content').focus({ preventScroll: true });
        }, { once: true });
        accept.addEventListener('click', () => { if (ready) pending.close(); });
        welcome.showModal();
    }
}
