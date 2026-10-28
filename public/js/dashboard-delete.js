(() => {
    'use strict';
    const modal = document.querySelector('#dashboard-delete-modal');
    const form = document.querySelector('#dashboard-delete-form');
    if (!modal || !form || modal.dataset.deleteInitialized) return;
    modal.dataset.deleteInitialized = 'true';
    let trigger = null;
    let inFlight = false;
    let restore = [];
    const destination = (value) => {
        try {
            const url = new URL(value, location.href);
            return value && url.origin === location.origin && ['http:', 'https:'].includes(url.protocol) && !url.username && !url.password ? url : null;
        } catch { return null; }
    };

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-delete-url]');
        if (!button || button.matches(':disabled') || inFlight) return;
        event.preventDefault();
        const url = destination(button.dataset.deleteUrl);
        if (!url) return;
        trigger = button;
        form.reset();
        form.action = url.href;
        form.dataset.successAction = button.dataset.deleteSuccessAction === 'redirect' ? 'redirect' : 'reload';
        form.querySelector('[type="submit"]').disabled = false;
        form.querySelector('[data-delete-record]').textContent = button.dataset.deleteName || '';
        window.tabler.Modal.getOrCreateInstance(modal).show();
    });
    form.addEventListener('ajax-form:before-submit', (event) => {
        if (!trigger || !destination(form.getAttribute('action'))) {
            event.preventDefault();
            return;
        }
        inFlight = true;
        const buttons = [trigger, ...form.querySelectorAll('[data-bs-dismiss="modal"]')];
        restore = buttons.map((button) => [button, button.disabled]);
        buttons.forEach((button) => { button.disabled = true; });
    });
    modal.addEventListener('hide.bs.modal', (event) => {
        if (inFlight) event.preventDefault();
    });
    form.addEventListener('ajax-form:complete', (event) => {
        inFlight = false;
        restore.forEach(([button, disabled]) => { button.disabled = disabled; });
        restore = [];
        if (event.detail.outcome === 'success') window.tabler.Modal.getOrCreateInstance(modal).hide();
    });
    modal.addEventListener('hidden.bs.modal', () => {
        form.removeAttribute('action');
        form.querySelector('[type="submit"]').disabled = true;
        if (trigger?.isConnected) trigger.focus();
        trigger = null;
    });
})();
