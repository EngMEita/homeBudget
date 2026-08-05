# ERD Draft

```text
users
user_preferences
devices
device_sessions

households
household_users
roles
permissions
role_permissions
household_user_permissions
invitations

currencies
exchange_rates

account_types
accounts
account_user_permissions
account_reconciliations

categories

transactions
transaction_entries
transaction_splits
transaction_versions

audit_logs
```

Core relationships:

- `users` belong to many `households` through `household_users`.
- `households` own `accounts`, `categories`, and `transactions`.
- `accounts` and `transactions` belong to `currencies`.
- `roles` and `permissions` are scoped to households.
