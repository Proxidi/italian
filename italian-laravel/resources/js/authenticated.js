import './dashboard-arrival.js';

const shell = document.querySelector('[data-app-shell]');

if (shell) {
    const toggle = shell.querySelector('[data-sidebar-toggle]');
    const sidebar = shell.querySelector('[data-app-sidebar]');
    const backdrop = shell.querySelector('[data-sidebar-backdrop]');
    const mobile = window.matchMedia('(max-width: 900px)');

    let collapsed = false;

    const mobileOpen = () =>
        shell.classList.contains('is-mobile-open');

    const sync = () => {
        const isMobile = mobile.matches;
        const isOpen = isMobile && mobileOpen();

        shell.classList.toggle('is-collapsed', !isMobile && collapsed);
        backdrop.hidden = !isOpen;
        sidebar.inert = isMobile && !isOpen;

        document.body.classList.toggle('sidebar-open', isOpen);

        toggle.setAttribute(
            'aria-expanded',
            String(isMobile ? isOpen : !collapsed),
        );

        toggle.setAttribute(
            'aria-label',
            isMobile
                ? (isOpen ? 'Cerrar navegación' : 'Abrir navegación')
                : (collapsed ? 'Expandir navegación' : 'Colapsar navegación'),
        );

        // Conserva nombres accesibles cuando solo se muestran iconos.
        sidebar.querySelectorAll('.sidebar-entry').forEach((entry) => {
            const label = entry.querySelector('span')?.textContent.trim();

            if (label) {
                entry.setAttribute('aria-label', label);
                entry.title = label;
            }
        });
    };

    const reducedMotion = window.matchMedia(
        '(prefers-reduced-motion: reduce)',
    );

    const groupStates = new WeakMap();

    const setGroupOpen = (group, shouldOpen) => {
        const summary = group.querySelector(':scope > summary');
        const submenu = group.querySelector(':scope > .sidebar-submenu');
        const previous = groupStates.get(group);

        // Captura la altura actual antes de cancelar una animación.
        const currentHeight = group.open
            ? submenu.getBoundingClientRect().height
            : 0;

        previous?.animation?.cancel();

        const state = {
            target: shouldOpen,
            animation: null,
        };

        groupStates.set(group, state);
        summary.setAttribute('aria-expanded', String(shouldOpen));

        const finish = () => {
            if (groupStates.get(group) !== state) {
                return;
            }

            group.open = shouldOpen;
            submenu.inert = !shouldOpen;
            submenu.style.removeProperty('height');
            state.animation = null;
        };

        if (reducedMotion.matches) {
            finish();
            return;
        }

        // details debe permanecer abierto durante la animación de cierre.
        group.open = true;
        submenu.inert = !shouldOpen;

        const targetHeight = shouldOpen ? submenu.scrollHeight : 0;

        state.animation = submenu.animate(
            [
                { height: `${currentHeight}px` },
                { height: `${targetHeight}px` },
            ],
            {
                duration: 240,
                easing: 'ease-in-out',
            },
        );

        state.animation.onfinish = finish;
    };

    sidebar.querySelectorAll('.sidebar-group').forEach((group) => {
        const summary = group.querySelector(':scope > summary');
        const submenu = group.querySelector(':scope > .sidebar-submenu');

        summary.setAttribute('aria-expanded', String(group.open));
        submenu.inert = !group.open;

        summary.addEventListener('click', (event) => {
            event.preventDefault();

            const state = groupStates.get(group);
            const shouldOpen = !(state?.target ?? group.open);

            // Con scroll independiente, expandimos antes de mostrar el grupo.
            // Así evitamos que un submenú lateral quede recortado.
            if (shouldOpen && !mobile.matches && collapsed) {
                collapsed = false;
                sync();
            }

            if (shouldOpen) {
                const siblings = group.parentElement.parentElement
                    .querySelectorAll(':scope > li > .sidebar-group');

                siblings.forEach((sibling) => {
                    if (sibling === group) {
                        return;
                    }

                    const siblingState = groupStates.get(sibling);

                    if (sibling.open || siblingState?.target) {
                        setGroupOpen(sibling, false);
                    }
                });
            }

            setGroupOpen(group, shouldOpen);
        });
    });

    const closeMobile = (restoreFocus = true) => {
        shell.classList.remove('is-mobile-open');
        sync();

        if (restoreFocus) {
            toggle.focus();
        }
    };

    shell.classList.add('has-layout-js');
    toggle.hidden = false;
    sync();

    toggle.addEventListener('click', () => {
        if (mobile.matches) {
            shell.classList.toggle('is-mobile-open');
            sync();

            if (mobileOpen()) {
                sidebar.querySelector('a, button, summary')?.focus();
            }
        } else {
            collapsed = !collapsed;
            sync();
        }
    });

    backdrop.addEventListener('click', () => closeMobile());

    mobile.addEventListener('change', () => {
        if (sidebar.contains(document.activeElement)) {
            toggle.focus();
        }

        shell.classList.remove('is-mobile-open');
        sync();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            if (mobile.matches && mobileOpen()) {
                event.preventDefault();
                closeMobile();
            }

            return;
        }

        if (event.key !== 'Tab' || !mobile.matches || !mobileOpen()) {
            return;
        }

        const focusable = [...sidebar.querySelectorAll(
            'a[href], button:not([disabled]), summary',
        )].filter((element) => element.getClientRects().length > 0);

        // El botón del header también permite salir de la navegación.
        const cycle = [toggle, ...focusable];
        const first = cycle[0];
        const last = cycle[cycle.length - 1];
        const active = document.activeElement;

        if (event.shiftKey && (active === first || !cycle.includes(active))) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && (active === last || !cycle.includes(active))) {
            event.preventDefault();
            first.focus();
        }
    });
}

const userMenu = document.querySelector('.user-menu');
if (userMenu) {
    const trigger = userMenu.querySelector('summary');
    userMenu.addEventListener('toggle', () => trigger.setAttribute('aria-expanded', String(userMenu.open)));
    document.addEventListener('click', event => {
        if (userMenu.open && !userMenu.contains(event.target)) userMenu.open = false;
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && userMenu.open) {
            userMenu.open = false;
            trigger.focus();
        }
    });
    userMenu.addEventListener('focusout', event => {
        // During pointer focus changes, activeElement can briefly be body.
        // Use the destination so an internal button can receive its click/submit.
        if (event.relatedTarget && !userMenu.contains(event.relatedTarget)) {
            userMenu.open = false;
        }
    });
}
