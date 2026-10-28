(() => {
    'use strict';
    const panel = document.querySelector('#languages-panel');
    if (!panel || panel.dataset.initialized) return;
    panel.dataset.initialized = 'true';
    const words = window.SupplyFlowLanguages;
    const toggles = [...panel.querySelectorAll('.language-toggle')];
    let busy = false;
    const lock = (value) => {
        busy = value;
        panel.setAttribute('aria-busy', String(value));
        toggles.forEach((toggle) => { toggle.disabled = value; });
    };
    const message = (text, success = false) => {
        const host = panel.querySelector('#languages-message');
        host.className = 'alert alert-' + (success ? 'success' : 'danger');
        host.textContent = text;
    };
    const updateSwitcher = (options) => {
        const host = document.querySelector('[data-language-switcher]');
        const template = document.querySelector('[data-language-option]');
        if (!host || !template || !Array.isArray(options)) return;
        const fragment = document.createDocumentFragment();
        options.forEach((option) => {
            const node = template.content.cloneNode(true);
            const form = node.querySelector('form');
            const button = node.querySelector('button');
            form.dataset.locale = option.code;
            button.value = option.code;
            button.lang = option.code;
            button.textContent = option.name;
            if (option.code === document.documentElement.lang) button.setAttribute('aria-current', 'true');
            fragment.append(node);
        });
        host.replaceChildren(fragment);
    };
    const apply = (rows) => {
        if (!Array.isArray(rows) || rows.length !== panel.querySelectorAll('[data-language]').length) throw new Error('state');
        rows.forEach((row) => {
            const element = [...panel.querySelectorAll('[data-language]')].find((el) => el.dataset.language === String(row.id));
            if (!element || typeof row.active !== 'boolean' || typeof row.default !== 'boolean') throw new Error('state');
            for (const field of ['active', 'default']) {
                const input = [...element.querySelectorAll('input')].find((el) => el.dataset.field === field);
                input.checked = row[field];
                element.querySelector('[data-state-label="' + field + '"]').textContent = words.module[field === 'active' ? (row[field] ? 'enabled' : 'disabled') : (row[field] ? 'yes' : 'no')];
            }
        });
    };
    const json = async (response) => {
        if (response.redirected || !/application\/json/i.test(response.headers.get('Content-Type') || '')) throw new Error('response');
        return response.json();
    };
    panel.addEventListener('change', async (event) => {
        const toggle = event.target;
        if (!toggle.matches('.language-toggle') || busy) return;
        lock(true);
        let confirmed = false;
        let response;
        try {
            const body = new FormData();
            body.set('_method', 'PATCH');
            body.set('field', toggle.dataset.field);
            body.set('value', toggle.checked ? '1' : '0');
            response = await fetch(toggle.dataset.url, {
                method: 'POST', credentials: 'same-origin', body,
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            });
            const data = await json(response);
            if (response.ok) {
                apply(data.languages);
                updateSwitcher(data.active_languages);
                confirmed = true;
                message(data.message || words.module.saved, true);
            } else {
                // Reconcile against authoritative state instead of inventing a reverted state.
                const current = await fetch(panel.dataset.stateUrl, { credentials: 'same-origin', headers: { Accept: 'application/json' }, cache: 'no-store' });
                if (!current.ok) throw new Error('state');
                const snapshot = await json(current);
                apply(snapshot.languages);
                updateSwitcher(snapshot.active_languages);
                confirmed = true;
                const validation = response.status === 422 ? Object.values(data.errors || {}).flat().filter((text) => typeof text === 'string').join(' ') : '';
                message(validation || words.ajax['status_' + response.status] || words.module.failed);
            }
        } catch {
            // The write may have committed even if the connection died: keep controls locked.
            message(words.module.unknown);
        } finally {
            if (confirmed) lock(false);
        }
    });
})();
