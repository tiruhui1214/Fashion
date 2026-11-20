# Fashion stages 1–7 — installation and upgrade

The updated source is prepared on Desktop. Existing XAMPP files, uploads, local configuration and live databases are not changed automatically.

## Existing XAMPP installation

1. Back up the old Fashion folder (especially uploads and config.local.php), and export the database in phpMyAdmin.
2. Copy the updated source into `C:\xampp\htdocs\Fashion`. Preserve your existing uploads, `.installed` file and `config.local.php`. Do not replace them with files from a different installation. Folder copy and database migration are separate operations.
3. Start MySQL in XAMPP.
4. Run in PowerShell:

   ```powershell
   Set-Location 'C:\xampp\htdocs\Fashion'
   & 'C:\xampp\php\php.exe' '.\database\migrations\20261120_features.php'
   ```

   This also runs the previous promotion migration. It adds missing fields/tables, backfills payment records from existing stored order totals, and can be rerun. MariaDB DDL commits implicitly; a failure may leave some new fields already created. Fix the reported issue and rerun, rather than deleting data.
5. Open the store. If an upgrade notice remains, confirm the migration ran from the deployed folder with the same database configuration.
6. Complete missing `state` values in old saved addresses; they cannot be inferred safely from the old records. Add product style/occasion tags and verify each variant's minimum-stock threshold.

Do not rerun setup.php or import the fresh schema over an existing populated database. The application intentionally shows an upgrade notice before loading new fields against an old schema.

## Fresh installation

Copy the source, start Apache/MySQL and run setup.php as before. The updated schema already contains the new fields. Do not copy a test `.installed`, test config, or test database.

## Included behaviour

1. Promotions: admin CRUD/search, uppercase unique codes, enabled/date/minimum-spend validation; percentage discount on merchandise only. RM200 free-shipping threshold uses merchandise subtotal before discount. Checkout revalidates the code and stores code/discount snapshots. All promotion times use Malaysia time.
2. Cart: persistent item selections, select all, selected total, auto-submit checkbox changes with JavaScript and explicit Update selection without JavaScript. Only selected purchased items are removed. Quantity changes, price changes and changed discount amounts require a refreshed checkout confirmation. Unavailable selected products block checkout.
3. Customers/addresses: optional profile phone, Malaysian address/state fields, a default address, saved-address picker with ownership checks, searchable admin profiles and order history, deactivation that also blocks existing sessions. Customers with orders cannot be physically deleted.
4. Products: per-variant minimum stock and activation, admin product name search, same-variant colour/size filters, common size options, editable comma-separated style/occasion tags. Purchased products/variants cannot be physically deleted; deactivate/unpublish them instead. Product creation audit is recorded for new products; legacy creator fields remain nullable.
5. Reviews: completed-purchase qualification, one review per customer/product, editable prefilled own review, deletion, visible-only average/count, admin search/hide/restore, moderator/time. Customer deletion uses an internal customer_deleted flag: it removes the review from public display and averages while retaining moderation audit. Recreating a deleted hidden review does not bypass moderation. Only an administrator can restore its hidden state.
6. Recommendations: independent signed-in chat and automatic complements for selected cart items. Catalogue rules work without a key. Optional model service and cache are described in AI-SETUP.md. No fake AI claims, invented product IDs or anonymous external API endpoint.
7. Orders: reference/customer search, status filters, one successful simulated payment record per order, status actor/history, cancellation only before shipping with one stock restore, deletion only for cancelled demonstration orders. Simulated payment failures create no order/payment and leave cart/stock unchanged. No refund processor is introduced.

## Schema/report alignment

Implementation intentionally retains the existing `users` table with `role` rather than splitting Admin/Customer; cart ownership is `cart_items.user_id`, without a redundant Cart header table. `payments.order_id` is unique: this system stores one successful simulated payment per order, not multiple failed attempts. Add order_status_history to the ERD if showing the implementation in full. Update the class diagram/data dictionary to these actual choices. Payment failure is a transient result, not a stored pre-order payment attempt.

Order delivery details remain an independent text snapshot; deleting a saved address clears the optional source reference only. Product identity/price/colour/size/image snapshots remain on order items. Deleting a promotion clears its optional reference, not its saved code, discount or totals.

Brand/logo/photography changes (phase 8) are outside this delivery; the existing FORME theme and copy are retained.
