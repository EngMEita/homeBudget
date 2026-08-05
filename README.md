# HomeBudget

Production-oriented multilingual household budgeting PWA built with Laravel, Sanctum, Vue 3, TypeScript, Pinia, Vite, SQLite, IndexedDB, and a service-worker sync foundation.

## Implemented Scope

- Email/password registration and token login using Sanctum.
- Household workspaces with roles, invitations, and scoped API middleware.
- Accounts, account types, currencies, categories, transactions, transfers, refunds, and ledger entries.
- Integer minor-unit money storage and backend-only balance/report calculations.
- Multi-currency transaction metadata with persisted exchange-rate fields.
- Receipts with attachments, partial/full categorization, allocation overrun validation, and offline attachment queue support.
- Budgets, budget periods, budget lines, and basic forecast/report payloads.
- Recurring rules, upcoming bills, savings goals, debts, installments, audit logs, backups, and dashboard counts.
- IndexedDB offline queue, Workbox-powered PWA service worker, background sync hook, optimistic conflict detection, retry/backoff, image compression, and chunked attachment payloads.
- CSV transaction export and SQLite backup/health-check API.
- Standalone dashboard, accounts, categories, receipts, reports, offline sync, transaction history, settings, and security screens.
- English/Arabic locale foundation and RTL-aware application shell.

## Requirements

- PHP 8.3 or newer.
- Composer.
- Node.js 20 or newer.
- SQLite with PDO support.
- PHP extensions: `pdo_sqlite`, `sqlite3`, `openssl`, `mbstring`, `fileinfo`, `json`, `tokenizer`, `ctype`, `curl`, `intl`.

## Local Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate --seed
npm install
npm run build
php artisan test
php artisan serve
```

Default demo login created by the seeder:

```text
owner@example.com
password
```

## SQLite Configuration

Development uses:

```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

Production should use an absolute private path outside `public`:

```env
DB_CONNECTION=sqlite
DB_DATABASE=/srv/homebudget/private/database.sqlite
```

The SQLite file must never be committed, directly served, or stored under `public`.

Recommended runtime pragmas are documented in [docs/production.md](docs/production.md). The application keeps financial write transactions short and uses optimistic locking/idempotency in high-risk write flows.

## Queues And Scheduler

Use the database queue for local and small production deployments:

```bash
php artisan queue:work --tries=3
```

Cron scheduler:

```cron
* * * * * cd /path/to/homeBudget && php artisan schedule:run >> /dev/null 2>&1
```

## Testing

```bash
php artisan test
npm run check:i18n
npm run build
npm run test:e2e
```

Current verified result:

```text
49 tests, 181 assertions
npm run check:i18n passed with 149 synchronized locale keys
npm run build passed and generated the PWA manifest/service worker
npm run test:e2e passed on Chromium and mobile Chromium
```

## Production Notes

- Serve through HTTPS only.
- Keep `APP_DEBUG=false`.
- Store SQLite and backups in private writable storage.
- Run `php artisan migrate --force` during deployment.
- Run `php artisan config:cache`, `route:cache`, and `view:cache` after configuring production env.
- Use storage permissions that allow only the application user to write private files.
- Review [docs/production.md](docs/production.md) before release.
