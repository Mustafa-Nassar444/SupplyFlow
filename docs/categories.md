# Creating categories with translations

Implemented 2026-10-28. This covers create/store only, not edit/delete or completion of T04.

## Flow

The form sends flat fields such as name_ar and name_en. CategoryRequest originally returned false from authorize() and expected name.ar; both are corrected to match the active-language form.

CategoryRequest now:
- Authorizes through CategoryPolicy::create (existing admin role only).
- Requires a string name of at most 255 characters for each active, ready locale.
- Rejects inactive/unknown name_{locale} fields, even if null, and rejects the old nested name payload.
- Defines rule messages in messages(), using lang/ar/categories.php and lang/en/categories.php.
- Defines attributes() so messages identify both the field and the language.

Store locks the language-settings row and refreshes the catalog inside a database transaction, then revalidates with the same CategoryRequest rules/messages. This prevents a language change between initial validation and saving from silently creating an inactive translation or omitting a newly required one.

Only validated name fields are passed to TranslationTrait::storeTranslates. A new Category is saved through Astrotomic; its saved event persists translation rows in the same transaction. Any failure rolls back the category and every translation. Arbitrary request fields are not mass-assigned.

Category now uses Astrotomic\Translatable\Translatable and its contract, replacing the obsolete Dimsav import. CategoryTranslation allows only name for mass assignment. A unique constraint on (category_id, locale) prevents duplicate translations for a category.

Example storage:
- categories: one parent id and timestamps.
- category_translations: one row with that category_id, locale=ar and Arabic name; another with locale=en and English name.

Reading $category->translate('ar')->name gets the Arabic value explicitly. Reading $category->name uses the current application locale and the existing Astrotomic fallback settings. No fallback setting changed.

The category index eager-loads translations and its dashboard breadcrumb is corrected. Category index/create are permitted return pages for the existing language switcher. Switch the interface language to see the corresponding stored name in the list.

## Responses and setup

AJAX gets HTTP 201 with message, redirect_url (create page), and category_id. The existing form uses data-success-action=none, so it shows success and retains values without reloading. Normal HTML submission redirects back to create with a Session success message.

The three category migrations were applied to the local MySQL database: categories, category_translations, and the new locale uniqueness constraint. No demo category was inserted into the working database. Other modules were not migrated.

For a different environment:
~~~powershell
php artisan migrate --path=database/migrations/2026_10_27_121524_create_categories_table.php --path=database/migrations/2026_10_27_122140_create_category_translations_table.php --path=database/migrations/2026_10_28_150000_add_category_translation_locale_unique.php
~~~

The unique migration does not delete/merge existing duplicate data; if duplicates exist in another environment, investigate them before applying the constraint.

## Verification

CategoryStoreTest covers JSON success, real Astrotomic persistence/readback, translated required/string/max messages, inactive/unknown/nested fields, active-language changes, admin authorization, HTML fallback, rollback on a translation failure, database uniqueness, and reading translated names through the list and language switcher.

Chrome was tested against an isolated SQLite test database: native validation opens the hidden required-language tab, AJAX 422 renders localized feedback in the correct tab, values are preserved, and valid input returns 201 and a success message without reload. No uncaught JavaScript errors occurred. Test categories exist only in the isolated fixture.

Pint and Blade compilation passed. See docs/tasks.md for the final scoped test counts. The known unrelated Dashboard/example/registration test failures from the earlier full-suite run were not resolved as part of this task.

CAT-STORE01 final verification: 28 scoped tests, 146 assertions passed (categories, languages, authentication and profile); Pint passed. Browser verification used an isolated fixture and passed native tab validation, localized AJAX errors and successful translated saving.

## Edit/delete update — 2026-10-28

Admin edit/update/delete and the reusable layout confirmation form are now implemented. Create/edit share the language-tab fields; inactive translations are preserved. The earlier create-only limitation is superseded for these actions. See [shared dashboard actions](dashboard-actions.md) for behavior, current store-flow notes, and final verification.
