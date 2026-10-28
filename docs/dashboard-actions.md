# Shared delete form and one dashboard

Implemented 2026-10-28.

## One dashboard

The authenticated, verified route named dashboard remains /dashboard. It now renders dashboard/index.blade.php using layouts/dashboard.blade.php and local Tabler assets. The root / redirects to that route; guests continue to the existing login page.

The previous dashboard.blade.php is only a compatibility wrapper extending dashboard.index. Its Breeze dashboard content is gone. Profile/authentication pages and their existing controllers remain available; they are not additional dashboard routes.

The Tabler sidebar links to Categories and Languages according to their Policies. The navbar has the authenticated user's real profile and logout actions. Demo statistics on the existing dashboard remain clearly labelled as demonstration data; this task did not implement reporting.

## Shared deletion

layouts/dashboard.blade.php includes partials/dashboard/delete-modal.blade.php once, outside all page forms. This contains one form:
- id: dashboard-delete-form
- POST with @csrf and @method('DELETE')
- class js-ajax-form, using the existing public/js/ajax-forms.js handler
- default success behavior: reload, preserving the existing AJAX flash message after navigation

public/js/dashboard-delete.js is loaded once from the shared scripts partial. It delegates clicks, so newly inserted rows or Modal content can use it without page-specific delete scripts.

A module that allows deletion can add this button:

~~~blade
@can('delete', $record)
    <button type="button"
            data-delete-url="{{ route('module.destroy', $record) }}"
            data-delete-name="{{ $record->name }}">
        {{ __('dashboard_ui.delete') }}
    </button>
@endcan
~~~

module.destroy is an example placeholder; use the module's real named route. The script validates the URL is same-origin, fills the shared form, writes the record label with textContent, and opens the Tabler confirmation Modal. The record label is not interpreted as HTML.

No request is sent until confirmation. Cancelling does not delete anything. During a request, the confirm/source/cancel buttons are disabled and the Modal cannot dismiss. On failure, controls are restored and the error remains visible. Reopening the Modal resets only messages/errors owned by the AJAX system.

Use data-delete-success-action="redirect" on the trigger to follow the response redirect_url instead of reloading. Category buttons use this option to return to the index after deleting, including deletion of the last row of a later pagination page. For default reload, current query parameters are retained.

A delete endpoint must enforce authentication, Policy authorization and its own business rules. This form is not a generic database-delete endpoint. Successful AJAX responses follow the existing contract:

~~~json
{"message":"Record deleted successfully.","redirect_url":"/module"}
~~~

Return 422 with errors for a deletion that business rules reject, or the appropriate authorization/error status. Use ordinary redirect + Session flash when handling non-JSON submissions. Do not use the form for posted financial/stock movements that project rules prohibit deleting.

Translations are in lang/ar/dashboard_ui.php and lang/en/dashboard_ui.php. The group name deliberately avoids dashboard.php, which collides with legacy __('Dashboard') lookups on case-insensitive Windows filesystems.

## Category actions

The index now displays real Edit and Delete actions for Admin, an actual empty state, and Bootstrap pagination. Sales/Warehouse can read the list but cannot see or call mutation actions. The placeholder details Modal and disabled demo filters were removed from this list.

Edit and create share dashboard/categories/_form.blade.php, including active-language tabs, direction, old input and validation feedback. Edit preloads existing translations and submits POST + PUT spoofing through AJAX. The update transaction locks the parent category, saves the validated active-language names and preserves inactive translations.

CategoryRequest authorizes create and update through the corresponding Policy methods. CategoryPolicy separately authorizes update/delete on the server. No edit/delete permission is granted just because a user is logged in.

Destroy locks the category in a transaction. The existing foreign key cascades its translation rows. A restricting foreign-key error returns a localized validation message rather than deleting referenced data or exposing SQL. Future modules referencing categories must use appropriate restrictive constraints; no Products module or relation was invented here.

The category index/create/edit paths work with the language switcher. Editing uses the same request validation messages as creation. Normal HTML update/delete submissions have redirect/Session fallbacks.

The store implementation found at the start of this task already used request->validated() directly inside its transaction; its business flow was preserved. Earlier documentation describing a language-settings lock for store is historical, not the current store implementation. This task did not reintroduce that lock or redesign language-change concurrency.

## Verification

- 39 focused PHP tests, 212 assertions passed: category actions/store, languages, authentication/profile, and home redirection.
- Tests include prefilled edit fields, preserving inactive translations, rejected updates, unauthorized users, deletion and cascades, rejecting referenced records, repeated deletion 404, normal HTML fallbacks and one shared form on the dashboard.
- Chrome on an isolated SQLite fixture passed real edit/update and deletion, cancellation, a mocked 422 failure, duplicate submission prevention/loading lock, success flash after navigation, reuse by a dynamic trigger, safe labels, and 390px containment. No uncaught JavaScript errors.
- Pint, Blade compilation, JS syntax and 48 local CSS asset references passed.
- Browser script: scripts/test-dashboard-actions.mjs. Use only an isolated fixture with the synthetic test account; never run against working records. It requires existing Playwright and Chrome, PLAYWRIGHT_MODULE_DIR, and optional DASHBOARD_ACTIONS_TEST_URL (default http://127.0.0.1:8016).
- No database migration, new dependency, working-record deletion, commit or push was needed in this task. Known unrelated old example/registration test mismatches are outside this change; the full suite was not claimed to pass.

## Profile layout update — 2026-10-28

/profile now also extends layouts.dashboard. Its existing information/password/account-deletion forms use Tabler classes and translated labels from profile_ui.php in both languages. No authentication/profile/password controller behavior changed; these forms intentionally remain ordinary submissions because their endpoints return HTML redirects, not the AJAX JSON contract. Default, updatePassword and userDeletion error bags remain isolated, with the existing CSRF and PATCH/PUT/DELETE spoofing.

Account deletion retains a separate password-confirmation Modal instead of using the generic record-delete form, which has no password field. public/js/profile.js only handles Modal focus and reopening after a server-side userDeletion error; it does not submit forms. No Alpine/Tailwind code or extra library is needed on this page. The language switcher's safe return list includes profile.

Verification: 21 focused tests/105 assertions passed, plus Chrome against an isolated fixture for profile saving, status messages, invalid-password Modal reopening/cancellation, English/Arabic, and mobile containment. Pint, Blade, JS syntax and asset checks passed. No working profile, password or account was changed in browser verification.
