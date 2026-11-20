# Promotion Management — stage 1

## Agreed rules and design

Use the current PHP/PDO application and users table. A promotion is created by a user with the admin role (enforced by the future admin action, as a foreign key cannot enforce the role). Codes will be normalised to uppercase by the future administration form. One percentage promotion per order; minimum spend uses the merchandise subtotal; shipping is excluded from the discount. Discount rates are percentages: 10.00 means 10%, not 0.10. Dates are inclusive and will use Malaysia local time consistently when checkout is integrated.

The promotions table contains a unique code, creator, percentage, minimum purchase amount, start/end datetime, enabled flag and timestamps. The orders table gains a nullable promotion reference, a code snapshot and a discount amount defaulting to zero. Deleting a promotion clears only its reference; the code snapshot, discount and stored order totals remain. No existing rows, amounts or tables are removed.

## Upgrade an existing installation

1. Export the configured database in phpMyAdmin before upgrading.
2. Run from the actual deployed Fashion folder (the project also exists under Desktop and XAMPP; these are separate copies):

   ```powershell
   & C:\xampp\php\php.exe .\database\migrations\20261120_promotions.php
   ```

3. The script uses that copy's config.local.php, or config.example.php if absent. It is CLI-only and may be rerun after a partial DDL failure. MariaDB DDL is not transactional. It expects the current application's existing users/orders tables; it does not silently convert an unrelated schema.
4. Do not rerun setup.php or import schema.sql over an existing installation. The updated schema.sql is for new installations.

## Implementation sequence

- Stage 1: add schema and repeatable CLI upgrade; lint the PHP script. Inspect a fresh schema and upgrade on a disposable MariaDB database before applying to existing data.
- Stage 2: add Promotions admin menu, CRUD and code/status search. Validate format, uniqueness, 0 < rate <= 100, minimum >= 0 and date range. Enforce admin role and CSRF.
- Stage 3: apply/remove code at checkout; validate active flag, dates and subtotal, recompute in integer cents on the server. No discount on shipping.
- Stage 4: persist promotion/code/discount snapshots atomically with the order. Historical totals must survive promotion edits/deletion.
- Stage 5: test invalid/expired/disabled codes, threshold boundaries, duplicate codes, repeat checkout and historical snapshots.

## Stage 1 verification

PHP lint passed. On 20 November 2026, an isolated MariaDB 10.11.16 instance on port 13379 verified the fresh schema, repeated migration, retention of a historical order total, and retention of order code/discount snapshots when deleting its promotion. No existing desktop/XAMPP database was upgraded. Stage 2 adds the administration UI; checkout integration remains pending.


## Stage 2: administration

Open Manage store → Promotions. Add/edit/delete a percentage promotion and search by code or Active/Scheduled/Expired/Disabled status. Times are Malaysia time (UTC+8); an end time of 23:59 means 23:59:00 exactly. Codes are uppercased and unique; rates allow two decimal places from 0.01 to 100.00. Minimum spend is RM 0.00–999999.99. Start/end dates are required and end must not precede start.

Only administrator POST actions can change promotions, using the existing CSRF check. Duplicate codes are rejected by application checks and a database unique key. Deletion leaves historical order snapshot fields intact through ON DELETE SET NULL. A missing promotions table displays an upgrade notice instead of querying a nonexistent table.

Run pure rule checks with `C:\xampp\php\php.exe tests/promotions.php`. Checkout does not yet apply codes; stage 3 adds that integration. Never claim these rule checks prove database integration or browser behaviour.

### Validation record — 20 November 2026

- PHP 8.5.12 lint: application PHP files and migration passed.
- 32 pure rules checks passed: code normalisation and malformed inputs, percentage/amount bounds and precision, invalid dates/date ordering, disabled state, Malaysia-time start/end boundary statuses.
- 14 HTTP checks passed using a separate test copy and disposable MariaDB: admin screen, create, duplicate rejection, invalid rate, matching/empty code searches, edit prefill, update, active/disabled filtering, delete, customer page denial, customer mutation denial with valid CSRF, invalid-CSRF rejection.
- No visual browser inspection is claimed; HTTP checks verify returned pages and responses rather than screenshots.
- Only the desktop source is updated. Copy it to XAMPP as planned, back up the deployed database and run the migration from that deployed copy before opening Promotions. Do not copy temporary test configuration or databases.
