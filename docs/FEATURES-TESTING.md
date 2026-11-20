# Stages 1–7 validation record

Date: 20 November 2026, Malaysia. Environment: Windows, PHP 8.5.12, MariaDB 10.11.16, headless Microsoft Edge via Playwright. The application targets PHP 8.2+/MariaDB 10.4+; this record does not claim execution on every supported version.

## Automated rules

Run from the project root:

```powershell
& 'C:\xampp\php\php.exe' tests/promotions.php
& 'C:\xampp\php\php.exe' tests/features.php
& 'C:\xampp\php\php.exe' tests/recommendation-service.php
```

- 32 promotion checks passed: normalisation, decimals, invalid code/date fields, rate bounds and date status boundaries.
- 39 feature rule checks passed: integer cents/rounding, checkout fingerprint changes, address validation, shared sizes, preference refinements, budget/size constraints, complementary roles and product ID validation.
- 13 recommendation service checks passed: complementary candidates, hard budget, refreshing changed prices/unavailable variants, model preference cleaning, valid response decoding and malformed-response rejection. Catalogue calls use an in-memory fixture and do not call an API.

## Integration

79 HTTP/database checks passed on an independent test copy using disposable databases on port 13379, not the user's XAMPP database on port 3306. Coverage:

- Legacy order retention, payment backfill, repeated schema migration and fresh schema execution.
- Admin pages, customer access denial, mutation denial and invalid CSRF.
- Phone/address persistence, single default, address edit/checkout ownership.
- Matching colour and size on the same stocked variant, complete size filter and product search.
- Persistent cart selection and complements, empty selection, removal confirmation.
- Invalid/valid promotions, exact discount/shipping, changed prices, declined payment.
- Selected-only order items, removal and stock deduction; payment persistence; duplicate checkout idempotency.
- Order ownership, foreign cancellation, once-only stock restoration and safe cancelled-order deletion.
- Purchased product/variant deletion protection; historical promotion snapshots retained after deletion.
- Valid/invalid order transitions, admin/time history, completed-order cancellation/deletion rejection.
- Purchase-qualified reviews, prefilled edits, average rating, hide/restore and moderator audit.
- Regression: customer deletion/recreation retains hidden moderation status.
- Customer history/search, order search, per-variant stock threshold, inactive variants.
- Independent chat, preference refinement, no match, reset, and inactive accounts including existing sessions.

An additional configured-service failure test used an unreachable loopback HTTPS endpoint with a dummy credential. It confirmed explicit rule fallback and no credential exposure. No real model account was contacted or billed.

## Browser

16 checks passed: desktop/mobile cart, checkout, account, assistant and product layouts; cart checkbox JavaScript selection/totals; admin Promotions/Inventory/Customers/Reviews/Orders rendering. No horizontal viewport overflow on the checked storefront pages and no JavaScript page errors. Cart/mobile and inventory screenshots were inspected.

## Limits

The live provider/key/model remains unconfigured and unverified. This is not a real-payment integration, external penetration test, accessibility certification, concurrency load test or the team's formal UAT. The existing XAMPP site/database was not upgraded automatically. Its missing `.installed` marker was restored separately and the login endpoint verified.
