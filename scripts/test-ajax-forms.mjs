// Uses an existing Playwright installation; does not install a browser or application dependency.
// PLAYWRIGHT_MODULE_DIR points at node_modules containing playwright (e.g. bundled workspace tools).
import { createRequire } from 'node:module';
import { resolve, join } from 'node:path';
import assert from 'node:assert/strict';

const require = createRequire(join(resolve(process.env.PLAYWRIGHT_MODULE_DIR || 'node_modules'), '_test.cjs'));
const { chromium } = require('playwright');
const browser = await chromium.launch({ channel: 'chrome', headless: true });
const origin = process.env.AJAX_TEST_URL || 'http://127.0.0.1:8015';
let passed = 0;
try {
    const page = await browser.newPage();
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    await page.goto(`${origin}/dashboard/examples/form`);
    await page.locator('#demo-form').waitFor();
    const test = async (name, run) => { await run(); console.log(`PASS ${++passed}: ${name}`); };
    const mount = async (markup, attributes = '') => {
        await page.evaluate(({ markup, attributes }) => {
            document.querySelector('#fixture')?.remove();
            const host = document.createElement('div');
            host.id = 'fixture';
            host.innerHTML = `<form class="js-ajax-form" action="/_ajax-test" method="post" ${attributes}>${markup}</form>`;
            document.querySelector('main').append(host);
            window.testEvents = [];
            for (const name of ['before-submit', 'success', 'validation-error', 'error', 'complete', 'reset']) {
                host.addEventListener(`ajax-form:${name}`, (event) => window.testEvents.push({ name, kind: event.detail.kind, data: event.detail.data, outcome: event.detail.outcome }));
            }
        }, { markup, attributes });
    };
    const settle = () => page.waitForFunction(() => window.testEvents.some((event) => event.name === 'complete'));
    const mock = async (status, body, contentType = 'application/json') => {
        await page.unroute('**/_ajax-test');
        await page.route('**/_ajax-test', (route) => route.fulfill({ status, contentType, body: typeof body === 'string' ? body : JSON.stringify(body) }));
    };
    const submit = () => page.locator('#fixture button[type=submit]').first().click();
    const field = '<input name="name" value="Example" aria-describedby="help"><span id="help">Hint</span><button type="submit" name="intent" value="save"><strong>Save</strong></button><button type="submit" disabled>Disabled</button>';

    await test('actual Laravel create, localized 422, then JSON success', async () => {
        await page.locator('#demo-form button[type=submit]').click();
        await page.locator('#demo-form .invalid-feedback').filter({ hasText: /valid/ }).first().waitFor();
        await page.locator('#name').fill('Demo');
        await page.locator('#email').fill('demo@example.test');
        await page.locator('#acknowledge').check();
        await page.locator('#demo-form button[type=submit]').click();
        await page.locator('#demo-form .alert-success').waitFor();
        assert.equal(await page.locator('#name').inputValue(), 'Demo');
    });
    await test('actual Laravel PATCH multipart upload, nested 422, reset hook', async () => {
        const form = page.locator('form.js-ajax-form').nth(1);
        await form.locator('button[type=submit]').click();
        await form.locator('[data-ajax-error-for] .invalid-feedback').waitFor();
        assert.equal(await page.locator('#edit-quantity').getAttribute('aria-invalid'), 'true');
        await page.locator('#edit-quantity').fill('3');
        await page.locator('#edit-file').setInputFiles({ name: 'sample.txt', mimeType: 'text/plain', buffer: Buffer.from('example') });
        await form.locator('button[type=submit]').click();
        await form.locator('.alert-success').waitFor();
        assert.equal(await page.locator('#edit-file').inputValue(), '');
    });
    await test('FormData submitter, disabled fields, duplicate guard, button restoration, single install', async () => {
        await mount(field + '<input name="skip" value="no" disabled>');
        await page.addScriptTag({ url: `${origin}/js/ajax-forms.js` });
        let calls = 0;
        await page.route('**/_ajax-test', async (route) => {
            calls++;
            const request = route.request();
            assert.match(request.postData(), /name="intent"\r\n\r\nsave/);
            assert.doesNotMatch(request.postData(), /name="skip"/);
            assert.equal(request.headers().accept, 'application/json');
            assert.equal(request.headers()['x-requested-with'], 'XMLHttpRequest');
            assert.match(request.headers()['content-type'], /^multipart\/form-data; boundary=/);
            await new Promise((done) => setTimeout(done, 100));
            await route.fulfill({ json: { message: 'Saved' } });
        });
        await page.locator('#fixture form').evaluate((form) => { form.requestSubmit(form.querySelector('button')); form.requestSubmit(); });
        await settle();
        assert.equal(calls, 1);
        assert.equal(await page.locator('#fixture button strong').textContent(), 'Save');
        assert.equal(await page.locator('#fixture button').nth(1).isDisabled(), true);
        assert.equal(await page.locator('#fixture form').getAttribute('aria-busy'), null);
    });
    await test('nested names, tags[], radio groups, explicit proxy, safe text, preserved ARIA and static errors', async () => {
        await mount(field + `<input name="items[0][quantity]"><input name="tags[]"><input name="tags[]"><div data-ajax-error-for="tags"></div>
            <label><input name="choice" type="radio" value="a"> A</label><label><input name="choice" type="radio" value="b"> B</label><div data-ajax-error-for="choice"></div>
            <input type="hidden" name="enhanced" data-ajax-focus="proxy" data-ajax-error-target="proxy-error"><input id="proxy"><div id="proxy-error"></div>
            <input name="static" class="is-invalid" aria-invalid="true"><div class="invalid-feedback" id="static-error">Static error</div>`);
        await mock(422, { errors: { name: ['<img src=x onerror=alert(1)>'], 'items.0.quantity': ['Quantity'], 'tags.0': ['Tag A'], 'tags.1': ['Tag B'], choice: ['Choose'], enhanced: ['Enhanced'], unknown: ['Summary'] } });
        await submit(); await settle();
        assert.equal(await page.locator('#fixture [data-ajax-error-for="tags"] .invalid-feedback').count(), 1);
        assert.equal(await page.locator('#fixture [data-ajax-error-for="choice"] .invalid-feedback').count(), 1);
        assert.equal(await page.locator('#proxy').getAttribute('aria-invalid'), 'true');
        assert.equal(await page.locator('#fixture img').count(), 0);
        assert.match(await page.locator('#fixture [name=name]').getAttribute('aria-describedby'), /^help ajax-error-/);
        assert.match(await page.locator('#fixture .alert').textContent(), /Summary/);
        await mock(200, { message: 'Saved' });
        await page.evaluate(() => { window.testEvents = []; }); await submit(); await settle();
        assert.equal(await page.locator('#fixture [name=name]').getAttribute('aria-describedby'), 'help');
        assert.equal(await page.locator('#fixture [name=name]').getAttribute('aria-invalid'), null);
        assert.equal(await page.locator('#static-error').count(), 1);
        assert.equal(await page.locator('#fixture [name=static]').getAttribute('aria-invalid'), 'true');
    });
    await test('401/403/419/429/500, invalid JSON, HTML, and network failure restore buttons', async () => {
        for (const status of [401, 403, 419, 429, 500, 200]) {
            await mount(field);
            await mock(status, '<html>private stack trace</html>', 'text/html');
            await submit(); await settle();
            assert.equal(await page.locator('#fixture .alert-success').count(), 0);
            assert.doesNotMatch(await page.locator('#fixture .alert').textContent(), /stack trace/);
            assert.equal(await page.locator('#fixture button').first().isEnabled(), true);
        }
        await mount(field); await mock(200, '{broken'); await submit(); await settle();
        assert.equal(await page.locator('#fixture .alert-danger').count(), 1);
        await mount(field); await page.unroute('**/_ajax-test');
        await page.route('**/_ajax-test', (route) => route.abort('failed'));
        await submit(); await settle();
        assert.equal(await page.locator('#fixture .alert-danger').count(), 1);
    });
    await test('204, explicit reset, cancelable event, native validation, and unmarked forms', async () => {
        await mount(field, 'data-success-action="reset"');
        await page.locator('#fixture [name=name]').fill('Changed');
        await mock(204, ''); await submit(); await settle();
        assert.equal(await page.locator('#fixture [name=name]').inputValue(), 'Example');
        assert.equal(await page.evaluate(() => window.testEvents.some((event) => event.name === 'reset')), true);
        await mount(field);
        await page.locator('#fixture form').evaluate((form) => form.addEventListener('ajax-form:before-submit', (event) => event.preventDefault()));
        await submit(); await settle();
        assert.equal(await page.locator('#fixture .alert').count(), 0);
        await mount('<input name="name" required><button type="submit">Save</button>');
        await submit();
        assert.equal(await page.evaluate(() => window.testEvents.length), 0);
        await mount(field);
        const intercepted = await page.locator('#fixture form').evaluate((form) => {
            form.classList.remove('js-ajax-form');
            const event = new Event('submit', { bubbles: true, cancelable: true });
            form.dispatchEvent(event);
            return event.defaultPrevented;
        });
        assert.equal(intercepted, false);
    });
    await test('clicked alternative submit button, missing redirect, and followed login redirect', async () => {
        await mount(field + '<button type="submit" name="intent" value="alternative">Alternative</button>', 'data-success-action="redirect"');
        await page.unroute('**/_ajax-test');
        await page.route('**/_ajax-test', (route) => {
            assert.match(route.request().postData(), /name="intent"\r\n\r\nalternative/);
            assert.doesNotMatch(route.request().postData(), /name="intent"\r\n\r\nsave/);
            return route.fulfill({ json: { message: 'Saved with no redirect' } });
        });
        await page.locator('#fixture button').last().click(); await settle();
        assert.match(page.url(), /dashboard\/examples\/form$/);
        assert.equal(await page.locator('#fixture .alert-success').textContent(), 'Saved with no redirect');
        await mount(field);
        await page.unroute('**/_ajax-test');
        await page.route('**/_ajax-test', (route) => route.fulfill({ status: 302, headers: { Location: `${origin}/dashboard` } }));
        await submit(); await settle();
        assert.equal(await page.locator('#fixture .alert-success').count(), 0);
        assert.equal(await page.locator('#fixture .alert-danger').count(), 1);
    });
    await test('dynamically inserted modal form, two-form isolation, unsafe redirect stays put', async () => {
        await mount(field, 'data-success-action="redirect"');
        await page.locator('#fixture').evaluate((host) => { host.className = 'modal show'; host.style.display = 'block'; });
        await mock(200, { message: 'Saved', redirect_url: 'https://example.test/evil' });
        await submit(); await settle();
        assert.match(page.url(), /dashboard\/examples\/form$/);
        assert.equal(await page.locator('#demo-form [name=name]').getAttribute('aria-invalid'), null);
    });
    await test('same-origin redirect and reload preserve success message after navigation', async () => {
        await mount(field, 'data-success-action="redirect"');
        await mock(200, { message: 'After redirect', redirect_url: '/dashboard?ajax=1' });
        await Promise.all([page.waitForURL('**/dashboard?ajax=1'), submit()]);
        await page.locator('[data-ajax-flash] .alert-success').waitFor();
        assert.equal(await page.locator('[data-ajax-flash]').textContent(), 'After redirect');
        await mount(field, 'data-success-action="reload"');
        await mock(200, { message: 'After reload' });
        await Promise.all([page.waitForEvent('load'), submit()]);
        await page.locator('[data-ajax-flash] .alert-success').waitFor();
        assert.equal(await page.locator('[data-ajax-flash]').textContent(), 'After reload');
    });
    await test('Arabic feedback is translated and RTL remains intact', async () => {
        await page.goto(`${origin}/dashboard/examples/form`);
        await page.getByRole('button', { name: 'Language', exact: true }).click();
        await page.getByRole('button', { name: 'العربية', exact: true }).click();
        await page.waitForFunction(() => document.documentElement.lang === 'ar');
        await mount(field); await mock(419, {}); await submit(); await settle();
        assert.match(await page.locator('#fixture .alert').textContent(), /حدّث الصفحة/);
        assert.equal(await page.locator('html').getAttribute('dir'), 'rtl');
    });
    assert.deepEqual(errors, []);
    console.log(`${passed} browser scenarios passed; no uncaught page errors.`);
} finally {
    await browser.close();
}
