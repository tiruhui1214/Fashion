# FORME — Online Fashion Store Management

A local-first teaching project built with **PHP 8.2+**, **MariaDB 10.4+**, HTML, CSS and vanilla JavaScript. Intended for Windows + XAMPP + VS Code. No npm, Composer, cloud account, payment API or Internet connection is needed to run the installed store.

## First run on Windows

1. Install XAMPP with PHP 8.2 and MariaDB from https://www.apachefriends.org/ . Install VS Code from https://code.visualstudio.com/ .
2. Extract the **Fashion** folder into `C:\xampp\htdocs\Fashion` (adjust if XAMPP is installed elsewhere).
3. Start **Apache** and **MySQL** in XAMPP Control Panel. XAMPP's button says MySQL; its bundled database is MariaDB.
4. Open `http://localhost/Fashion/setup.php` in the browser.
5. Choose an administrator name, email and password. The installer creates the `forme` database and a demonstration collection.
6. Open `http://localhost/Fashion/`, sign in, and choose **Account → Manage store**.

If your database uses different credentials or a different port, copy `config.example.php` to `config.local.php` and edit the local copy first. Do not share local database passwords.

Read **[the illustrated-by-steps Chinese Windows guide](docs/WINDOWS-SETUP.md)** before moving computers. Never run the fresh installer over an existing database you want to keep.

## Implemented areas

- Customer registration, sign in/out, profile, saved-address CRUD; administrator customer CRUD with order-history protection.
- Hero/campaign banner CRUD, upload preview, text colour/alignment/link/order/visibility settings, optional mobile banner images.
- Women/Men/Kids category CRUD, product CRUD, homepage featured edit, catalogue search/filter/sort.
- Colour/size variant CRUD, per-variant stock and image, extra colour-specific gallery photos, low-stock overview.
- Persistent signed-in shopping bag CRUD and wishlist toggle.
- Transactional simulated checkout, integer-cent totals, server prices and stock checks, idempotency key, customer order history and ownership checks.
- Controlled order progression and cancellation with stock restoration. Only cancelled demonstration orders can be deleted by an administrator.
- Purchase-verified reviews: one editable review per customer/product after a completed order, with delete support.
- Brand/contact settings and optional logo upload.

## Project layout

```text
Fashion/
  index.php                 Router and shared storefront layout
  setup.php                 One-time local installer; locks using .installed
  config.example.php        Default LOCAL XAMPP connection settings
  app/
    bootstrap.php           PDO, sessions, CSRF, validation, upload helpers
    actions.php             Customer POST operations, checkout transaction
    admin-actions.php       Administrator POST operations
    home.php                Homepage
    catalog.php             Catalogue and product detail
    shop.php                Wishlist, bag, checkout, orders
    account.php             Auth, account, information pages
    admin.php               Dashboard and admin forms
    seed.php                Eight illustrative sample products, banners
  database/schema.sql       Database and tables (no private user data)
  assets/                   CSS, JavaScript, original SVG placeholders
  uploads/                  Uploaded JPG/PNG/WebP; copy when migrating
  docs/                     Setup, walkthrough, tests and screenshots
```

## Architecture and learning

Presentation is in the view files/CSS/JS. Application operations and business rules are in the action files. PDO prepared statements access MariaDB. This is a small three-tier teaching structure, not a framework. Read `docs/CODE-WALKTHROUGH.md` and modify one feature at a time. Record each member's actual code and report contributions.

## Important scope boundaries

This is a **working starter**, not a complete assignment submission. Your team must create its own Figma prototype, diagrams, report, process-level tests, UAT, and individual walkthrough. Tutor approval of module boundaries and unique features remains authoritative.

Sample images are original **SVG illustrations**, not product photographs; every sample garment uses a placeholder shirt drawing even when its name is trousers or a dress. Upload your properly sourced photography before presenting the visual design. No external fonts or photo services are used; typography uses local system fonts. Policies, size measurements and shipping rules are demonstration content.

No real payment, email delivery/password recovery, refund processing, tax calculation, image cropping, anonymous bag, newsletter service, or drag-and-drop page builder is included. Checkout requires sign-in. Homepage has a fixed section structure; banners can be added/reordered/hidden within their placement, categories can have their images edited, and the featured edit shows the first four flagged products. It does not rearrange every homepage section. Cart is a full page, not a drawer. The application is intended for local demonstration; public deployment needs additional operational/security work.

## Backup

Back up **both** the project folder (including `uploads`) **and** a phpMyAdmin SQL export of the `forme` database. Git intentionally ignores uploaded content, local configuration and the installer lock. A Git clone alone does not restore those files or database rows.
