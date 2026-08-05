# Production Deployment And Release Checklist

## Environment

- Set `APP_ENV=production`.
- Set `APP_DEBUG=false`.
- Set `APP_URL=https://your-domain.example`.
- Use `DB_CONNECTION=sqlite`.
- Set `DB_DATABASE` to an absolute private path outside the web root.
- Use `QUEUE_CONNECTION=database`.
- Use `SESSION_DRIVER=database`.
- Configure a real mail transport before enabling invitations and password reset email delivery.

## Required PHP Extensions

- `pdo_sqlite`
- `sqlite3`
- `openssl`
- `mbstring`
- `fileinfo`
- `json`
- `tokenizer`
- `ctype`
- `curl`
- `intl`

## SQLite Operations

Recommended startup pragmas:

```sql
PRAGMA foreign_keys = ON;
PRAGMA journal_mode = WAL;
PRAGMA busy_timeout = 5000;
PRAGMA synchronous = NORMAL;
```

Operational rules:

- Keep the database file outside `public`.
- Keep WAL/SHM files private with the database file.
- Back up the SQLite file with a SQLite-compatible backup flow.
- Do not copy a live database unsafely during high write activity.
- Run `PRAGMA integrity_check` and `PRAGMA foreign_key_check` after backup creation.

## Deployment

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

Queue worker:

```bash
php artisan queue:work --tries=3 --timeout=60
```

Scheduler cron:

```cron
* * * * * cd /path/to/homeBudget && php artisan schedule:run >> /dev/null 2>&1
```

## Backup And Restore

Implemented:

- Manual household-scoped backup endpoint.
- Backup log table.
- Backup size/status recording.
- SQLite health-check metadata.

Before restoring:

- Require owner/admin authorization.
- Require password confirmation in the deployment UI or operations process.
- Stop queue workers.
- Put the app in maintenance mode.
- Take a pre-restore backup.
- Restore the SQLite file and WAL/SHM state consistently.
- Run integrity and foreign-key checks.
- Restart workers and exit maintenance mode.

## Security Checklist

- HTTPS enforced by hosting/proxy.
- `APP_DEBUG=false`.
- `.env` not committed.
- SQLite and backups outside `public`.
- Storage permissions limited to the application user.
- Sanctum tokens revocable by user.
- Household isolation enforced by middleware and policies.
- Sensitive exports/backups require owner/admin authorization.
- Financial writes use backend validation and integer minor units.

## Performance Checklist

- Transaction history uses pagination.
- Exports stream CSV responses.
- Dashboard payloads use counts and limited latest records.
- Offline attachments are compressed and chunked before sync.
- SQLite writes are short and avoid external calls inside database transactions.
- Add query profiling before high-volume release testing.

## Release Verification

Run before release:

```bash
php artisan test
npm run build
php artisan migrate --pretend
```

Manual smoke test:

- Register and log in.
- Create a household.
- Switch language to Arabic and confirm RTL shell.
- Create account, expense, income, transfer, receipt, budget, recurring bill, savings goal, and debt.
- Queue an offline transaction and sync it.
- Create a backup and verify health-check metadata.
- Export transactions as CSV.
