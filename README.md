# SupplyFlow

A supply-company management dashboard built with **Laravel, Blade, and Tabler**, with Arabic/English interfaces and RTL/LTR layouts.

SupplyFlow targets a single company and a single warehouse. The current implementation provides the dashboard foundation, language settings, translated categories, and account management. Purchasing, inventory, sales, collections, and reporting are planned next.

**Status: actively in development.** Dashboard statistics are demonstration data. Detailed implementation progress is tracked in [docs/tasks.md](docs/tasks.md).

## Current features

- **Unified Tabler dashboard** with shared layouts, components, navigation, and local assets.
- **Arabic and English** interfaces, language switching, RTL/LTR support, and local fonts.
- **Language management** for Admin: activate supported languages and select one active default language. French is seeded as inactive until its interface translations and assets are prepared.
- **Translated categories**: create, list, edit, and delete, with language tabs and Astrotomic-backed translations. Mutations are restricted to Admin; Sales and Warehouse can read the list.
- **Shared AJAX forms** with validation feedback, file-upload support, Laravel method spoofing, loading states, and configurable success actions.
- **Reusable delete confirmation** shared across dashboard modules.
- **Authentication and profile management**: login/logout, password-reset routes, profile updates, password changes, and password-confirmed account deletion. Public registration is disabled.

Language settings control which translation fields are available. They do not automatically translate interface text or existing records. Inactive data translations are preserved.

## Technology

| Layer | Technology |
| --- | --- |
| Backend | Laravel 12.70.0 · PHP 8.2+ |
| Views | Blade · Tabler 1.6.1 |
| Data translations | Astrotomic Laravel Translatable 11.17.1 |
| Authentication foundation | Laravel Breeze |
| Database | MySQL; MariaDB is also in the project scope |
| Asset tooling | Vite, npm, and a separate Tabler source-build script |
| Tests and formatting | PHPUnit · Laravel Pint |

Exact dependency versions are recorded in the Composer and npm lockfiles. MySQL 8.4.3 and Node.js 22.14.0 were used during local verification; MariaDB has not been verified here.

## Local setup

### Prerequisites

- PHP 8.2 or later, with the extensions required by Laravel and `pdo_mysql`.
- Composer 2.
- MySQL and an empty local database.
- Node.js compatible with the installed Vite packages: Node 20.19+ or 22.12+; the verified local version is 22.14.0.
- PDO SQLite for the default in-memory test configuration.

### 1. Install dependencies

Clone this repository, open its directory, and run:

```sh
composer install
npm ci
```

For a **fresh installation**, create your environment file and application key:

```sh
cp .env.example .env
php artisan key:generate
```

On PowerShell, `Copy-Item .env.example .env` is the equivalent copy command. For an existing installation, keep its `.env` and application key.

### 2. Configure the environment

Create the database in your database client, then edit `.env` with your own local credentials:

```dotenv
APP_NAME=SupplyFlow
APP_URL=http://127.0.0.1:8000
APP_LOCALE=en
APP_FALLBACK_LOCALE=en

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=supplyflow
DB_USERNAME=your_local_user
DB_PASSWORD=your_local_password
```

The existing configuration uses database-backed sessions, cache, and queues. Configure a mail transport if you want to use password-reset emails. Keep `.env` private.

### 3. Prepare the database

```sh
php artisan migrate
php artisan db:seed --class=LanguagesSeeder
```

The language seeder adds Arabic, English, and French in that order. Arabic and English start active; French starts inactive. The initial default follows a supported `APP_LOCALE`, and subsequent runs preserve customized language settings.

The root `DatabaseSeeder` also inserts fixed development users and is not safe to rerun. The setup above deliberately seeds languages only.

### 4. Create a local Admin account

Public registration is disabled. Open Tinker:

```sh
php artisan tinker
```

Create your local account with a masked password prompt:

```php
\App\Models\User::forceCreate([
    'name' => 'Local Administrator',
    'email' => 'admin@example.test',
    'role' => 'admin',
    'password' => \Illuminate\Support\Facades\Hash::make(
        \Laravel\Prompts\password('Choose an admin password')
    ),
]);
```

Replace the example name and email with your local details. This is a trusted CLI setup step; application requests must use their normal validation and authorization.

### 5. Build and run

```sh
npm run build
php artisan serve
```

Open the URL printed by Artisan. `/` redirects to `/dashboard`, and guests are sent to `/login`.

| Page | Route |
| --- | --- |
| Dashboard | `/dashboard` |
| Categories | `/dashboard/categories` |
| Language settings — Admin only | `/dashboard/languages` |
| Profile | `/profile` |

For asset development, run `npm run dev` in a separate terminal. The Tabler dashboard uses local published assets; the Vite build also prepares the existing authentication-page assets. Queue workers and Scheduler jobs are not required for the currently implemented dashboard modules.

## Tabler assets

Published CSS, JavaScript, fonts, and notices are under [public/vendor/tabler](public/vendor/tabler). The dashboard loads these locally without a CDN or a second Bootstrap bundle.

To rebuild them from the included Tabler source:

```sh
npm ci --prefix .tools/tabler-build
npm run build:tabler
```

The original source and archive are retained under `tablerViews/`. Build-tool dependencies have their own lockfile in `.tools/tabler-build/`.

## Development conventions

- Use the shared Blade layout and translation files for new dashboard pages.
- Keep request validation in Form Requests and server authorization in Policies.
- Use the active-language catalog and `TranslationTrait` when building translated fields.
- Add `js-ajax-form` to forms whose endpoints return the documented JSON contract. Profile forms currently use normal submissions and redirects.
- Reuse the layout's delete confirmation through `data-delete-url` and `data-delete-name`; each endpoint still enforces its own permissions and business rules.
- Preserve database constraints and transactions. Financial operations, once implemented, must use precise decimal arithmetic.

See [AGENTS.md](AGENTS.md) for repository working instructions.

## Testing

`phpunit.xml` configures SQLite in memory. Enable PDO SQLite before running the focused feature tests:

```sh
php artisan test --filter="CategoryStoreTest|CategoryActionsTest|LanguagesTest|ProfileTest|PasswordUpdateTest|AuthenticationTest"
```

Additional checks:

```sh
php artisan view:cache
node scripts/verify-dashboard-assets.mjs
php vendor/bin/pint --test
```

The repository also contains browser-verification scripts and a MySQL language-concurrency check. Their fixture and database requirements are documented in the module guides. Browser scripts must run against an isolated test instance.

Some legacy tests still expect removed demo routes or public registration. The full suite is not currently clean; focused verification and known limitations are recorded in [docs/tasks.md](docs/tasks.md). SQLite tests do not establish MySQL/MariaDB locking behavior.

## Roadmap

The planned operating cycle is: receive purchases → confirm and reserve sales orders → deliver → collect payments → review reports.

Remaining work includes:

- Products, customers, and suppliers.
- Currencies, exchange-rate snapshots, and decimal calculations.
- Purchasing, inventory movements, moving-average cost, and stock counts.
- Sales orders, reservations, delivery, and cancellation rules.
- Collections and payment reversals.
- Operational reports, CSV exports, audit logs, and notifications.
- Extended user administration and authorization acceptance checks.

The first version retains the single-company/single-warehouse scope. Customers and suppliers are business records rather than login accounts. API access, online payments, multiple warehouses, and partial receiving/delivery are outside the initial scope.

## Documentation

| Reference | Purpose |
| --- | --- |
| [Requirements](docs/requirements.md) | Business rules and acceptance criteria — Arabic |
| [Tasks](docs/tasks.md) | Implementation status and handover notes |
| [Decisions](docs/decisions.md) | Recorded project decisions |
| [Dashboard foundation](docs/dashboard-ui.md) | Blade structure and local Tabler assets |
| [Dashboard actions](docs/dashboard-actions.md) | Shared deletion, navigation, and profile layout |
| [AJAX forms](docs/ajax-forms.md) | Request/response contract, validation, and events |
| [Languages and translations](docs/languages.md) | Language readiness, catalog, Trait, and Astrotomic |
| [Categories](docs/categories.md) | Translated category validation and persistence |

[Figma design reference](https://www.figma.com/design/UIw4rnUut77XjVk7VShIm3) is the visual reference; the requirements document governs business rules.

## Third-party notices

SupplyFlow uses Laravel, Tabler, Astrotomic, and other dependencies under their respective licenses. Tabler and bundled asset notices are retained in [public/vendor/tabler](public/vendor/tabler), including font and icon notices. Composer and npm packages retain their upstream licensing information.
