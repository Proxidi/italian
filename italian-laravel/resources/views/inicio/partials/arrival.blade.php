{{-- Visual prototype only: replace this demo content with authorized data when its modules migrate. --}}
<dialog class="arrival-dialog arrival-dialog--welcome" data-welcome-dialog aria-labelledby="welcome-title" aria-describedby="welcome-description">
    <div class="arrival-welcome-brand"><img src="{{ asset('assets/images/auth/logo-blanco.png') }}" alt="The Italian Coffee Company" width="264" height="164"></div>
    <div class="arrival-dialog__body">
        <p class="dashboard-eyebrow">UN NUEVO COMIENZO</p>
        <h2 id="welcome-title" tabindex="-1" autofocus>Qué gusto verte,<br><span>{{ $layout['profile']['short_name'] }}.</span></h2>
        <p id="welcome-description">Un buen café. Un gran equipo.<br>Hagamos que hoy cuente.</p>
        <button type="button" class="arrival-button" data-welcome-continue>Comenzar mi día <span aria-hidden="true">→</span></button>
        <p class="arrival-caption">Any Time is Coffee Time</p>
    </div>
</dialog>
<dialog class="arrival-dialog arrival-dialog--pending" data-pending-demo aria-labelledby="pending-title" aria-describedby="pending-description">
    <div class="arrival-dialog__body">
        <div class="arrival-alert-heading"><span class="arrival-alert-icon" aria-hidden="true">!</span><span class="arrival-demo-label">DEMOSTRACIÓN VISUAL</span></div>
        <p class="dashboard-eyebrow">REQUIERE TU ATENCIÓN</p>
        <h2 id="pending-title" tabindex="-1" autofocus>Hay pendientes<br>por revisar.</h2>
        <p id="pending-description">Así se verán los avisos importantes de tu operación. Estos datos son de ejemplo.</p>
        <div class="arrival-pending-item"><span class="arrival-severity-dot" aria-hidden="true"></span><div><strong>Pedidos por revisar</strong><p>Ejemplo: 2 pedidos de franquicia y 1 de pastelería pendientes de revisión.</p></div></div>
        <p class="arrival-demo-note">Este aviso no consulta pedidos reales ni modifica información.</p>
        <button type="button" class="arrival-button" data-pending-accept disabled>Un momento…</button>
        <p class="arrival-caption" data-pending-timer role="status" aria-live="polite">El botón estará disponible en un segundo.</p>
    </div>
</dialog>
