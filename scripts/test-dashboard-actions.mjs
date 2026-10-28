// Run only against an isolated fixture with the synthetic language-test admin account.
import { createRequire } from 'node:module';
import { resolve, join } from 'node:path';
import assert from 'node:assert/strict';
const require = createRequire(join(resolve(process.env.PLAYWRIGHT_MODULE_DIR || 'node_modules'), '_test.cjs'));
const { chromium } = require('playwright');
const browser = await chromium.launch({ channel: 'chrome', headless: true });
const base = process.env.DASHBOARD_ACTIONS_TEST_URL || 'http://127.0.0.1:8016';
try {
    const page = await browser.newPage({ viewport: { width: 1440, height: 950 } });
    const errors = [];
    page.on('pageerror', (e) => errors.push(e.message));
    await page.goto(base + '/login');
    await page.locator('#email').fill('language-test@example.test');
    await page.locator('#password').fill('LanguageTest!123');
    await Promise.all([page.waitForURL('**/dashboard'), page.locator('button[type=submit]').click()]);
    assert.equal(await page.locator('#main-content').count(), 1);
    assert.equal(await page.locator('#dashboard-delete-form').count(), 1);
    assert.equal(await page.getByText("You're logged in!", { exact: true }).count(), 0);
    await page.goto(base + '/dashboard/categories/create');
    const stamp = String(Date.now());
    await page.locator('#name_ar').fill('فئة اختبار ' + stamp);
    await page.locator('#category-language-tab-en').click();
    await page.locator('#name_en').fill('Test category ' + stamp);
    const createResponse = page.waitForResponse((r) => r.url().endsWith('/dashboard/categories') && r.request().method() === 'POST');
    await page.locator('#category-create-form [type=submit]').click();
    const created = await createResponse;
    assert.equal(created.status(), 201);
    const id = (await created.json()).category_id;
    await page.goto(base + '/dashboard/categories');
    const row = () => page.locator('[data-category-id="' + id + '"]');
    await row().locator('a').click();
    await page.waitForURL('**/categories/' + id + '/edit');
    assert.equal(await page.locator('#name_en').inputValue(), 'Test category ' + stamp);
    await page.locator('#name_ar').fill('تعديل اختبار ' + stamp);
    await page.locator('#category-language-tab-en').click();
    await page.locator('#name_en').fill('Updated category ' + stamp);
    await Promise.all([page.waitForURL('**/dashboard/categories'), page.locator('#category-edit-form [type=submit]').click()]);
    await page.locator('[data-ajax-flash] .alert-success').waitFor();
    assert.match(await row().textContent(), new RegExp(stamp));
    const openDelete = async () => {
        await row().locator('[data-delete-url]').click();
        await page.locator('#dashboard-delete-modal.show').waitFor();
    };
    const modal = page.locator('#dashboard-delete-modal');
    const form = page.locator('#dashboard-delete-form');
    await openDelete();
    assert.match(await form.locator('[data-delete-record]').textContent(), new RegExp(stamp));
    await modal.locator('.modal-footer [data-bs-dismiss]').click();
    await modal.waitFor({ state: 'hidden' });
    assert.equal(await row().count(), 1);

    // A rejected delete must keep the modal open and restore controls.
    const endpoint = '**/dashboard/categories/' + id;
    await page.route(endpoint, (route) => route.fulfill({ status: 422, json: { message: 'In use', errors: { delete: ['Protected record'] } } }));
    await openDelete();
    await form.locator('[type=submit]').click();
    await form.locator('.alert-danger').waitFor();
    assert.equal(await form.locator('[type=submit]').isEnabled(), true);
    assert.equal(await modal.isVisible(), true);
    assert.equal(await row().count(), 1);
    await modal.locator('.modal-footer [data-bs-dismiss]').click();
    await modal.waitFor({ state: 'hidden' });
    await page.unroute(endpoint);

    // Hold a real delete to inspect loading and attempt duplicate submission.
    let release;
    const gate = new Promise((done) => { release = done; });
    let deletes = 0;
    await page.route(endpoint, async (route) => {
        deletes++;
        assert.match(route.request().postData(), /name="_method"\r\n\r\nDELETE/);
        assert.match(route.request().postData(), /name="_token"/);
        await gate;
        await route.continue();
    });
    await openDelete();
    assert.equal(await form.locator('.alert').count(), 0);
    await form.evaluate((el) => { el.requestSubmit(); el.requestSubmit(); });
    await page.waitForFunction(() => document.querySelector('#dashboard-delete-form').getAttribute('aria-busy') === 'true');
    assert.equal(await modal.locator('.modal-footer [data-bs-dismiss]').isDisabled(), true);
    await page.evaluate(() => window.tabler.Modal.getInstance(document.querySelector('#dashboard-delete-modal')).hide());
    assert.equal(await modal.isVisible(), true);
    const reloaded = page.waitForEvent('load');
    release();
    await reloaded;
    await page.locator('[data-ajax-flash] .alert-success').waitFor();
    assert.equal(deletes, 1);
    assert.equal(await row().count(), 0);
    await page.unroute(endpoint);

    // Reuse the same layout form for a dynamically added trigger for a different module.
    await page.evaluate(() => {
        const button = document.createElement('button');
        button.id = 'dynamic-delete';
        button.type = 'button';
        button.dataset.deleteUrl = '/_test-shared-delete';
        button.dataset.deleteName = '<img src=x onerror=alert(1)>';
        button.textContent = 'Dynamic record';
        document.querySelector('main').append(button);
    });
    await page.route('**/_test-shared-delete', (route) => route.fulfill({ json: { message: 'Dynamic delete succeeded' } }));
    await page.locator('#dynamic-delete').click();
    await modal.waitFor({ state: 'visible' });
    assert.equal(await form.locator('[data-delete-record]').textContent(), '<img src=x onerror=alert(1)>');
    assert.equal(await form.locator('[data-delete-record] img').count(), 0);
    await Promise.all([page.waitForEvent('load'), form.locator('[type=submit]').click()]);
    await page.locator('[data-ajax-flash] .alert-success').waitFor();
    assert.equal(await page.locator('[data-ajax-flash]').textContent(), 'Dynamic delete succeeded');
    await page.setViewportSize({ width: 390, height: 844 });
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
    assert.deepEqual(errors, []);
    console.log('PASS: unified Tabler dashboard, translated edit/update, cancel, rejected delete, duplicate guard/loading lock, real delete with post-reload flash, dynamic shared-form reuse, safe record names, mobile containment; no uncaught JS errors.');
} finally { await browser.close(); }
