# Master Prompt: Build a Production-Ready Multilingual Household Budget PWA

You are a Senior Full-Stack PHP Engineer, Laravel Architect, Database Designer, PWA Specialist, Security Engineer, and Financial Application Designer.

Your task is to analyze, design, implement, test, document, and prepare for production a lightweight but professional household budgeting application.

The application must be built as a real, production-ready product, not as a prototype, static mockup, UI demonstration, or incomplete proof of concept.

The application must allow individuals, couples, and family members to collaboratively manage income, expenses, accounts, payment methods, shopping receipts, budgets, financial goals, recurring commitments, and financial forecasts.

---

# 1. Mandatory Technology Stack

The following technology stack is mandatory and must not be replaced unless there is a critical technical reason that is clearly documented.

## Backend

Use:

* PHP.
* Laravel.
* The latest stable and supported Laravel version.
* The latest stable PHP version supported by Laravel.
* Laravel Sanctum for authentication.
* Laravel Form Requests for validation.
* Laravel Policies and Gates for authorization.
* Laravel API Resources for API responses.
* Laravel Events and Listeners where appropriate.
* Laravel Notifications.
* Laravel Scheduler.
* Laravel Queues where useful.
* Laravel Storage for attachments.
* Laravel Cache.
* Laravel Migrations.
* Laravel Factories.
* Laravel Seeders.
* Pest or PHPUnit for automated tests.

All financial rules, security rules, authorization rules, calculations, balance calculations, budget calculations, and business logic must be implemented and validated in the backend.

The frontend must never be treated as the trusted source for financial values, permissions, balances, exchange rates, totals, or transaction states.

## Frontend

Use:

* Vue 3.
* TypeScript.
* Vite.
* Pinia.
* Vue Router.
* Axios or a well-structured Fetch client.
* IndexedDB for offline data.
* Workbox or Vite PWA Plugin.
* Responsive mobile-first architecture.
* Component-based architecture.
* Full RTL and LTR support.
* PWA installation support.
* Offline queue.
* Background synchronization where supported.
* Push notifications where supported.

## Database

Use SQLite as the primary and only production database for the current version.

Do not use MySQL, PostgreSQL, MariaDB, MongoDB, or any other database in the current implementation.

The SQLite database must be centralized on the server.

The frontend and mobile-installed PWA must never access the SQLite file directly.

All devices must communicate with the Laravel backend through a secure HTTPS JSON API.

## Offline Storage

Use IndexedDB inside the PWA for temporary local offline storage.

The architecture must be:

```text
Vue 3 PWA
    |
    | HTTPS JSON API
    |
Laravel PHP Backend
    |
    | Eloquent ORM
    |
Central SQLite Database
```

Offline architecture:

```text
Vue 3 PWA
    |
IndexedDB and Local Sync Queue
    |
Internet Connection Restored
    |
Laravel Synchronization API
    |
Central SQLite Database
```

## Authentication

Use Laravel Sanctum.

## API

Use a versioned REST JSON API.

## Money Storage

Store monetary values using integer minor units.

Examples:

* 125.50 SAR must be stored as 12550 halalas.
* 99.75 EGP must be stored as 9975 piastres.
* 20 USD must be stored according to the currency decimal configuration.

Never use floating-point or SQLite REAL values for financial amounts.

## Public Identifiers

Use UUID or ULID for public identifiers and records created offline.

---

# 2. Application Purpose

Build a lightweight household financial management application that allows users to:

* Record expenses quickly.
* Record income.
* Manage bank accounts, cash wallets, cards, and electronic wallets.
* Record shopping receipts quickly.
* Save a receipt total first and categorize it later.
* Categorize shopping receipts by department.
* Optionally categorize individual products.
* Manage monthly and custom budgets.
* Forecast end-of-month expenses.
* Forecast end-of-month balances.
* Manage recurring income and expenses.
* Track upcoming bills.
* Manage savings goals.
* Manage debts, loans, and installments.
* Support multiple currencies.
* Share one household budget with a spouse or other authorized users.
* Identify who created, modified, deleted, or restored a transaction.
* Work temporarily without internet.
* Synchronize safely after internet connectivity returns.
* Install the application as a PWA on Android, iOS, Windows, and macOS.

The application must be:

* Lightweight.
* Fast.
* Mobile-first.
* Secure.
* Multilingual.
* Multi-user.
* Multi-currency.
* Offline-capable.
* Installable.
* Maintainable.
* Testable.
* Expandable.
* Suitable for everyday family use.

---

# 3. Language and Localization Requirements

The application must primarily support:

* English.
* Arabic.

## Default Language

English must be the default application language.

The default application locale must be:

```text
en
```

The default document direction must be:

```text
LTR
```

## Arabic Support

Arabic must be fully supported as a first-class language.

When Arabic is selected:

* The full interface must switch to RTL.
* Navigation direction must be adjusted.
* Sidebars must be mirrored where appropriate.
* Icons with directional meaning must be mirrored.
* Breadcrumbs must follow RTL direction.
* Forms must remain visually usable.
* Tables must remain readable.
* Charts and legends must remain understandable.
* Currency and number display must remain correct.
* Dates must remain clear.
* Modal layouts must support RTL.
* Toast messages must support RTL.
* Email notifications must support Arabic.
* Push notifications must support Arabic.
* Validation messages must support Arabic.
* Backend API validation messages must be translatable.
* Exported PDF and Excel reports must support Arabic content.

## Language Switching

Provide a language selector available from:

* The login screen.
* The registration screen.
* The user profile.
* The main application settings.

The selected language must be saved in:

* User preferences when the user is authenticated.
* Local storage or IndexedDB before authentication.
* A secure cookie when appropriate.

The language must remain selected after:

* Refreshing the page.
* Closing and reopening the PWA.
* Logging in.
* Logging out.
* Switching devices after user preference synchronization.

## Translation Architecture

Do not hardcode visible text inside Vue components.

All visible frontend text must use localization keys.

Use structured translation files such as:

```text
resources/js/locales/en/
resources/js/locales/ar/
```

The translation structure may include:

```text
common.json
auth.json
navigation.json
accounts.json
transactions.json
receipts.json
budgets.json
reports.json
settings.json
validation.json
notifications.json
```

Backend translations should use Laravel language files.

All application-defined entities that require translated system labels must support English and Arabic.

Examples include:

* Default categories.
* Account types.
* Transaction types.
* Transaction statuses.
* Receipt statuses.
* Notification labels.
* Budget period names.
* Recurrence labels.
* Permission labels.

User-created names do not need automatic translation.

For example, a user-created account named “Main Riyadh Account” must remain exactly as entered.

## Localization Rules

Use locale-aware formatting for:

* Numbers.
* Currency values.
* Percentages.
* Dates.
* Times.
* Relative time.
* Decimal separators.
* Thousands separators.

Do not assume that the user interface language is the same as the currency.

For example:

* The interface may be Arabic.
* The account currency may be SAR.
* A report may include EGP and USD.

## Translation Quality

Arabic translation must be professional, accurate, natural, and appropriate for financial applications.

Do not use machine-like literal Arabic translations.

Avoid inconsistent terminology.

Create a terminology dictionary for important concepts, including:

* Expense.
* Income.
* Transfer.
* Account.
* Wallet.
* Budget.
* Receipt.
* Allocation.
* Balance.
* Available Balance.
* Credit Limit.
* Forecast.
* Recurring Transaction.
* Savings Goal.
* Debt.
* Installment.
* Reconciliation.
* Refund.
* Settlement.
* Uncategorized Purchase.
* Partially Categorized.
* Fully Categorized.

## Missing Translation Protection

Provide development checks that identify missing English or Arabic translation keys.

English must be the fallback language if a translation key is missing.

Do not display raw translation keys to production users.

---

# 4. Household and Workspace Model

The system must use the concept of:

```text
Household / Financial Workspace
```

A user may create or join multiple financial workspaces.

Examples:

* Family Budget.
* Personal Budget.
* Saudi Arabia Home.
* Egypt Home.
* Travel Budget.
* Children Budget.
* Small Project Budget.
* Home Renovation.
* Umrah Trip.
* School Expenses.

Each household or workspace must have:

* A unique identifier.
* A name.
* A default base currency.
* A default language.
* An owner.
* Members.
* Roles.
* Permissions.
* Accounts.
* Transactions.
* Categories.
* Budgets.
* Reports.
* Goals.
* Settings.
* Audit logs.

All data must be fully isolated between households.

A user must never be able to access another household’s information without explicit membership and permission.

Household isolation must be enforced in the backend, not only in the frontend.

---

# 5. Users, Roles, and Permissions

Implement a flexible role and permission system.

## Owner

The owner can:

* Manage the household.
* Edit household settings.
* Invite users.
* Remove users.
* Assign roles.
* Manage permissions.
* Manage accounts.
* Manage categories.
* Manage currencies.
* Manage budgets.
* View all transactions.
* Edit all transactions.
* Restore deleted transactions.
* Export data.
* Create backups.
* Restore backups.
* View audit logs.
* Transfer ownership.
* Delete or deactivate the household.

## Administrator

An administrator can manage most household information but must not automatically be able to:

* Delete the household.
* Transfer ownership.
* Restore a full database backup.
* Modify critical security settings.
* Perform irreversible administrative operations.

## Contributor

A contributor may be allowed to:

* Add expenses.
* Add income.
* Add shopping receipts.
* Add receipt categorizations.
* Upload receipt images.
* Add transfers.
* Edit transactions created by that user within a configurable period.
* View authorized accounts.
* View authorized reports.

## Viewer

A viewer may:

* View approved information.
* View reports.
* View transactions according to assigned permissions.

A viewer cannot create or modify financial data.

## Restricted User

A restricted user can have custom permissions such as:

* Use one specific cash wallet only.
* Add expenses without viewing balances.
* View only personal transactions.
* Use selected categories.
* View selected accounts.
* Record expenses but not income.
* Record transactions without seeing household reports.
* View only transactions created by that user.
* Hide sensitive or private accounts.

## Permission Requirements

Build a clear permission matrix.

Authorization must be enforced using Laravel Policies and backend middleware.

Never depend on hiding buttons in the frontend as the only authorization method.

---

# 6. Authentication and Security

Implement secure authentication that includes:

* Registration by email.
* Login by email and password.
* Email verification.
* Password reset.
* Password confirmation for sensitive operations.
* Optional two-factor authentication.
* Future-ready phone authentication support.
* Future-ready passkey support.
* Active device and session management.
* Logout from one device.
* Logout from all devices.
* Notifications for new device login.
* Login rate limiting.
* API rate limiting.
* Secure cookies.
* CSRF protection.
* XSS protection.
* SQL injection protection.
* Secure password hashing.
* Session expiration.
* Account lock protection.
* Secure password reset tokens.

Sensitive actions should require confirmation, including:

* Deleting a household.
* Restoring a backup.
* Transferring ownership.
* Deleting an account.
* Changing security settings.
* Removing an administrator.
* Exporting all household data.

Record important security activity in the audit log.

---

# 7. Accounts, Wallets, and Payment Sources

Create an independent Account entity.

Supported account types must include:

* Cash.
* Bank Account.
* Debit Card.
* Credit Card.
* Prepaid Card.
* Electronic Wallet.
* Salary Account.
* Savings Account.
* Investment Account.
* Joint Wallet.
* Petty Cash.
* Custom Account Type.

Users must be able to create custom account types.

## Account Fields

Each account should support:

* UUID or ULID.
* Household.
* Account name.
* Account type.
* Currency.
* Opening balance.
* Calculated current balance.
* Institution or bank name.
* Optional last four card digits.
* Optional credit limit.
* Optional statement closing day.
* Optional payment due day.
* Optional minimum payment.
* Icon.
* Display color.
* Notes.
* Active status.
* Archived status.
* Shared or private status.
* Allowed users.
* Users allowed to view the balance.
* Users allowed to create transactions.
* Users allowed to view transactions.

## Balance Calculation

The balance must be calculated from financial movements.

For a regular asset account:

```text
Opening Balance
+ Confirmed Income
+ Incoming Transfers
- Confirmed Expenses
- Outgoing Transfers
± Reconciliation Adjustments
```

For credit card accounts, use proper credit balance behavior.

Do not treat the current balance as a manually editable value.

A manual difference must be recorded through a reconciliation or adjustment transaction.

---

# 8. SQLite Requirements

SQLite must be the central production database.

## Environment Configuration

Development configuration should support:

```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

Production should use an absolute database path outside the public web directory:

```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/private/path/database.sqlite
```

## Database File Security

The SQLite database file must:

* Never be stored inside the public directory.
* Never be directly downloadable.
* Never be committed to Git.
* Be included in the backup strategy.
* Have appropriate file permissions.
* Be writable only by the application service user.
* Be protected from direct HTTP access.

Add SQLite database files and related files to `.gitignore`.

## SQLite Configuration

Enable appropriate SQLite settings, including:

```sql
PRAGMA foreign_keys = ON;
PRAGMA journal_mode = WAL;
PRAGMA busy_timeout = 5000;
```

Select an appropriate synchronous mode after evaluating performance and durability.

Document the selected SQLite configuration.

## Concurrency

Because SQLite supports one writer at a time, design all write operations carefully.

Requirements:

* Keep write transactions short.
* Never perform network calls inside database transactions.
* Never upload files inside database transactions.
* Never generate reports inside financial write transactions.
* Use optimized indexes.
* Avoid unnecessary writes.
* Use limited retries for database busy or locked errors.
* Use optimistic locking.
* Use idempotency keys.
* Use queues for suitable non-immediate tasks.
* Avoid long-running background writes.
* Keep financial transaction boundaries minimal and clear.

## SQLite Limits

Design the application for:

* Individuals.
* Couples.
* Families.
* Small shared financial workspaces.
* A limited number of simultaneous users.
* Thousands or tens of thousands of transactions.
* A limited number of simultaneous writes.

The application is not intended to be an enterprise accounting system with hundreds of simultaneous writers.

Keep the data access layer maintainable so a future database migration is possible, but do not use another database in the current implementation.

---

# 9. Monetary Data Rules

Never use floating-point numbers for money.

Use integer minor units.

The currencies table must include:

* ISO code.
* English name.
* Arabic name.
* Symbol.
* Decimal places.
* Minor unit factor.
* Active status.

Examples:

```text
SAR:
decimal_places = 2
minor_unit_factor = 100

EGP:
decimal_places = 2
minor_unit_factor = 100

USD:
decimal_places = 2
minor_unit_factor = 100
```

Use safe integer arithmetic for:

* Addition.
* Subtraction.
* Comparisons.
* Budget calculations.
* Splits.
* Receipt allocations.
* Currency conversion.
* Refunds.
* Installments.
* Reconciliation.
* Forecast inputs.

Define documented rounding rules for currency conversion.

---

# 10. Multi-Currency Support

The application must support multiple currencies, including:

* SAR.
* EGP.
* USD.
* EUR.
* Any user-enabled currency.

Each household must have a base reporting currency.

Each account must have one currency.

Each transaction must store:

* Original currency.
* Original amount.
* Exchange rate used.
* Converted base-currency amount.
* Exchange rate source.
* Exchange rate date.
* Whether the exchange rate was entered manually.

Historical exchange rates must not change after the transaction is confirmed.

The user must be able to:

* Enter an exchange rate manually.
* Record a transfer between accounts using different currencies.
* Record the sent amount.
* Record the received amount.
* Record conversion fees.
* View original and converted values.
* Run reports in the household base currency.
* Optionally view reports in another supported currency.

Prepare the architecture for a future exchange-rate API, but do not require an external API for the first release.

---

# 11. Unified Financial Transaction Engine

Build a unified financial transaction engine supporting:

* Expense.
* Income.
* Transfer.
* Opening Balance.
* Adjustment.
* Refund.
* Credit Card Payment.
* Loan Received.
* Debt Payment.
* Savings Goal Contribution.
* Settlement.
* Reconciliation Adjustment.

Consider using an internal simplified ledger or double-entry ledger.

If double-entry is used:

* It must remain hidden from normal users.
* It must guarantee balance integrity.
* It must not make the user interface complicated.
* It must support reporting by accounts and categories.

## Mandatory Financial Rules

* Transfers are not income.
* Transfers are not expenses.
* Opening balances are not income.
* Reconciliation adjustments are not regular income or expenses.
* Credit card payments are not new expenses when purchases were already recorded.
* Refunds should be linked to the original expense where possible.
* A received loan is a liability, not normal income.
* Loan principal repayment is not a household consumption expense.
* Loan interest and fees are expenses.
* Receipt allocation rows must not create additional account deductions.
* Receipt items must not create additional account deductions.
* A receipt total and its categorization must never be double-counted.
* All financial creation and update operations must use database transactions.
* All balance-affecting operations must be idempotent.
* Financial records must not be hard-deleted by default.
* Historical financial changes must be auditable.

---

# 12. Expense Recording

Create an extremely fast expense entry flow.

## Quick Expense Entry

The minimum required fields should be:

* Amount.
* Account.
* Category.
* Date.
* Save.

Use intelligent defaults such as:

* Current date.
* Current time.
* Most recently used account.
* Frequently used categories.
* User-specific preferences.

## Full Expense Fields

Support:

* Amount.
* Currency.
* Transaction date.
* Transaction time.
* Account.
* Category.
* Subcategory.
* Merchant.
* Paid by user.
* Beneficiary.
* Description.
* Notes.
* Location.
* Tags.
* Project.
* Cost center.
* Attachments.
* Personal or shared.
* Planned or unplanned.
* Recurring or one-time.
* Reimbursable.
* Requires later categorization.
* Included in budget.
* Transaction status.
* Created by.
* Updated by.
* Version.
* Offline client UUID.
* Idempotency key.

## Expense Statuses

Support statuses such as:

* Draft.
* Pending.
* Confirmed.
* Partially Categorized.
* Fully Categorized.
* Cancelled.
* Partially Refunded.
* Fully Refunded.

---

# 13. Quick Shopping Receipt Entry

This is a core feature.

Create a specialized operation named:

```text
Quick Shopping Receipt
```

The user must be able to record a supermarket, hypermarket, mall, pharmacy, or general shopping receipt in a few seconds.

## Minimum Quick Receipt Fields

The user should enter:

* Total amount.
* Currency.
* Merchant.
* Account or card used.
* Purchase date and time.
* User who paid.
* Optional receipt image.
* Optional note.

After saving:

* The full amount must immediately affect the selected account.
* The receipt must initially appear under an “Uncategorized Purchases” category.
* The receipt categorization status must be “Uncategorized”.
* The user may categorize it later.

## Example

A user pays 750 SAR at a supermarket.

The user records only:

```text
Merchant: Carrefour
Total: 750 SAR
Paid From: Main Bank Card
Date: Current Date
Receipt Image: Optional
```

The account is reduced by 750 SAR immediately.

The receipt can be analyzed and categorized later.

---

# 14. Receipt Categorization and Analysis

The user must be able to open a receipt later and categorize it into departments.

Example:

```text
Vegetables and Fruits: 120 SAR
Dairy and Cheese: 160 SAR
Meat and Poultry: 210 SAR
Cleaning Supplies: 90 SAR
Children Supplies: 110 SAR
Household Items: 60 SAR
Total: 750 SAR
```

## Receipt Summary Screen

Display:

* Receipt total.
* Categorized amount.
* Remaining uncategorized amount.
* Categorization completion percentage.
* Receipt status.
* Merchant.
* Account.
* Currency.
* Date.
* User who paid.
* Receipt image.
* Notes.

## Categorization Rules

* The sum of allocations must never exceed the receipt total.
* Partial categorization must be allowed.
* A receipt can be saved while partially categorized.
* The remaining amount must be clearly displayed.
* The user may allocate the remainder to “Other”.
* The receipt becomes Fully Categorized only when allocations equal the total.
* Categorization rows do not create new account movements.
* Product rows do not create new account movements.
* The receipt header represents the actual financial payment.
* Allocation rows represent analytical classification only.
* Account statements show one receipt transaction.
* Category reports use allocation rows.
* Total expenses must remain equal to the receipt total.
* The receipt must never be counted once as a header and again as allocations.

## Receipt Categorization Statuses

Support:

* Uncategorized.
* Partially Categorized.
* Fully Categorized.
* Needs Review.
* Cancelled.
* Partially Refunded.
* Fully Refunded.

---

# 15. Receipt Detail Levels

Support three levels of receipt detail.

## Level One: Receipt Total Only

The user records only the total amount.

## Level Two: Department Categorization

The user categorizes the receipt into broad departments such as:

* Vegetables and Fruits.
* Dairy and Cheese.
* Meat and Poultry.
* Fish and Seafood.
* Bakery.
* Dry Food.
* Frozen Food.
* Beverages.
* Sweets.
* Cleaning Supplies.
* Personal Care.
* Health Products.
* Children Supplies.
* Household Items.
* Clothing.
* Electronics.
* Other.

## Level Three: Individual Products

The user may optionally record individual products.

Example:

```text
Dairy and Cheese:
- Milk
- Cheese
- Yogurt
- Laban
```

Product-level entry must remain optional.

Do not force users to manually enter every product.

The default daily workflow should prioritize speed.

---

# 16. Receipt Allocation Data

Each receipt allocation should support:

* UUID or ULID.
* Receipt.
* Category.
* Amount in minor units.
* Optional beneficiary.
* Optional family member.
* Optional project.
* Optional cost center.
* Optional tags.
* Optional notes.
* Necessary or discretionary indicator.
* Budget inclusion indicator.
* Created by.
* Updated by.
* Version.

The system must display the remaining allocatable amount in real time.

---

# 17. Product-Level Receipt Items

Optional product items should support:

* UUID or ULID.
* Receipt.
* Allocation.
* Product name.
* Brand.
* Size.
* Unit.
* Quantity.
* Unit price.
* Total price.
* Barcode.
* Category.
* Merchant.
* Purchase date.
* Optional image.
* Notes.

Future reports may include:

* Product price history.
* Average product price.
* Price comparison between stores.
* Lowest recorded store price.
* Unusual price increases.
* Unit cost history.
* Purchase frequency.

---

# 18. Merchant Templates

Support merchant-specific templates.

For example, when the user selects Carrefour, the application may suggest:

* Vegetables.
* Dairy.
* Meat.
* Food.
* Cleaning.
* Children.
* Household.

Suggestions should be ordered based on:

* Categories used most often for that merchant.
* The current user.
* Recent receipts.
* Seasonal history.
* Household history.

The user must be able to customize merchant templates.

---

# 19. Future OCR and AI Receipt Analysis

Prepare the architecture for future OCR and AI support.

A future OCR process may extract:

* Merchant name.
* Branch.
* Date.
* Time.
* Receipt number.
* Total.
* Tax.
* Product names.
* Product quantities.
* Product prices.
* Payment method.

A future AI categorization feature may:

* Suggest categories for products.
* Group products into departments.
* Identify unusual prices.
* Detect likely duplicates.

OCR and AI results must never be automatically confirmed without user review.

The first version must not depend on OCR.

---

# 20. Income Management

Support income types such as:

* Salary.
* Allowance.
* Bonus.
* Commission.
* Freelance Income.
* Rental Income.
* Investment Return.
* Gift.
* Asset Sale.
* Reimbursement.
* Transfer from another person.
* Custom Income Type.

## Income Fields

Support:

* Amount.
* Currency.
* Income source.
* Receiving account.
* Expected date.
* Actual received date.
* Recurrence.
* Expected or confirmed status.
* Beneficiary user.
* Category.
* Notes.
* Attachments.
* Tags.
* Project.
* Shared or private.

## Income Statuses

Support:

* Expected.
* Due.
* Received.
* Delayed.
* Cancelled.

Expected income must not affect account balances until it is received and confirmed.

---

# 21. Transfers Between Accounts

Create a dedicated Transfer transaction type.

Support:

* Source account.
* Destination account.
* Sent amount.
* Received amount.
* Source currency.
* Destination currency.
* Exchange rate.
* Transfer fees.
* Date.
* Bank reference.
* Notes.
* Status.
* Created by.
* Updated by.

Transfer fees should be recorded as linked expenses where appropriate.

A transfer must not increase total household income or total household expenses.

---

# 22. Credit Card Support

Implement correct credit card behavior.

Support:

* Credit limit.
* Used balance.
* Available credit.
* Statement closing date.
* Payment due date.
* Minimum payment.
* Card purchases.
* Statement periods.
* Full payment.
* Partial payment.
* Fees.
* Interest.
* Due-date notifications.

Paying a credit card from a bank account must be treated as a transfer or liability settlement, not as a new purchase expense.

---

# 23. Categories and Subcategories

Provide default categories in both English and Arabic.

Users must be able to:

* Create categories.
* Edit categories.
* Archive categories.
* Reorder categories.
* Create subcategories.
* Assign icons.
* Assign colors.
* Use categories for expenses, income, or both.

Suggested expense categories include:

* Housing.
* Rent.
* Electricity.
* Water.
* Internet.
* Communications.
* Groceries.
* Vegetables and Fruits.
* Dairy and Cheese.
* Meat and Poultry.
* Restaurants.
* Transportation.
* Fuel.
* Vehicle Maintenance.
* Health.
* Medicine.
* Education.
* School.
* Children.
* Clothing.
* Entertainment.
* Travel.
* Installments.
* Debts.
* Gifts.
* Charity.
* Cleaning Supplies.
* Household Items.
* Personal Care.
* Subscriptions.
* Bank Fees.
* Emergency Expenses.
* Uncategorized Purchases.
* Other.

## Category Fields

Support:

* English name.
* Arabic name.
* Parent category.
* Category type.
* Icon.
* Color.
* Fixed or variable.
* Necessary or discretionary.
* Budget eligible.
* Active or archived.
* Display order.

System default categories should have English and Arabic names.

User-created categories may have:

* Required primary name.
* Optional English name.
* Optional Arabic name.

---

# 24. Shared Expenses and Family Allocation

Support splitting an expense among multiple people.

Split methods:

* Equally.
* By fixed amount.
* By percentage.
* By custom share.

Track:

* Who paid.
* Who benefited.
* Who is responsible for the cost.
* Each person’s share.
* Whether settlement has occurred.
* Remaining amount owed.
* Settlement date.
* Settlement transaction.

---

# 25. Budget Management

Create a professional budget engine.

Budgets may be:

* Weekly.
* Monthly.
* Yearly.
* Custom period.
* Category-based.
* Subcategory-based.
* Person-based.
* Account-based.
* Project-based.
* Tag-based.
* Household-location-based.

Examples:

* 2,000 SAR monthly groceries budget.
* 500 SAR monthly restaurant budget.
* 1,500 SAR children budget.
* 5,000 SAR travel budget for three months.

## Budget Fields

Support:

* Budget name.
* Period.
* Planned amount.
* Actual spending.
* Remaining amount.
* Usage percentage.
* Days remaining.
* Allowed daily average.
* Forecasted final spending.
* Expected overrun.
* Optional unused amount rollover.
* Optional overrun rollover.
* Warning thresholds.

## Budget Notifications

Support thresholds such as:

* 50%.
* 75%.
* 90%.
* 100%.
* Custom percentage.

Prevent unclear double-counting across overlapping budgets.

If overlapping budgets are allowed, clearly explain how each transaction affects each budget.

---

# 26. Financial Forecasting

Create a forecasting engine that is useful but explainable.

Forecast:

* Total expenses by the end of the month.
* Expenses by category.
* Expected income.
* Expected end-of-month balance.
* Possible cash shortage.
* Categories likely to exceed budget.
* Cash requirements until next salary.
* Daily average spending.
* Weekly average spending.
* Comparison with the previous month.
* Comparison with the last 3 months.
* Comparison with the last 6 months.
* Comparison with the last 12 months.
* Suggested next-month budget.
* Unusual spending increases.
* Seasonal spending patterns.

Forecast calculations may use:

* Actual spending to date.
* Historical averages.
* Moving averages.
* Confirmed recurring expenses.
* Upcoming bills.
* Planned expenses.
* Expected income.
* Remaining days.
* Uncategorized receipt totals.
* Historical seasonal periods.

Forecasts must be clearly labeled as estimates.

The application should explain the main factors used in each forecast.

Do not present forecasts as guaranteed facts.

---

# 27. Recurring Transactions

Support recurrence patterns such as:

* Daily.
* Weekly.
* Every two weeks.
* Monthly.
* Every two months.
* Quarterly.
* Semi-annually.
* Annually.
* Custom recurrence.

Examples:

* Salary.
* Rent.
* Electricity.
* Internet.
* School fees.
* Subscriptions.
* Installments.
* Family transfers.
* Weekly personal allowance.

Support:

* Start date.
* End date.
* Number of occurrences.
* Day of month.
* Automatic transaction creation.
* Reminder-only mode.
* Modify this occurrence only.
* Modify this and future occurrences.
* Modify the entire series.
* Pause recurrence.
* Resume recurrence.
* Cancel recurrence.

Handle months that do not contain the selected day.

---

# 28. Upcoming Bills and Commitments

Create an Upcoming Bills section.

Support:

* Utility bills.
* Rent.
* Subscriptions.
* Installments.
* Credit card payments.
* Debts.
* Insurance.
* School fees.
* Annual commitments.
* Custom bills.

Statuses:

* Expected.
* Due Soon.
* Due Today.
* Overdue.
* Paid.
* Cancelled.

Allow configurable notifications before the due date.

---

# 29. Savings Goals

Create savings goal functionality.

Examples:

* Emergency Fund.
* Car Purchase.
* Travel.
* Umrah.
* School Expenses.
* Home Renovation.
* Gold Purchase.
* Debt Repayment.

Goal fields:

* Name.
* Target amount.
* Currency.
* Current amount.
* Start date.
* Target date.
* Linked account.
* Planned monthly contribution.
* Completion percentage.
* Forecasted completion date.
* Manual contributions.
* Automatic contributions.
* Withdrawals.
* Withdrawal reason.
* Status.

Goal contributions must be linked to actual financial transactions.

---

# 30. Debts, Loans, and Installments

Support:

* Money owed to the household.
* Money owed by the household.
* Personal loans.
* Family loans.
* Installments.
* Advances.
* Credit liabilities.
* Long-term obligations.

Fields:

* Counterparty.
* Original amount.
* Remaining amount.
* Currency.
* Start date.
* Due date.
* Installment schedule.
* Fees.
* Interest.
* Payment account.
* Status.
* Attachments.
* Notes.

Every payment must be linked to a real financial transaction.

---

# 31. Tags, Projects, and Cost Centers

Support:

* Tags.
* Projects.
* Cost Centers.

Examples:

* Ramadan.
* Egypt Trip.
* Umrah.
* Yusuf School.
* Saudi Home.
* Egypt Home.
* Home Renovation.
* Eid.
* Medical Treatment.
* Family Event.

A transaction may have multiple tags.

---

# 32. Custom Fields

Support flexible custom fields without requiring major schema changes.

Field types:

* Text.
* Long Text.
* Number.
* Date.
* Date and Time.
* Single Select.
* Multi Select.
* Boolean.
* User.
* Account.
* Category.
* Attachment.

Examples:

* Child name.
* Country.
* House.
* Receipt number.
* Order number.
* Expense reason.
* Reimbursement organization.
* Event type.

Custom fields may apply to:

* Expenses.
* Income.
* Transfers.
* Receipts.
* Accounts.
* Goals.
* Debts.
* Specific categories.

---

# 33. Dashboard

Create a mobile-friendly dashboard displaying:

* Total account balances.
* Balances by currency.
* Current month income.
* Current month expenses.
* Net cash flow.
* Budget usage.
* Forecasted month-end spending.
* Forecasted month-end balance.
* Highest spending categories.
* Recent transactions.
* Uncategorized receipts.
* Partially categorized receipts.
* Upcoming bills.
* Upcoming income.
* Debts due.
* Savings goals.
* Important alerts.
* Comparison with the previous month.

Allow users to:

* Reorder dashboard cards.
* Hide cards.
* Save personal dashboard preferences.

---

# 34. Reports

Create professional reports for:

* Expenses.
* Income.
* Cash Flow.
* Expenses by Category.
* Expenses by Account.
* Expenses by User.
* Expenses by Currency.
* Expenses by Payment Method.
* Expenses by Merchant.
* Expenses by Project.
* Expenses by Tag.
* Budget Versus Actual.
* Necessary Versus Discretionary Expenses.
* Fixed Versus Variable Expenses.
* Monthly Comparison.
* Annual Comparison.
* Uncategorized Receipts.
* Receipt Department Analysis.
* Debts.
* Savings Goals.
* Subscriptions.
* Net Worth.
* Account Balance Changes.
* Product Price History.

## Report Filters

Support:

* Date range.
* User.
* Account.
* Category.
* Merchant.
* Currency.
* Transaction status.
* Transaction type.
* Tags.
* Project.
* Minimum amount.
* Maximum amount.
* Planned or unplanned.
* Personal or shared.

## Export

Support:

* CSV.
* Excel.
* PDF.
* Print.
* Structured JSON backup.

Exports must support English and Arabic.

Arabic PDF exports must correctly render Arabic characters and RTL layout.

---

# 35. Search

Provide fast search across:

* Amount.
* Date.
* Merchant.
* Description.
* Category.
* Account.
* User.
* Tags.
* Receipt number.
* Product name.
* Barcode.
* Notes.
* Custom fields.

---

# 36. Notifications

Support:

* In-app notifications.
* PWA push notifications.
* Email notifications where appropriate.

Examples:

* Budget approaching limit.
* Budget exceeded.
* Bill due soon.
* Installment due.
* Expected salary.
* Low account balance.
* Large transaction.
* Receipt needs categorization.
* User invitation.
* Important transaction modified.
* Important transaction deleted.
* Synchronization conflict.
* Synchronization failure.
* Product price increase.
* Subscription price increase.
* Login from a new device.

Users must be able to configure:

* Notification types.
* Notification channels.
* Notification timing.
* Accounts included.
* Categories included.
* Amount thresholds.
* Quiet hours.

Notifications must be localized in English and Arabic.

---

# 37. PWA Requirements

Implement a real PWA, not only a web manifest.

Required:

* Web App Manifest.
* Service Worker.
* Installability.
* App icons in required sizes.
* Splash screen support where possible.
* Standalone display mode.
* Offline mode.
* IndexedDB.
* Offline transaction queue.
* Background Sync where supported.
* Manual sync fallback.
* Push notifications.
* Cache strategy.
* Application update notification.
* Connectivity status.
* Last synchronization time.
* Manual synchronization button.
* Safe retry behavior.
* Android support.
* iOS support.
* Windows support.
* macOS support.

---

# 38. Offline and Synchronization Strategy

Use a local-first approach for quick daily entry.

Each offline-created record should include:

* Client UUID.
* Idempotency key.
* Device ID.
* Household ID.
* User ID.
* Local creation time.
* Local update time.
* Synchronization status.
* Version.
* Last synchronized time.
* Retry count.
* Error details.

Synchronization statuses:

* Pending.
* Syncing.
* Synced.
* Failed.
* Conflict.

## Idempotency

Every create operation from the PWA must include an idempotency key.

If the same request is sent multiple times because of a connection problem, only one financial record must be created.

Create an appropriate unique database constraint.

## Conflict Resolution

When the same record is modified on two devices:

* Never silently overwrite a newer version.
* Compare version numbers.
* Use optimistic locking.
* Mark the record as conflicted.
* Display both versions.
* Allow the user to choose one version.
* Allow field-level merge where practical.
* Preserve version history.
* Record the conflict in the audit log.

Financial conflicts must require explicit resolution when automatic merging may affect amounts, accounts, categories, dates, or balances.

---

# 39. User Experience Requirements

The application must be mobile-first.

Required:

* Full English LTR support.
* Full Arabic RTL support.
* Light mode.
* Dark mode.
* Bottom navigation on phones.
* Sidebar on desktop.
* Floating add button.
* Large touch-friendly controls.
* Fast amount entry.
* Numeric keypad optimization.
* Correct localized currency display.
* Skeleton loading states.
* Useful empty states.
* Undo for safe operations.
* Duplicate previous transaction.
* Favorite accounts.
* Favorite categories.
* Smart defaults.
* Draft saving.
* Clear confirmations.
* Clear errors.
* No unnecessary full-page reloads.
* Accessible form labels.
* Keyboard navigation.
* Screen-reader-friendly controls.
* Adequate contrast.
* Responsive tables.

---

# 40. Quick Action Menu

Provide a primary action button containing:

* Add Expense.
* Add Income.
* Add Quick Shopping Receipt.
* Categorize Receipt.
* Transfer Between Accounts.
* Add Debt.
* Add Savings Goal.
* Reconcile Account.
* Capture Receipt Image.

All labels must be translated into Arabic.

---

# 41. Attachments

Support:

* Images.
* PDF files.
* Multiple attachments.
* Image compression.
* Attachment previews.
* Secure storage.
* Temporary authorized download links.
* File type validation.
* File size limits.
* Safe deletion.
* Authorization checks.

Uploading an attachment must not occur inside a long-running financial database transaction.

---

# 42. Audit Log

Create a detailed audit log.

Record:

* User.
* Household.
* Entity type.
* Entity identifier.
* Action.
* Old values.
* New values.
* Date and time.
* IP address.
* User agent.
* Device identifier.
* Operation source.
* Web or installed PWA.
* Online or offline synchronization source.
* Optional reason for sensitive changes.

Audit events should include:

* Creation.
* Update.
* Deletion.
* Restoration.
* Categorization.
* Reconciliation.
* Backup.
* Restore.
* Role changes.
* Permission changes.
* Ownership transfer.
* Conflict resolution.

Audit logs must not be casually editable by normal application users.

---

# 43. Duplicate Detection

Detect possible duplicate transactions based on:

* Amount.
* Account.
* Date.
* Time.
* Merchant.
* User.
* Reference number.
* Receipt number.
* Receipt image fingerprint where possible.
* Client UUID.
* Idempotency key.

Display a warning but allow the user to continue when appropriate.

Idempotency-key duplicates must be blocked automatically.

---

# 44. Reconciliation

Create an account reconciliation feature.

The user can enter the real-world account balance.

The system must:

* Compare it to the calculated balance.
* Display the difference.
* Allow the user to review missing transactions.
* Allow a controlled reconciliation adjustment.
* Record the adjustment separately.
* Require a reason.
* Record the user in the audit log.

A reconciliation difference must not silently modify previous transactions.

---

# 45. Suggested Backend Architecture

Use a clean, maintainable Laravel architecture.

Suggested services or action classes:

```text
TransactionService
AccountBalanceService
ExpenseService
IncomeService
TransferService
ReceiptService
ReceiptAllocationService
ReceiptItemService
CurrencyConversionService
BudgetCalculationService
ForecastService
RecurringTransactionService
CreditCardService
DebtService
SavingsGoalService
ReconciliationService
SynchronizationService
ConflictResolutionService
BackupService
AuditService
NotificationService
```

Controllers should remain thin.

Controllers should:

* Receive the request.
* Authorize the action.
* Validate the input.
* Call an Action or Service.
* Return an API Resource.

Do not place complex financial logic in controllers or Vue components.

Use DTOs when they improve clarity.

Do not use unnecessary design patterns that add complexity without benefit.

---

# 46. API Structure

Create a versioned REST API such as:

```text
/api/v1/auth
/api/v1/profile
/api/v1/preferences
/api/v1/households
/api/v1/households/{household}/members
/api/v1/accounts
/api/v1/account-types
/api/v1/currencies
/api/v1/exchange-rates
/api/v1/categories
/api/v1/merchants
/api/v1/transactions
/api/v1/expenses
/api/v1/incomes
/api/v1/transfers
/api/v1/receipts
/api/v1/receipts/{receipt}/allocations
/api/v1/receipts/{receipt}/items
/api/v1/budgets
/api/v1/recurring-transactions
/api/v1/bills
/api/v1/goals
/api/v1/debts
/api/v1/reports
/api/v1/notifications
/api/v1/sync
/api/v1/conflicts
/api/v1/backups
/api/v1/audit-logs
```

Use:

* Authentication middleware.
* Household scope middleware.
* Authorization policies.
* Form Request validation.
* API Resources.
* Pagination.
* Standard error responses.
* Idempotency middleware.
* Rate limiting.
* Audit logging.
* API versioning.

---

# 47. Suggested Database Design

Design a professional ERD.

The initial schema may include:

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
merchants
merchant_templates
merchant_template_categories

transactions
transaction_entries
transaction_splits
transaction_allocations
transaction_versions

receipts
receipt_allocations
receipt_items

recurring_rules
recurring_occurrences

budgets
budget_lines
budget_periods

upcoming_bills

income_sources

savings_goals
goal_contributions

debts
debt_installments
settlements

projects
cost_centers
tags
transaction_tags

custom_fields
custom_field_options
custom_field_values

attachments

notifications
notification_preferences

sync_operations
sync_conflicts

audit_logs

backups
backup_logs
```

You may improve the schema if a better design exists.

Explain all important schema decisions.

---

# 48. Receipt Database Model

A recommended receipt design is:

## Receipt Header

The receipt header represents the actual payment.

Fields may include:

* id.
* uuid.
* household_id.
* transaction_id.
* account_id.
* merchant_id.
* paid_by_user_id.
* currency_id.
* total_minor_amount.
* base_currency_minor_amount.
* exchange_rate.
* transaction_date.
* transaction_time.
* receipt_status.
* categorization_status.
* receipt_number.
* notes.
* version.
* idempotency_key.
* client_uuid.
* created_by.
* updated_by.
* created_at.
* updated_at.
* deleted_at.

## Receipt Allocations

Allocations represent analytical distribution.

Fields may include:

* id.
* uuid.
* receipt_id.
* category_id.
* amount_minor.
* beneficiary_user_id.
* project_id.
* cost_center_id.
* notes.
* version.
* created_by.
* updated_by.
* timestamps.

## Receipt Items

Items are optional product details.

Fields may include:

* id.
* uuid.
* receipt_id.
* allocation_id.
* product_name.
* brand.
* quantity.
* unit.
* unit_price_minor.
* total_price_minor.
* barcode.
* category_id.
* notes.
* timestamps.

## Receipt Constraints

Enforce:

* Receipt total must be positive.
* Allocation amount must be positive.
* Sum of active allocations must not exceed receipt total.
* Fully categorized status requires allocation total to equal receipt total.
* Receipt items must not change account balance.
* Receipt allocations must not change account balance.
* Receipt cancellation must follow financial reversal rules.
* Refunds must be recorded explicitly.
* Deleted allocations must update categorization totals.
* Version checks must prevent stale updates.

---

# 49. Database Constraints and Indexes

Use:

* Foreign keys.
* Unique constraints.
* Composite indexes.
* Not-null constraints.
* Check constraints where SQLite support is reliable.
* Soft deletes.
* Version columns.
* Status validation.
* Default values.

Important indexes should include:

* household_id.
* user_id.
* account_id.
* category_id.
* merchant_id.
* transaction_date.
* transaction_type.
* transaction_status.
* currency_id.
* created_at.
* updated_at.
* client_uuid.
* idempotency_key.
* receipt categorization status.
* recurring due date.
* bill due date.

Do not use cascading deletion in a way that can remove financial history.

Archive accounts and categories when they already have transactions.

---

# 50. Backup and Restore

Create an SQLite-specific backup system.

Support:

* Manual backups.
* Scheduled backups.
* Backup retention rules.
* Compressed backups.
* Optional encrypted backups.
* Backup verification.
* Backup status history.
* Protected restore workflow.
* Restore authorization.
* Restore confirmation.
* Restore audit logging.

Do not copy the active SQLite file unsafely.

Use an SQLite-compatible backup method such as the SQLite backup command or API.

Example:

```bash
sqlite3 database/database.sqlite ".backup 'storage/app/backups/database-backup.sqlite'"
```

Ensure the strategy is compatible with WAL mode.

Provide database health checks such as:

```sql
PRAGMA integrity_check;
PRAGMA foreign_key_check;
```

Record health-check results.

---

# 51. Testing Requirements

Create comprehensive automated tests.

## Unit Tests

Test:

* Money minor-unit conversions.
* Currency conversion.
* Rounding.
* Account balances.
* Receipt totals.
* Partial receipt categorization.
* Full receipt categorization.
* Prevention of allocation overrun.
* Prevention of receipt double-counting.
* Budget calculations.
* Forecast calculations.
* Recurring transactions.
* Credit card behavior.
* Transfers.
* Refunds.
* Debt calculations.
* Installments.
* Reconciliation.
* Optimistic locking.
* Idempotency.

## Feature Tests

Test:

* Registration.
* Login.
* Email verification.
* Password reset.
* Household creation.
* User invitation.
* Role assignment.
* Permission enforcement.
* Account creation.
* Expense creation.
* Income creation.
* Transfer creation.
* Quick receipt creation.
* Receipt partial categorization.
* Receipt full categorization.
* Attachment upload.
* Budget creation.
* Report filters.
* Export.
* Offline synchronization API.
* Conflict generation.
* Conflict resolution.
* Backup authorization.
* Audit log creation.

## SQLite-Specific Tests

Test:

* Foreign keys are enabled.
* Transactions commit correctly.
* Transactions roll back correctly.
* Unique constraints work.
* Idempotency keys prevent duplicates.
* Optimistic locking rejects stale versions.
* Financial deletion restrictions work.
* Database busy retry logic works.
* Backup creation works.
* Backup validation works.
* Integrity check works.
* Financial integer storage is correct.
* No floating-point rounding error exists.

Use:

```env
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

for suitable tests.

Use a dedicated SQLite test file for tests requiring:

* WAL mode.
* Multiple connections.
* Persistent state.
* Backup tests.
* Lock simulation.
* Concurrency tests.

Never run tests against the real production database.

## End-to-End Tests

Use Playwright or Cypress.

Test complete user journeys such as:

1. Register an account.
2. Create a household.
3. Switch language from English to Arabic.
4. Confirm RTL layout.
5. Invite a spouse.
6. Create a bank account.
7. Create a cash wallet.
8. Create a credit card.
9. Record a salary.
10. Record a quick shopping receipt.
11. Categorize part of the receipt into vegetables and dairy.
12. Save it as partially categorized.
13. Complete categorization using meat and cleaning supplies.
14. Confirm the receipt total was not double-counted.
15. Confirm one account movement exists.
16. Confirm category reports use allocations.
17. Create a monthly budget.
18. Exceed a budget threshold.
19. Receive a localized notification.
20. Work offline.
21. Create an offline expense.
22. Restore connectivity.
23. Synchronize the expense.
24. Simulate an edit conflict.
25. Resolve the conflict.
26. Install the PWA.

---

# 52. Performance Requirements

Apply:

* Pagination.
* Lazy loading.
* Database indexing.
* Query optimization.
* Prevention of N+1 queries.
* Appropriate caching.
* Background jobs for long exports.
* Chunked exports.
* Image compression.
* Frontend code splitting.
* Bundle optimization.
* Database query profiling.
* Efficient dashboard aggregation.
* Efficient report filtering.

The application should remain responsive with thousands or tens of thousands of transactions in a household.

---

# 53. Demo and Seed Data

Create realistic seed data including:

* A husband.
* A wife.
* A family household.
* A SAR bank account.
* A SAR cash wallet.
* An EGP account.
* A credit card.
* Monthly salaries.
* Recurring rent.
* Recurring internet bill.
* A supermarket receipt.
* Partial receipt categorization.
* Full receipt categorization.
* A groceries budget.
* A savings goal.
* A family debt.
* An international transfer.
* A restricted user.
* English and Arabic default categories.
* English and Arabic system labels.

---

# 54. Required Screens

Prepare and implement screens for:

* Splash and loading.
* Login.
* Registration.
* Email verification.
* Password reset.
* Language selection.
* Household selection.
* Household creation.
* Dashboard.
* Accounts.
* Account details.
* Account reconciliation.
* Transactions.
* Quick expense.
* Full expense.
* Income.
* Transfers.
* Quick shopping receipt.
* Receipt details.
* Receipt categorization.
* Product details.
* Merchants.
* Categories.
* Budgets.
* Recurring transactions.
* Upcoming bills.
* Savings goals.
* Debts.
* Reports.
* Notifications.
* Search.
* Household members.
* Roles and permissions.
* Audit logs.
* Backups.
* Synchronization status.
* Conflict resolution.
* User profile.
* Language and appearance settings.
* Security and active sessions.

---

# 55. MVP Scope

The first production-ready MVP must include:

* Authentication.
* English as the default language.
* Full Arabic support.
* Full LTR and RTL support.
* Household workspaces.
* Household members.
* Roles and permissions.
* Accounts and wallets.
* Multiple currencies.
* Categories.
* Expenses.
* Income.
* Transfers.
* Credit card basics.
* Quick shopping receipts.
* Partial receipt categorization.
* Full receipt categorization.
* Category-based receipt reporting.
* Monthly budgets.
* Basic forecasting.
* Recurring transactions.
* Upcoming bills.
* Dashboard.
* Basic reports.
* Audit logs.
* Attachments.
* PWA installation.
* Offline queue.
* Synchronization.
* Conflict detection.
* CSV and Excel export.
* SQLite backups.
* Comprehensive automated tests.

The following may be postponed until later phases:

* OCR.
* AI product categorization.
* Advanced product price comparison.
* Automatic bank integration.
* Automatic live exchange rates.
* Complex investment tracking.
* Advanced predictive machine learning.
* Passkeys.
* Phone login.

---

# 56. Implementation Phases

## Phase 1: Analysis and Architecture

Deliver:

* Project understanding.
* Functional requirements.
* Non-functional requirements.
* MVP definition.
* User stories.
* Use cases.
* Architecture diagram.
* ERD.
* Permission matrix.
* Financial rules.
* Receipt categorization rules.
* Localization strategy.
* Offline strategy.
* Synchronization strategy.
* Conflict resolution strategy.
* Security plan.
* Testing plan.
* Risks and mitigations.

## Phase 2: Project Foundation

Implement:

* Laravel application.
* Vue application.
* TypeScript.
* SQLite configuration.
* Authentication.
* API versioning.
* Localization framework.
* English translations.
* Arabic translations.
* LTR and RTL layout.
* Household model.
* Member model.
* Permission system.
* CI test workflow.

## Phase 3: Core Financial Structure

Implement:

* Currencies.
* Accounts.
* Account types.
* Categories.
* Merchants.
* Financial transaction engine.
* Expenses.
* Income.
* Transfers.
* Balance calculation.
* Audit logs.

## Phase 4: Shopping Receipts

Implement:

* Quick receipt entry.
* Receipt attachments.
* Partial categorization.
* Full categorization.
* Remaining amount calculation.
* Receipt reporting.
* Double-count prevention.
* Merchant templates.
* Optional product items.

## Phase 5: Budgeting and Forecasting

Implement:

* Budgets.
* Budget lines.
* Budget periods.
* Budget notifications.
* Dashboard.
* Basic forecasting.
* Reports.
* Export.

## Phase 6: Recurring Financial Activity

Implement:

* Recurring expenses.
* Recurring income.
* Upcoming bills.
* Credit card due dates.
* Notifications.

## Phase 7: PWA and Offline Operation

Implement:

* Manifest.
* Service worker.
* IndexedDB.
* Offline queue.
* Synchronization API.
* Idempotency.
* Optimistic locking.
* Conflict resolution.
* Connectivity status.
* Installability.
* Push notifications.

## Phase 8: Advanced Financial Features

Implement:

* Savings goals.
* Debts.
* Installments.
* Settlements.
* Custom fields.
* Product price history.
* Advanced reports.

## Phase 9: Production Preparation

Complete:

* Security review.
* Performance review.
* Accessibility review.
* Translation review.
* Arabic RTL review.
* Test coverage.
* Backup validation.
* Restore testing.
* Production configuration.
* Deployment documentation.
* Monitoring.
* Error tracking.
* Release checklist.

Do not move to a new phase while required tests from the current phase are failing.

---

# 57. Mandatory Deliverables Before Coding

Before writing implementation code, provide:

1. A concise project understanding.
2. Confirmed mandatory technology stack.
3. MVP scope.
4. Deferred features.
5. Functional requirements.
6. Non-functional requirements.
7. Architecture diagram.
8. ERD.
9. Permissions matrix.
10. User stories.
11. Use cases.
12. Screen list.
13. Main user flows.
14. Financial transaction rules.
15. Receipt categorization rules.
16. Multi-currency strategy.
17. Localization strategy.
18. English and Arabic translation structure.
19. RTL strategy.
20. Offline strategy.
21. Synchronization strategy.
22. Idempotency strategy.
23. Conflict resolution strategy.
24. SQLite concurrency strategy.
25. Security plan.
26. Backup and restore plan.
27. Testing plan.
28. Implementation phases.
29. Technical risks.
30. Risk mitigation plan.

After presenting these deliverables, begin implementation immediately in small, testable phases.

---

# 58. Strict Implementation Rules

Follow these rules without exception:

* Do not create a mockup-only project.
* Do not create non-functional buttons.
* Do not leave core features as TODO comments.
* Do not use fake data in final functionality.
* Do not place critical financial calculations only in the frontend.
* Do not trust frontend totals.
* Do not trust frontend permissions.
* Do not trust frontend exchange rates without backend validation.
* Use database transactions for financial writes.
* Keep SQLite write transactions short.
* Do not use floating-point money values.
* Do not hard-delete financial history by default.
* Do not double-count receipts and allocations.
* Do not count transfers as income or expenses.
* Do not count credit card payments as new purchases.
* Use idempotency for create operations.
* Use optimistic locking for updates.
* Maintain complete audit trails.
* Enforce household isolation in the backend.
* Use clean code.
* Use clear naming.
* Avoid oversized controllers.
* Avoid oversized services.
* Avoid oversized Vue components.
* Separate responsibilities.
* Document architectural decisions.
* Use migrations, factories, and seeders.
* Write tests for every critical financial rule.
* Run tests after every phase.
* Do not consider a phase complete when tests fail.
* Do not commit secrets.
* Provide a complete `.env.example`.
* Provide clear local and production setup instructions.
* Ensure the application works after cloning the repository.
* Ensure English is the default language.
* Ensure Arabic is fully functional.
* Do not hardcode frontend labels.
* Do not release with missing translation keys.
* Do not release with broken RTL layouts.

---

# 59. Local Development Setup

Provide a clear setup process similar to:

```bash
git clone <repository-url>
cd <project-directory>

composer install

cp .env.example .env

php artisan key:generate

touch database/database.sqlite

php artisan migrate --seed

npm install

npm run build

php artisan test

php artisan serve
```

The `.env.example` must include a working SQLite configuration.

Also document:

* Required PHP extensions.
* Required Node.js version.
* Storage permissions.
* Queue configuration.
* Scheduler configuration.
* PWA development behavior.
* Production build process.

Required PHP extensions should include:

```text
pdo_sqlite
sqlite3
openssl
mbstring
fileinfo
json
tokenizer
ctype
curl
intl
```

---

# 60. Production Hosting Requirements

The application must be deployable on hosting that supports:

* PHP.
* Laravel.
* SQLite.
* PDO SQLite.
* SQLite3.
* HTTPS.
* Writable private storage.
* Cron jobs.
* Queue worker or a suitable queue execution method.
* Secure attachment storage.
* Scheduled backups.

The SQLite database must be stored outside the public directory.

Production documentation must include:

* Environment configuration.
* Database path.
* File permissions.
* Queue worker.
* Scheduler.
* Backup schedule.
* HTTPS requirement.
* Cache clearing.
* Migration process.
* Rollback plan.
* Health checks.
* Monitoring.
* Error logs.

---

# 61. Acceptance Criteria

The product is considered ready when a user can:

* Register an account.
* Log in securely.
* Use English by default.
* Switch to Arabic.
* See the full interface switch to RTL.
* Create a family household.
* Invite a spouse.
* Assign permissions.
* Add a bank account.
* Add a cash wallet.
* Add a credit card.
* Use multiple currencies.
* Record a salary.
* Record an expense.
* Record a supermarket receipt quickly.
* Upload a receipt image.
* Leave the receipt uncategorized.
* Open the receipt later.
* Categorize part of it as vegetables and dairy.
* Save partial categorization.
* Complete it later using meat, cleaning supplies, and household items.
* See the remaining uncategorized amount.
* Confirm that the receipt was deducted only once.
* See one account statement movement.
* See category reports based on receipt allocations.
* Create a monthly budget.
* See actual spending.
* See forecasted spending.
* Receive a budget warning.
* Create a transfer.
* Record a currency exchange rate.
* Work temporarily without internet.
* Create an offline expense.
* Reconnect to the internet.
* Synchronize safely.
* Avoid duplicate transactions after retries.
* Detect edit conflicts.
* Resolve conflicts.
* Install the application on a phone.
* Install the application on a computer.
* Export data.
* Create a backup.
* Validate a backup.
* View audit history.
* Identify who created or modified each transaction.
* Protect household data from unauthorized users.

---

# 62. Progress Report After Every Phase

After each implementation phase, provide a structured report containing:

* Phase name.
* Features completed.
* Files created.
* Files modified.
* Database migrations added.
* Tables created.
* API endpoints completed.
* Vue screens completed.
* English translations added.
* Arabic translations added.
* RTL checks completed.
* Tests executed.
* Test results.
* Code coverage where available.
* Security checks completed.
* Performance checks completed.
* Known issues.
* Remaining risks.
* Technical debt.
* Next implementation step.

Do not claim that a feature is complete unless:

* Its backend logic exists.
* Its frontend flow exists.
* Authorization is implemented.
* Validation is implemented.
* Localization is implemented.
* Relevant tests pass.

Begin now with the analysis and architecture phase. After completing the required architecture deliverables, proceed with implementation in small, testable, production-quality increments.
