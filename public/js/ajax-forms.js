(() => {
    'use strict';
    const installed = Symbol.for('SupplyFlow.ajaxForms');
    if (document[installed]) return;
    document[installed] = true;

    const messages = window.SupplyFlowAjaxMessages || {};
    const pending = new WeakSet();
    const managed = new WeakMap();
    let sequence = 0;
    const text = (key) => typeof messages[key] === 'string' ? messages[key] : '';
    const emit = (form, name, detail, cancelable = false) => form.dispatchEvent(new CustomEvent(`ajax-form:${name}`, {
        bubbles: true, cancelable, detail: { form, ...detail },
    }));
    const canonical = (name) => name.replace(/\[([^\]]*)\]/g, (_, part) => part ? `.${part}` : '').replace(/^\./, '');
    const controls = (form) => Array.from(form.elements).filter((field) => field instanceof HTMLElement);
    const byId = (form, id) => [...form.querySelectorAll('[id]'), ...controls(form)].find((el) => el.id === id);
    const visible = (field) => !field.matches(':disabled, [type="hidden"]') && !field.closest('[inert]')
        && field.getClientRects().length > 0 && getComputedStyle(field).visibility === 'visible';
    const focusable = (field) => visible(field) && field.tabIndex >= 0;

    function state(form) {
        if (!managed.has(form)) managed.set(form, { nodes: [], fields: new Map() });
        return managed.get(form);
    }

    function clear(form) {
        const current = state(form);
        current.nodes.forEach((node) => node.remove());
        current.fields.forEach((original, field) => {
            if (!original.invalidClass) field.classList.remove('is-invalid');
            if (original.ariaInvalid === null) field.removeAttribute('aria-invalid');
            else field.setAttribute('aria-invalid', original.ariaInvalid);
            // Remove only IDs attached by this system, retaining all other descriptions.
            const ids = (field.getAttribute('aria-describedby') || '').split(/\s+/).filter((id) => id && !original.ids.has(id));
            if (ids.length) field.setAttribute('aria-describedby', ids.join(' '));
            else if (original.description === null) field.removeAttribute('aria-describedby');
            else field.setAttribute('aria-describedby', original.description);
        });
        current.nodes = [];
        current.fields.clear();
    }

    function notice(form, message, type = 'danger') {
        const node = document.createElement('div');
        node.className = `alert alert-${type}`;
        node.setAttribute('role', type === 'danger' ? 'alert' : 'status');
        node.textContent = message;
        node.tabIndex = -1;
        const host = form.querySelector('[data-ajax-messages]') || form;
        host.prepend(node);
        state(form).nodes.push(node);
        return node;
    }

    function invalid(form, field, id) {
        const current = state(form);
        if (!current.fields.has(field)) current.fields.set(field, {
            invalidClass: field.classList.contains('is-invalid'), ariaInvalid: field.getAttribute('aria-invalid'),
            description: field.getAttribute('aria-describedby'), ids: new Set(),
        });
        current.fields.get(field).ids.add(id);
        field.classList.add('is-invalid');
        field.setAttribute('aria-invalid', 'true');
        const ids = new Set((field.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean));
        ids.add(id);
        field.setAttribute('aria-describedby', [...ids].join(' '));
    }

    function showValidation(form, errors) {
        const fields = controls(form).filter((field) => field.name);
        const placeholders = [...form.querySelectorAll('[data-ajax-error-for]')];
        const unmapped = [];
        const grouped = new Map();
        let displayed = 0;
        let first;
        for (const [key, raw] of Object.entries(errors)) {
            const lines = (Array.isArray(raw) ? raw : [raw]).filter((value) => typeof value === 'string').map((value) => value.slice(0, 2000));
            if (!lines.length) continue;
            displayed++;
            const exact = fields.filter((field) => canonical(field.name) === key);
            // tags[] and nested array groups: Laravel may report tags.0 etc.
            const matched = exact.length ? exact : fields.filter((field) => field.name.endsWith('[]') && key.startsWith(`${canonical(field.name)}.`));
            const placeholder = placeholders.find((el) => canonical(el.dataset.ajaxErrorFor) === key)
                || placeholders.find((el) => matched.some((field) => canonical(field.name) === canonical(el.dataset.ajaxErrorFor)));
            const proxies = matched.map((field) => field.dataset.ajaxFocus ? byId(form, field.dataset.ajaxFocus) : field).filter(Boolean);
            const shown = proxies.find(visible);
            if (!shown) { unmapped.push(...lines); continue; }
            const existing = grouped.get(matched[0]);
            if (existing) {
                existing.textContent += ` ${lines.join(' ')}`;
                continue;
            }
            const feedback = document.createElement('div');
            feedback.id = `ajax-error-${++sequence}`;
            feedback.className = 'invalid-feedback d-block';
            feedback.textContent = lines.join(' ');
            grouped.set(matched[0], feedback);
            const target = placeholder || (matched[0]?.dataset.ajaxErrorTarget ? byId(form, matched[0].dataset.ajaxErrorTarget) : null);
            if (target) target.append(feedback);
            else if (matched.some((field) => ['radio', 'checkbox'].includes(field.type)) && matched.length > 1) {
                // Explicit placeholders are recommended for complex groups; one fallback per group.
                matched.at(-1).after(feedback);
            } else shown.after(feedback);
            state(form).nodes.push(feedback);
            new Set([...matched, ...proxies]).forEach((field) => invalid(form, field, feedback.id));
            first ||= proxies.find(focusable);
        }
        if (unmapped.length || !displayed) {
            const summary = notice(form, unmapped.join(' ') || text('validation'));
            first ||= summary;
        }
        if (first) {
            first.focus({ preventScroll: true });
            first.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }
    }

    function busy(form) {
        const previousBusy = form.getAttribute('aria-busy');
        form.setAttribute('aria-busy', 'true');
        const buttons = controls(form).filter((field) => (field.tagName === 'BUTTON' && field.type === 'submit') || (field.tagName === 'INPUT' && ['submit', 'image'].includes(field.type)));
        const saved = buttons.map((button) => {
            const original = { button, disabled: button.disabled, width: button.style.minWidth, value: button.value };
            if (button.tagName === 'BUTTON') {
                button.style.minWidth = `${button.getBoundingClientRect().width}px`;
                original.children = [...button.childNodes];
                const spinner = document.createElement('span');
                spinner.className = 'spinner-border spinner-border-sm me-2';
                spinner.setAttribute('aria-hidden', 'true');
                button.replaceChildren(spinner, document.createTextNode(text('loading')));
            } else if (button.type === 'submit') button.value = text('loading');
            button.disabled = true;
            return original;
        });
        return () => {
            saved.forEach(({ button, disabled, width, value, children }) => {
                button.disabled = disabled;
                button.style.minWidth = width;
                if (children) button.replaceChildren(...children);
                else button.value = value;
            });
            if (previousBusy === null) form.removeAttribute('aria-busy');
            else form.setAttribute('aria-busy', previousBusy);
        };
    }

    function safeDestination(value) {
        if (typeof value !== 'string' || !value.trim()) return null;
        try {
            const url = new URL(value, location.href);
            return url.origin === location.origin && ['http:', 'https:'].includes(url.protocol) && !url.username && !url.password ? url : null;
        } catch { return null; }
    }

    const flashKey = 'supplyflow.ajax-form.flash';
    function navigate(url, message, reload = false) {
        try { sessionStorage.setItem(flashKey, JSON.stringify({ message, target: url.pathname + url.search, expires: Date.now() + 60000 })); } catch { /* Storage may be disabled. */ }
        if (reload) location.reload();
        else location.assign(url.href);
    }

    function restoreFlash() {
        try {
            const raw = sessionStorage.getItem(flashKey);
            sessionStorage.removeItem(flashKey);
            if (!raw) return;
            const flash = JSON.parse(raw);
            if (flash.expires < Date.now() || flash.target !== location.pathname + location.search || typeof flash.message !== 'string') return;
            const host = document.querySelector('[data-ajax-flash]');
            if (!host) return;
            const alert = document.createElement('div');
            alert.className = 'alert alert-success';
            alert.setAttribute('role', 'status');
            alert.textContent = flash.message.slice(0, 2000);
            host.append(alert);
        } catch { /* Invalid/blocked browser storage never interrupts page rendering. */ }
    }

    document.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.classList.contains('js-ajax-form')) return;
        event.preventDefault();
        if (pending.has(form)) return;
        // Native submissions/requestSubmit already validate; also guard synthetic submit events.
        if (!form.noValidate && !event.submitter?.formNoValidate && !form.reportValidity()) return;
        pending.add(form);
        let release = () => {};
        let response = null;
        let data = null;
        let outcome = 'cancelled';
        const submitter = event.submitter;
        try {
            const formData = new FormData(form);
            if (submitter?.name && !submitter.matches(':disabled')) formData.append(submitter.name, submitter.value);
            if (!emit(form, 'before-submit', { formData, submitter }, true)) return;
            clear(form);
            release = busy(form);
            const url = safeDestination(form.getAttribute('action') || location.href);
            const method = (form.getAttribute('method') || 'GET').toUpperCase();
            if (!url || !['GET', 'POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) throw new Error('request');
            // Keep POST + _method for Laravel multipart PUT/PATCH file uploads.
            const options = { method, credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } };
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            if (csrf) options.headers['X-CSRF-TOKEN'] = csrf;
            if (method === 'GET') {
                for (const [key, value] of formData) url.searchParams.append(key, typeof value === 'string' ? value : value.name);
            } else options.body = formData;
            response = await fetch(url.href, options);
            if (response.redirected) throw new Error('protocol');
            const contentType = response.headers.get('Content-Type') || '';
            if (response.status !== 204 && /\bapplication\/(?:[\w.-]+\+)?json\b/i.test(contentType)) {
                try { data = await response.json(); } catch { /* Handled below as an invalid response. */ }
            }
            if (!response.ok) {
                outcome = 'error';
                if (response.status === 422 && data?.errors && typeof data.errors === 'object' && !Array.isArray(data.errors)) {
                    outcome = 'validation-error';
                    showValidation(form, data.errors);
                    emit(form, outcome, { response, data, errors: data.errors, submitter });
                } else {
                    notice(form, text(`status_${response.status}`) || text('server'));
                    emit(form, 'error', { response, data, kind: 'http', submitter });
                }
                return;
            }
            if (response.status !== 204 && (!data || typeof data !== 'object' || Array.isArray(data) || typeof data.message !== 'string' || !data.message.trim())) throw new Error('protocol');
            outcome = 'success';
            const message = response.status === 204 ? text('success') : data.message.slice(0, 2000);
            notice(form, message, 'success');
            const action = form.dataset.successAction || 'none';
            if (action === 'reset') {
                HTMLFormElement.prototype.reset.call(form);
                emit(form, 'reset', { response, data });
            }
            emit(form, 'success', { response, data, submitter });
            if (action === 'reload') navigate(new URL(location.href), message, true);
            if (action === 'redirect') {
                const destination = safeDestination(data?.redirect_url);
                if (destination) navigate(destination, message);
            }
        } catch (error) {
            outcome = 'error';
            const kind = response ? 'protocol' : 'network';
            notice(form, text(kind));
            emit(form, 'error', { response, data, kind, submitter });
        } finally {
            release();
            pending.delete(form);
            emit(form, 'complete', { response, data, outcome, submitter });
        }
    });

    document.addEventListener('reset', (event) => {
        const form = event.target;
        if (form instanceof HTMLFormElement && form.classList.contains('js-ajax-form') && !pending.has(form)) clear(form);
    });

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', restoreFlash, { once: true });
    else restoreFlash();
})();
