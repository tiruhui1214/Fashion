# Validation record

Executed on 6 November 2026 (Asia/Kuala_Lumpur), using PHP 8.4.27, MariaDB 11.8.6 and Chromium on Linux. The source targets PHP 8.2+ and MariaDB 10.4+; an actual Windows/XAMPP run is still required on your computer.

These are development checks, not your assignment test plan, UAT or evidence of individual contributions. No Windows/Apache execution or fresh cloud-task restoration is claimed.

## HTTP integration checks

44 checks passed.

- Passed: Installer renders before initialisation
- Passed: Installer creates MariaDB schema, demo collection and admin
- Passed: Installer locks after successful initialisation
- Passed: Admin authentication succeeds
- Passed: Admin page: dashboard
- Passed: Admin page: content
- Passed: Admin page: categories
- Passed: Admin page: products
- Passed: Admin page: inventory
- Passed: Admin page: orders
- Passed: Admin page: customers
- Passed: Admin page: settings
- Passed: Admin page: guide
- Passed: Public page: home
- Passed: Public page: catalog
- Passed: Public page: catalog&department=Women
- Passed: Public page: product&id=1
- Passed: Public page: about
- Passed: Public page: help&topic=size
- Passed: Public page: help&topic=delivery
- Passed: Public page: help&topic=privacy
- Passed: Public page: help&topic=contact
- Passed: Public page: login
- Passed: Public page: register
- Passed: Customer registration and login
- Passed: Customer admin permission denied
- Passed: Profile update persists
- Passed: Address creation persists
- Passed: Wishlist creation
- Passed: Wishlist retrieval
- Passed: Cart addition uses server price
- Passed: Cart quantity update recalculates subtotal
- Passed: Cart rejects insufficient stock
- Passed: Declined payment displays error and retains cart
- Passed: Checkout places simulated order
- Passed: Repeated checkout returns original order
- Passed: Checkout clears cart
- Passed: Cancellation restores stock
- Passed: Repeat cancellation cannot restore stock twice
- Passed: Reviews require a completed purchase
- Passed: Brand settings save
- Passed: Contact page reads saved settings
- Passed: Order ownership enforced
- Passed: Invalid CSRF token rejects mutation

## Chromium browser checks

15 checks passed.

- Passed: Home renders one active hero
- Passed: Slideshow next button changes banner
- Passed: Colour swatch changes actual product image
- Passed: Colour selection updates selectable sizes
- Passed: Admin banner text preview updates immediately
- Passed: Admin banner upload preview uses selected local file
- Passed: Admin JPG/PNG image upload persists banner
- Passed: Server rejects SVG uploads
- Passed: Validation error preserves ordinary entered values
- Passed: No mobile page overflow: home
- Passed: No mobile page overflow: index.php?page=catalog
- Passed: No mobile page overflow: index.php?page=product&id=1
- Passed: No mobile page overflow: index.php?page=admin&section=content
- Passed: Mobile navigation opens
- Passed: No JavaScript runtime errors during tested interactions

## Database and lifecycle checks

18 checks passed.

- Passed: Database verifies stock restored to initial 12
- Passed: Database verifies one order for repeated checkout
- Passed: Product create
- Passed: Product update
- Passed: Product delete
- Passed: Checkout decrements stock exactly once
- Passed: Order progression: Processing
- Passed: Order progression: Shipped
- Passed: Order progression: Completed
- Passed: Completed purchaser creates review
- Passed: Review update stays unique
- Passed: Review delete
- Passed: Completed order delete rejected
- Passed: Cancelled order deletion does not restore stock twice
- Passed: Admin customer create
- Passed: Admin customer update
- Passed: Admin customer delete
- Passed: SQL-like search remains literal text

## Scope of evidence

Total: 77 successful assertions in the recorded runs, plus PHP syntax checks on all PHP source files and JavaScript syntax validation. Browser checks used 1440×1000 desktop and 390×844 mobile viewports.

Screenshots: `home-desktop.png`, `home-mobile.png`, `product-desktop.png`, `admin-desktop.png`. They show illustrative placeholder assets; they are not Figma prototype files.

The repeated-checkout and cancellation database assertions verified exact row counts and stock numbers, rather than relying only on success messages. Upload checks used a real PNG and rejected an SVG.

## Still perform on Windows

- Install via XAMPP with your own administrator account.
- Check Apache honours the bundled .htaccess restrictions. The PHP development server used here does not apply .htaccess rules.
- Use your actual product photos, verify every colour/size combination and every homepage banner.
- Run each tutor-approved module process with normal, invalid and boundary inputs, including edit/delete forms. Not every individual CRUD path is covered by the automated runs.
- Export/restore your real demonstration database and upload directory on the second computer.
- Test simultaneous shoppers, larger catalogues, low disk space and server restarts if included in your test scope.
- Capture your own before/after screenshots, expected/actual results, UAT and individual walkthrough.
