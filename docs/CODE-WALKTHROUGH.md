# Learning and presentation walkthrough

## Request flow

1. Apache runs `index.php`. `bootstrap.php` starts the session and opens PDO on demand.
2. The page name is selected from an explicit allowlist. Protected pages verify sign-in and role.
3. POST requests go to `actions.php`. A session CSRF token is checked before mutation.
4. The action validates input, checks ownership/role, runs prepared SQL, sets a feedback message and redirects.
5. A view file reads the saved state and renders escaped HTML. `app.js` handles progressive enhancements.

Use the browser's Network panel to follow a POST → redirect → GET. Refreshing a result page should not repeat a form submission.

## Suggested module mapping — reconcile with the tutor-approved names

| Module | Relevant files/tables | CRUD |
|---|---|---|
| User/account | account.php, actions.php; users, addresses | register/create customer, retrieve, edit profile/customer, delete eligible customer/address |
| Homepage content | admin.php, admin-actions.php, home.php; banners, settings | add, list, edit, delete banner |
| Product/category | catalog.php, admin files; categories, products | add, list/detail, edit, delete |
| Variant/inventory | admin files, catalog.php; variants, product_images | add, list, edit stock/colour/image, delete |
| Shopping bag | shop.php, actions.php; cart_items | add, retrieve, update quantity, remove |
| Order | shop.php, actions.php, admin files; orders, order_items | create via checkout, view, progress/cancel, delete cancelled demo order |

Wishlist and reviews are related supporting functions. Shared UI, architecture and integration are team tasks. Candidate unique functions include banner preview, low-stock alerts, and cancellation with stock restoration; tutor approval, fair complexity and actual individual contribution must be checked.

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

1. Run the installer on your own computer. Locate the resulting tables in phpMyAdmin.
2. Change one CSS colour and one heading, save and refresh.
3. Trace product retrieval from the SQL query to the card HTML.
4. Add a product and one variant through the admin UI; inspect both rows in phpMyAdmin.
5. Trace one complete CRUD module and record normal/error cases.
6. Trace checkout and cancellation; compare stock before and after.
7. Each member implements and explains agreed changes in their two modules, keeps a truthful contribution log, and creates their own diagrams/report evidence.

The project is not a replacement for your individual understanding, original work, approved feature scope, or assignment documentation. The specification does not state an AI policy; follow any tutor guidance provided separately.
