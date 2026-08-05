# Strategy Notes

## Transaction Rules

- Transfers are not income or expense.
- Opening balances are not income.
- Reconciliation differences are adjustments, not cash flow.
- Refunds reference the original expense where possible.

## Offline and Sync

- Queue local changes in IndexedDB.
- Send operations with a client UUID and idempotency key.
- Reject stale updates with version checks.

## Localization

- English is default.
- Arabic enables RTL at the document level.
- All UI text uses translation keys.

## SQLite

- Production uses a single private SQLite file.
- Write transactions stay short.
- Retry busy/locked writes with bounded attempts.
