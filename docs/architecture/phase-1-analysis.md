# Phase 1: Analysis and Architecture

## Project Understanding

HomeBudget is a multilingual household finance PWA for shared budgeting, transaction tracking, receipt allocation, budgets, recurring activity, and offline-safe synchronization.

The product must be a real Laravel + Vue application with SQLite as the production database, integer minor-unit money storage, RTL/LTR language support, and backend-enforced household isolation.

## Mandatory Stack

- Backend: PHP, Laravel, Sanctum, Form Requests, Policies, Gates, API Resources, Events, Listeners, Notifications, Scheduler, Queues, Storage, Cache, Migrations, Factories, Seeders, Pest or PHPUnit.
- Frontend: Vue 3, TypeScript, Vite, Pinia, Vue Router, Axios, IndexedDB, PWA support, RTL/LTR.
- Database: SQLite only.

## MVP Scope

- Authentication.
- Household workspaces and memberships.
- Roles and permissions.
- Currencies and multi-currency account setup.
- Accounts.
- Core transactions.
- English and Arabic localization.
- PWA shell with offline-ready foundation.

## Deferred Features

- OCR.
- AI categorization.
- Bank integrations.
- Live exchange-rate API dependency.
- Passkeys.
- Phone authentication.

## Functional Requirements

- Users can register and log in.
- Users can create or join households.
- Household data stays isolated.
- Users can manage accounts and transactions.
- Monetary values are stored in integer minor units.
- UI supports English and Arabic.

## Non-Functional Requirements

- Fast mobile-first UI.
- Secure backend authorization.
- SQLite-safe write patterns.
- Testable, maintainable architecture.
- PWA installability and offline queue readiness.

## Architecture Diagram

```text
Vue 3 PWA
  |
  | HTTPS JSON API
  |
Laravel Backend
  |
  | Eloquent ORM
  |
SQLite Database
```

## ERD

```text
users
  └─ household_users ─ households
        ├─ roles
        ├─ permissions
        └─ accounts
             └─ transactions

currencies ─┬─ accounts
            └─ transactions

households ─ categories
households ─ account_types
households ─ transactions
```

## Permissions Matrix

- Owner: full household control, membership, roles, accounts, categories, exports, backups.
- Administrator: broad management without ownership transfer or irreversible security actions.
- Contributor: add and edit permitted financial data.
- Viewer: read-only access according to permissions.
- Restricted user: custom limited permissions.

## User Stories

- As a user, I can create a household and invite a spouse.
- As a user, I can record expenses, income, and transfers.
- As a user, I can switch between English and Arabic.
- As a user, I can work offline and sync later.

## Use Cases

- Create household.
- Add currency and account.
- Record transaction.
- Review balances and reports.
- Resolve sync conflict.

## Screen List

- Login.
- Registration.
- Household switcher.
- Dashboard.
- Accounts.
- Transactions.
- Settings.
- Audit log.

## Main Flows

- Register -> create household -> add accounts -> record transactions.
- Switch locale -> UI direction updates -> user preference persists.
- Offline create -> sync queue -> backend confirmation.

## Financial Rules

- Store money in integer minor units.
- Transfers are not income or expense.
- Balances are calculated from movements.
- Receipt allocations must not double-count spending.

## Receipt Rules

- Receipt total must be positive.
- Allocations cannot exceed the receipt total.
- Categorization updates analytical totals only.

## Multi-Currency Strategy

- Each household has one base currency.
- Each account has one currency.
- Each transaction stores original and base converted amounts.

## Localization Strategy

- English default locale.
- Arabic locale with RTL layout and translated backend messages.
- No user-facing text hardcoded in Vue.

## RTL Strategy

- Use `dir` on the document root.
- Mirror navigation and directional icons when locale is Arabic.

## Offline Strategy

- IndexedDB stores temporary drafts and queued mutations.
- The PWA keeps a sync queue for replay after reconnect.

## Synchronization Strategy

- Use client UUIDs and idempotency keys.
- Server resolves conflicts with version checks.

## Idempotency Strategy

- Create operations accept idempotency keys.
- Duplicate replays return the original record.

## Conflict Resolution Strategy

- Versioned updates reject stale writes.
- Conflicts surface for user review before overwrite.

## SQLite Concurrency Strategy

- Keep write transactions short.
- No network calls during transactions.
- Use retries for busy/locked responses.

## Security Plan

- Sanctum authentication.
- Policy-based authorization.
- CSRF protection.
- Server-side household isolation.

## Backup and Restore Plan

- SQLite-safe backups only.
- Restore requires elevated confirmation and audit logging.

## Testing Plan

- Unit tests for money and balance logic.
- Feature tests for auth and household access.
- SQLite-specific tests for constraints and idempotency.

## Implementation Phases

- Phase 1: analysis and architecture.
- Phase 2: foundation.
- Phase 3: core financial structure.
- Phase 4: receipts.
- Phase 5: budgeting and forecasting.
- Phase 6: recurring activity.
- Phase 7: PWA and offline sync.
- Phase 8: advanced financial features.
- Phase 9: production hardening.

## Risks

- SQLite concurrency limits.
- RTL usability regressions.
- Double-counting receipt allocations.
- Weak offline conflict handling.

## Mitigations

- Transactional writes, versioning, idempotency.
- Feature tests for critical paths.
- Locale-aware formatting and layout checks.
