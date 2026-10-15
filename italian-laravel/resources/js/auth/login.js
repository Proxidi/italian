const form = document.querySelector('[data-login-form]');

if (form) {
    const inputs = [...form.querySelectorAll('input[required]')];

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
        const emptyInputs = inputs.filter(
            (input) => input.validity.valueMissing,
        );

        inputs.forEach(clearFeedback);

        if (emptyInputs.length === 0) {
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
