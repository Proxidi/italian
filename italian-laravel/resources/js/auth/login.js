const form = document.querySelector('[data-login-form]');

if (form) {
    const inputs = [...form.querySelectorAll('input[required]')];
    const submit = form.querySelector('[type="submit"]');
    const label = form.querySelector('[data-submit-label]');
    const spinner = form.querySelector('.login__spinner');
    const status = form.querySelector('[data-login-status]');
    const toggle = form.querySelector('[data-password-toggle]');
    const password = form.querySelector('#password');
    let submitting = false;

    toggle.hidden = false;
    toggle.addEventListener('click', () => {
        const visible = password.type === 'password';
        password.type = visible ? 'text' : 'password';
        toggle.setAttribute('aria-pressed', String(visible));
        toggle.setAttribute('aria-label', visible ? 'Ocultar contraseña' : 'Mostrar contraseña');
    });
    window.addEventListener('pageshow', () => {
        submitting = false;
        submit.disabled = false;
        form.removeAttribute('aria-busy');
        label.textContent = 'Iniciar sesión';
        spinner.hidden = true;
        status.textContent = '';
        password.type = 'password';
        toggle.setAttribute('aria-pressed', 'false');
        toggle.setAttribute('aria-label', 'Mostrar contraseña');
    });

    const feedbackFor = (input) =>
        document.getElementById(`${input.id}-feedback`);

    const showFeedback = (input) => {
        const feedback = feedbackFor(input);

        input.setAttribute('aria-invalid', 'true');
        input.dataset.clientInvalid = 'true';

        feedback.textContent = input.dataset.emptyMessage;
        feedback.hidden = false;
    };

    const clearFeedback = (input) => {
        if (input.dataset.clientInvalid !== 'true') {
            return;
        }

        input.setAttribute('aria-invalid', 'false');
        delete input.dataset.clientInvalid;

        const feedback = feedbackFor(input);

        feedback.textContent = '';
        feedback.hidden = true;
    };

    // Sin JavaScript, siguen funcionando los atributos required
    // y la validación del servidor.
    form.noValidate = true;

    form.addEventListener('submit', (event) => {
        if (submitting) {
            event.preventDefault();
            return;
        }
        const emptyInputs = inputs.filter(
            (input) => input.validity.valueMissing,
        );

        inputs.forEach(clearFeedback);

        if (emptyInputs.length === 0) {
            submitting = true;
            submit.disabled = true;
            form.setAttribute('aria-busy', 'true');
            label.textContent = 'Iniciando sesión…';
            spinner.hidden = false;
            status.textContent = 'Comprobando tus credenciales.';
            return;
        }

        event.preventDefault();

        emptyInputs.forEach(showFeedback);
        emptyInputs[0].focus();
    });

    inputs.forEach((input) => {
        input.addEventListener('input', () => {
            if (!input.validity.valueMissing) {
                clearFeedback(input);
            }
        });
    });
}
