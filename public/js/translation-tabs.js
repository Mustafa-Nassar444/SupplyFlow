(() => {
    const reveal = (field) => {
        const pane = field?.closest('.tab-pane');
        const form = field?.form;
        if (!pane || !form) return;
        const tab = [...form.querySelectorAll('[data-translation-tabs] [role="tab"]')]
            .find((button) => button.getAttribute('aria-controls') === pane.id);
        if (tab) window.tabler.Tab.getOrCreateInstance(tab).show();
    };

    // Capture native validation before the browser tries to focus a hidden required input.
    const validating = new WeakSet();
    document.addEventListener('invalid', (event) => {
        const form = event.target.form;
        if (!form?.querySelector('[data-translation-tabs]') || validating.has(form)) return;
        validating.add(form);
        reveal(event.target);
        queueMicrotask(() => validating.delete(form));
    }, true);
    document.addEventListener('ajax-form:validation-error', (event) => {
        const form = event.target;
        if (!form.querySelector('[data-translation-tabs]')) return;
        const fields = [...form.elements];
        const field = fields.find((input) => Object.hasOwn(event.detail.errors, input.name));
        if (!field) return;
        reveal(field);
        field.focus({ preventScroll: true });
        field.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    });
})();
