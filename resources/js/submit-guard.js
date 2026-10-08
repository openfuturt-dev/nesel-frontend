/**
 * Locks a form's submit button while the form submits, so a double click cannot
 * send it twice. The browser only fires "submit" after native validation has
 * passed, so a form it rejects is never locked. The server-side submission token
 * remains the authoritative protection against duplicates.
 *
 * @param {HTMLFormElement} form
 * @param {{ busyLabel?: string, win?: Window }} [options]
 * @returns {() => void} Restores the idle state.
 */
export function guardSubmission(form, { busyLabel = 'Envoi en cours…', win = window } = {}) {
    const button = form.querySelector('[type="submit"]');
    const label = form.querySelector('[data-submit-label]');

    if (!button) {
        return () => {};
    }

    const idleLabel = label?.textContent ?? '';

    const reset = () => {
        delete form.dataset.submitting;
        button.disabled = false;
        button.removeAttribute('aria-busy');

        if (label) {
            label.textContent = idleLabel;
        }
    };

    form.addEventListener('submit', (event) => {
        if (form.dataset.submitting === 'true') {
            event.preventDefault();

            return;
        }

        form.dataset.submitting = 'true';
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');

        if (label) {
            label.textContent = busyLabel;
        }
    });

    // A page restored from the back/forward cache must not stay locked.
    win.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            reset();
        }
    });

    return reset;
}
