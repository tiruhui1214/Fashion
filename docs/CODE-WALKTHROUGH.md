# Learning and presentation walkthrough

## Request flow

1. Apache runs `index.php`. `bootstrap.php` starts the session and opens PDO on demand.
2. The page name is selected from an explicit allowlist. Protected pages verify sign-in and role.
3. POST requests go to `actions.php`. A session CSRF token is checked before mutation.
4. The action validates input, checks ownership/role, runs prepared SQL, sets a feedback message and redirects.
5. A view file reads the saved state and renders escaped HTML. `app.js` handles progressive enhancements.

Use the browser's Network panel to follow a POST → redirect → GET. Refreshing a result page should not repeat a form submission.

## Six module mapping

| Module | Main files/tables | Main operations and special features |
|---|---|---|
| Product | catalog.php, admin.php, admin-feature-actions.php; categories/products/variants/product_images | CRUD, search, same-variant filters, activation, per-variant low-stock alerts |
| Promotion | admin-promotions.php, admin-promotions-actions.php, promotions.php; promotions/orders | CRUD/search, code validation, checkout discount and historical snapshot |
| Customer | account.php, account-profile.php, admin-customers.php, feature-actions.php; users/addresses | Registration/profile, default saved addresses, admin search/history/deactivation |
| Shopping cart | shop.php, shop-checkout.php, feature-actions.php; cart_items | Selection, totals, quantity/removal and selected-only checkout; complementary recommendations |
| Order | admin-orders.php, feature-services.php, admin-feature-actions.php; orders/order_items/payments/order_status_history | Simulated payment, search/status, cancellation and once-only stock restoration |
| Review and Rating | product-reviews.php, admin-reviews.php, feature-actions.php; reviews | Completed-purchase reviews, editing, moderation, visible-only average/count |

Authentication and content/brand management are shared support. The floating recommendation assistant and selected-cart complements are independent entry points; see [README — AI 配置](../README.md#ai). Actual individual contributions and tutor-approved module names must match the report. PHP files are under app/ unless otherwise noted.

## Explain these business rules in your own words

- Product price is always fetched from MariaDB; a submitted form cannot choose its own price.
- Colour + size is a variant. Each variant has a unique SKU and separate stock.
- Bag quantities are not reservations. Checkout checks stock again under a transaction and row locks.
- Money is accumulated as integer cents in PHP; SQL stores decimal amounts.
- Shipping is RM 8 below RM 200 and free from RM 200.
- An idempotency key links a checkout attempt to one order; retrying the same successful form returns that order.
- The order stores a snapshot of product name, colour, size, image and price, so later product edits do not rewrite the order.
- Only Confirmed/Processing orders can be cancelled. A locked order prevents cancelling it twice and increasing stock twice.
- Only administrators can delete Cancelled demonstration orders. Removing one does not restore stock again.
- Reviews require a completed order containing the product. Rating is limited to 1–5.
- Uploads use random names and verified MIME types. Only JPEG, PNG and WebP are accepted; 5 MB limit.

## A practical learning sequence

1. Use a separate development database or the shared deployed site. Run the installer only for a fresh empty local database; never reinstall the populated shared store.
2. Change one CSS colour and one heading, save and refresh.
3. Trace product retrieval from the SQL query to the card HTML.
4. Add a product and one variant through the admin UI; inspect both rows in phpMyAdmin.
5. Trace one complete CRUD module and record normal/error cases.
6. Trace checkout and cancellation; compare stock before and after.
7. Each member implements and explains agreed changes in their two modules, keeps a truthful contribution log, and creates their own diagrams/report evidence.

The project is not a replacement for your individual understanding, original work, approved feature scope, or assignment documentation. The specification does not state an AI policy; follow any tutor guidance provided separately.

## Catalogue header implementation

`catalog-banners.php` builds an allowlist of department and existing category targets. The display-only editor stores five settings per target (image, mobile image, title, subtitle and text colour) in the existing settings table. All writes require administrator access and CSRF validation. A subcategory without an image override inherits the department image; reset deletes only that target's five keys. The front-end header has no anchor, button, click handler or destination URL.

## Database versus brand

HIFIVE is the storefront name. Database names and service credentials come from config.local.php and need not match the brand. An existing legacy brand_name value is displayed as HIFIVE; newly entered custom brand names remain supported. Existing SKU/order references are historical identifiers, not labels to rewrite.
