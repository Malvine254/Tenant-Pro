(() => {
    'use strict';

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    const showStatus = (form, message, isError = false) => {
        let status = form.querySelector('[data-ajax-status]');
        if (!status) {
            status = document.createElement('p');
            status.dataset.ajaxStatus = 'true';
            status.setAttribute('role', 'status');
            form.appendChild(status);
        }
        status.textContent = message;
        status.classList.toggle('ajax-status-error', isError);
        status.classList.toggle('ajax-status-success', !isError);
    };

    document.querySelectorAll('form[data-ajax-form]').forEach(form => {
        form.addEventListener('submit', async event => {
            event.preventDefault();
            const submit = form.querySelector('[type="submit"]');
            const originalLabel = submit?.textContent;
            if (submit) {
                submit.disabled = true;
                submit.textContent = 'Saving...';
            }

            try {
                const response = await fetch(form.action || window.location.href, {
                    method: (form.method || 'POST').toUpperCase(),
                    body: new FormData(form),
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        ...(csrf ? {'X-CSRF-TOKEN': csrf} : {})
                    },
                    credentials: 'same-origin'
                });
                const contentType = response.headers.get('content-type') || '';
                const payload = contentType.includes('application/json') ? await response.json() : null;
                if (!response.ok) {
                    const message = payload?.message || Object.values(payload?.errors || {}).flat()[0] || 'Unable to save this request.';
                    throw new Error(message);
                }

                showStatus(form, payload?.message || 'Saved successfully.');
                form.dispatchEvent(new CustomEvent('ajax:success', {detail: payload}));
            } catch (error) {
                showStatus(form, error.message || 'Unable to save this request.', true);
                form.dispatchEvent(new CustomEvent('ajax:error', {detail: error}));
            } finally {
                if (submit) {
                    submit.disabled = false;
                    submit.textContent = originalLabel;
                }
            }
        });
    });
})();
