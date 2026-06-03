
## Role

You are a senior PHP 8.2 + MySQL/MariaDB ecommerce engineer, QA engineer, payment integration reviewer, security reviewer, and UI/UX engineer.

Work inside this repository:

```text
C:\xampp\htdocs\Shop\UX_SHOP\UX_Shop_New
```

The local storefront is:

```text
http://localhost/Shop/UX_SHOP/UX_Shop_New/
```

Your job is to implement the fixes, not only write another audit. Work in small, verifiable phases. Preserve working behavior and existing data. Do not remove unrelated user changes.

## Primary Business Goal

Make UX Pacific Shop launch-ready for a mixed catalog:

- Single digital products
- Digital bundles
- Physical products
- Products that offer a digital or physical format
- Mixed carts containing products and bundles
- Secure Razorpay online payment
- Physical-only Cash on Delivery (COD)
- Post-payment access to multiple digital resources
- Cloud-hosted files plus protected Canva, Figma, and external resource links
- Admin management of product and bundle resources

There must be one consistent customer journey:

```text
Browse -> Add product or bundle -> Cart -> One shared checkout ->
Verified payment -> Server fulfillment -> Order confirmation ->
My Orders / My Downloads -> Download files or open protected resource links
```

## Important Current State

Before changing anything, inspect the current code. Do not blindly apply findings from older audit reports because several fixes have already landed.

### Already present and should be preserved

- Razorpay checkout modal creation is wired in `script.js`.
- Razorpay server-side signature verification exists in `api/payment/razorpay-verify.php`.
- Razorpay amount is fetched from the gateway and checked server-side.
- `api/admin/order/update_status.php` already calls `DigitalDownloadService::generateDownloadsForOrder()` for fulfillable statuses.
- `api/payment/test-pay.php` exists and is guarded for non-production usage.
- Download tokens require a signed-in user and ownership checks.
- Customer order queries are scoped to the signed-in user.
- CSRF protection exists on mutation endpoints.
- Admin logout currently sends POST + CSRF and should remain that way.
- Security headers exist in `includes/config.php`.

### Known launch blockers still visible in current code

1. Bundles are represented as proxy products in the cart.
   - `api/_bootstrap.php::apiEnsureProduct()` may return a real product when a bundle ID collides with a product ID.
   - Otherwise it creates a hidden proxy row in `products`.
   - `api/cart/list.php` returns only product fields and loses `item_type='bundle'`.
   - `api/cart/add.php`, `update.php`, and `remove.php` do not have a real bundle-aware cart model.
   - This creates different behavior for product-only, bundle-only, guest, signed-in, and mixed-cart checkout paths.

2. COD is still enabled for digital carts.
   - `script.js::loadCheckoutPage()` resets COD to enabled.
   - `api/order/create.php` accepts COD without rejecting digital items.

3. Checkout trusts client-selected `available_type`.
   - `api/order/create.php` uses the submitted cart item value for physical/digital decisions.
   - The server must validate the customer selection against the catalog row.

4. Fulfillment is not centralized.
   - Browser verification generates downloads.
   - Test-pay generates downloads.
   - Admin paid status generates downloads.
   - Razorpay `payment.captured` webhook marks an order paid but does not generate downloads or send the paid-order email.
   - A customer who pays and closes the browser may get a paid order without usable downloads.

5. Current download storage is too limited.
   - `includes/DigitalDownloadService.php` supports only one `digital_file_path` per product or bundle.
   - It streams local files.
   - A real bundle may contain a ZIP, PDFs, Canva links, Figma links, instructions, and other resources.

6. Review eligibility includes unpaid orders.
   - `includes/reviews.php::reviewEligibleOrderStatuses()` includes `pending`.

7. Order confirmation trusts `localStorage.lastOrder`.
   - `order-confirmation.php` renders success messaging from local storage.
   - Messaging can say downloads are ready even when they are not.
   - It promises email download links although the email implementation is inconsistent.

8. Razorpay dismissed payment has no real retry action.
   - `script.js` tells the user to retry from My Orders.
   - `orders.php` has no `Pay now` action for `awaiting_payment`.

9. Stock lifecycle is incomplete for online payment.
   - Physical stock is decremented when an online draft order is created.
   - Abandoned, failed, or expired Razorpay orders can retain stock without a release path.

10. Production cleanup is incomplete.
    - `.env` is inside the web root.
    - `apiold/` is a public legacy API surface.
    - `.htaccess` does not wire `404.php`.
    - Runtime schema mutation is called from `includes/config.php` through `marketplaceEnsureSchema($conn)`.
    - Several diagnostic files remain in the web root.
    - Mojibake text is visible in multiple files, such as corrupted rupee signs, arrows, and punctuation.

## Read Before Editing

Inspect these files first:

```text
checkout.php
cart.php
orders.php
order-confirmation.php
signin.php
signup.php
script.js
includes/config.php
includes/helpers.php
includes/marketplace.php
includes/reviews.php
includes/DigitalDownloadService.php
includes/OrderPaymentService.php
includes/RazorpayClient.php
includes/EmailService.php
api/_bootstrap.php
api/cart/add.php
api/cart/list.php
api/cart/update.php
api/cart/remove.php
api/cart/merge.php
api/order/create.php
api/order/get.php
api/order/downloads.php
api/payment/razorpay-create-order.php
api/payment/razorpay-verify.php
api/payment/webhook.php
api/payment/test-pay.php
api/download/file.php
api/admin/product/save.php
api/admin/bundles/save.php
api/admin/order/update_status.php
admin/admin-dashboard.php
admin/admin-dashboard.js
migrations/001_admin_improvements.sql
migrations/002_production_schema_alignment.sql
migrations/003_digital_products.sql
.env.example
.htaccess
robots.txt
sitemap.xml
```

Also inspect:

```text
WEBSITE_FULL_AUDIT_AND_FIXING_PLAN.md
apiold/
check-users.php
create-test-user.php
test-login.php
test-session.php
seed-bundles.php
```

## Non-Negotiable Rules

1. Do not hardcode Razorpay keys, SMTP credentials, cloud credentials, or database credentials.
2. Keep Razorpay test mode working locally. Production must switch to live keys only through environment variables.
3. Never enable `ENABLE_TEST_PAYMENT=true` in production.
4. Never expose private cloud object URLs directly.
5. Never trust product name, price, item type, physical/digital type, total, tax, or shipping values from JavaScript.
6. Never unlock digital resources based only on frontend success or query parameters.
7. Preserve all existing orders and users.
8. Create additive, reversible SQL migrations. Do not manually mutate the live database without documenting the migration.
9. Use prepared statements for all database values.
10. Keep all mutations protected by authentication, authorization, and CSRF where appropriate.
11. Do not use runtime `CREATE TABLE` or `ALTER TABLE` calls as the final production schema strategy.
12. Do not treat bundles as proxy products.
13. Do not finish until PHP lint, JavaScript checks, migration review, and browser regression testing are complete.

---

# Phase 1: Create a Safe Database Migration

Create:

```text
migrations/004_unified_cart_digital_resources_and_fulfillment.sql
```

The exact SQL must match the inspected schema and the deployment database engine. The current dump mentions MariaDB 10.4.32, so verify syntax compatibility. Do not assume MySQL-only syntax.

## 1.1 Normalize cart items

Replace the proxy-product bundle pattern with a real typed cart model. The final cart table must support:

```text
user_id
item_type: product | bundle
product_id: nullable
bundle_id: nullable
selected_format: digital | physical
quantity
size
created_at
```

Requirements:

- Existing cart rows migrate as `item_type='product'`.
- A product row uses `product_id` and leaves `bundle_id` null.
- A bundle row uses `bundle_id` and leaves `product_id` null.
- Add an appropriate uniqueness strategy for user + item identity + selected format + size.
- Add useful indexes.
- Avoid duplicate migrated rows.
- If CHECK constraints are not reliable on the deployed MariaDB version, enforce the XOR rule in PHP.

## 1.2 Snapshot format on order items

Add a field such as:

```text
selected_format ENUM('digital','physical')
```

to `order_items`, or use another clearly named equivalent. This must record what the customer actually purchased so later fulfillment does not depend on mutable catalog values.

## 1.3 Add payment state separately from order state

The current `orders.status` mixes payment and fulfillment concepts. Add:

```text
payment_status: pending | paid | failed | refunded
paid_at
```

Keep the existing order status for lifecycle compatibility, but make payment authorization explicit. Backfill carefully based on existing statuses and payment fields. Document all assumptions.

Recommended policy:

```text
Online draft: order.status = awaiting_payment, payment_status = pending
Online verified: order.status = paid, payment_status = paid
Physical COD: order.status = pending, payment_status = pending
Failed payment: order.status = failed, payment_status = failed
```

## 1.4 Add normalized digital resources

Create a resource table that allows many resources for one product or bundle:

```sql
CREATE TABLE digital_resources (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NULL,
  bundle_id INT NULL,
  title VARCHAR(255) NOT NULL,
  resource_type VARCHAR(40) NOT NULL,
  delivery_mode VARCHAR(30) NOT NULL,
  storage_provider VARCHAR(30) NOT NULL DEFAULT 'local',
  storage_key VARCHAR(700) NULL,
  external_url TEXT NULL,
  instructions TEXT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

Allowed resource concepts:

```text
resource_type:
  file
  zip
  pdf
  canva
  figma
  external_link
  instructions

delivery_mode:
  download
  open_link
  instructions

storage_provider:
  local
  r2
  s3
  external
```

Rules:

- Exactly one of `product_id` or `bundle_id` must be populated.
- `storage_key` is used for a private local/cloud file.
- `external_url` is used for Canva, Figma, or another approved HTTPS resource link.
- Add owner and active/sort indexes.
- Validate URLs server-side.
- Do not print external resource URLs in anonymous product/catalog APIs.

## 1.5 Upgrade digital download entitlements

Current `digital_downloads` allows only one download per `order_item_id`. Upgrade it so each purchased resource can receive an entitlement.

Requirements:

- Add `resource_id`.
- Replace `UNIQUE(order_item_id)` with `UNIQUE(order_item_id, resource_id)`.
- Keep old rows working during transition.
- Keep token uniqueness.
- Preserve expiry and download-limit support for downloadable files.
- For external links and instructions, record access safely without pretending they are downloadable files.

## 1.6 Add inventory reservation support

Choose and implement a safe strategy for physical online orders. Recommended:

```text
inventory_reservations
  id
  order_id
  order_item_id
  item_type
  item_id
  quantity
  expires_at
  released_at
  consumed_at
```

Requirements:

- Do not permanently decrement physical stock merely because Razorpay modal opened.
- Reserve stock atomically when order is created.
- Consume reservation when payment is verified.
- Release on payment failure, cancellation, or expiry.
- Add a callable cleanup script for expired awaiting-payment orders.
- Digital resources do not need stock reservation unless there is a deliberate limited-license policy.

## 1.7 Remove schema drift

Update migration documentation and schema dumps so a new environment can be created reliably.

Do not leave production dependent on:

```php
marketplaceEnsureSchema($conn);
```

Keep a temporary guarded compatibility path only if necessary during rollout, then document its removal.

---

# Phase 2: Replace Proxy Bundles With One Cart Model

Refactor:

```text
api/_bootstrap.php
api/cart/add.php
api/cart/list.php
api/cart/update.php
api/cart/remove.php
api/cart/merge.php
script.js
cart.php
```

## Required behavior

1. Remove the proxy-product bundle creation logic from `apiEnsureProduct()`.
2. Resolve catalog rows by both `item_type` and ID.
3. Return typed cart rows from `api/cart/list.php`.
4. Preserve `item_type`, `product_id`, `bundle_id`, and `selected_format` during:
   - add
   - update quantity
   - remove
   - guest local-storage cart
   - login cart merge
   - reorder
   - Buy Now
5. Validate catalog availability server-side.
6. For bundles, derive digital behavior from the server. Do not trust the browser.
7. For products:
   - `available_type='digital'`: only allow `selected_format='digital'`.
   - `available_type='physical'`: only allow `selected_format='physical'`.
   - `available_type='both'`: require or safely default a valid explicit customer choice.
8. Fix any active product/bundle ID collision issue.
9. Do not create new `products` rows as a cart side effect.

## Acceptance tests

Test as guest and signed-in customer:

```text
Add one digital product
Add one physical product
Add one bundle
Add product and bundle together
Update each quantity
Remove each item independently
Sign in after building guest cart
Reorder a previous product order
Reorder a previous bundle order
```

---

# Phase 3: Unify Checkout Layout and Business Rules

Refactor:

```text
checkout.php
script.js
api/order/create.php
```

There must be one shared checkout layout and one order-creation contract for:

```text
product-only cart
bundle-only cart
product + bundle cart
digital-only cart
physical-only cart
mixed digital + physical cart
```

## Required UI behavior

- Use one order summary component for all cart combinations.
- Show a `Product` or `Bundle` badge for clarity without changing the checkout structure.
- Digital-only cart:
  - Hide shipping address fields.
  - Show contact email and phone.
  - Show a clear digital-delivery notice.
  - Disable and hide COD.
  - Automatically select online payment if COD had been selected.
- Physical-only cart:
  - Require shipping address.
  - Allow COD and Razorpay.
- Mixed digital + physical cart:
  - Require shipping address.
  - Apply physical shipping.
  - Disable COD so digital fulfillment is not left unpaid.
- The same layout must remain usable on mobile.

## Required API behavior

`api/order/create.php` must:

- Load product/bundle records server-side by typed identity.
- Derive prices server-side.
- Derive allowed selected formats server-side.
- Reject invalid format tampering.
- Derive shipping server-side.
- Derive tax server-side.
- Reject COD if any order line contains digital delivery.
- Reject inactive/deleted items.
- Validate physical stock atomically.
- Snapshot item type, selected format, name, image, and price into `order_items`.
- Never use submitted totals as authoritative.

## COD policy

Use this launch policy:

```text
COD allowed only when every purchased item is physical.
Any order containing a digital resource must use online payment.
```

Implement both frontend disabling and backend rejection.

---

# Phase 4: Centralize Payment and Fulfillment

Create a service such as:

```text
includes/OrderFulfillmentService.php
```

Refactor:

```text
includes/OrderPaymentService.php
includes/DigitalDownloadService.php
api/payment/razorpay-verify.php
api/payment/webhook.php
api/payment/test-pay.php
api/admin/order/update_status.php
```

## Required fulfillment entry point

Create an idempotent method such as:

```php
OrderFulfillmentService::fulfillPaidOrder($orderId, $conn);
```

It must:

1. Re-read the order in a transaction.
2. Confirm `payment_status='paid'` or an explicitly approved admin transition.
3. Generate missing entitlements for all active purchased digital resources.
4. Consume physical inventory reservations.
5. Avoid duplicate entitlements and duplicate stock changes.
6. Send the correct email once, or queue it once.
7. Log failures without falsely returning customer success if fulfillment is incomplete.

Call it after:

```text
Successful browser Razorpay verification
Successful Razorpay payment.captured webhook
Successful local test payment
Admin transition to paid
```

## Webhook hardening

`api/payment/webhook.php` must:

- Generate entitlements and email after a successful captured payment.
- Be idempotent when browser verification and webhook race.
- Reject unverified webhooks in production.
- Prefer failing closed when webhook secret is missing.
- Require an explicit development-only override if unsigned local webhook testing is necessary.
- Validate event payload fields before database use.

## Razorpay retry

Add a safe retry flow:

- Show `Pay now` in `orders.php` for customer-owned `awaiting_payment` orders.
- Reuse the existing internal order.
- Reuse or recreate the Razorpay order safely based on its gateway state.
- Do not create duplicate internal orders.
- Do not allow retry for paid, cancelled, delivered, or refunded orders.
- Use the existing session and CSRF token.

## Razorpay environment switch

Keep source code identical across environments.

Local/staging:

```text
APP_ENV=local
RAZORPAY_KEY_ID=<test key id>
RAZORPAY_KEY_SECRET=<test secret>
RAZORPAY_WEBHOOK_SECRET=<test webhook secret>
ENABLE_TEST_PAYMENT=true
```

Production:

```text
APP_ENV=production
APP_DEBUG=false
RAZORPAY_KEY_ID=<live key id>
RAZORPAY_KEY_SECRET=<live secret>
RAZORPAY_WEBHOOK_SECRET=<live webhook secret>
ENABLE_TEST_PAYMENT=false
```

Never print secrets in logs, API responses, documentation output, or commits.

---

# Phase 5: Add Multi-Resource Digital Delivery With Cloud Support

Create a storage abstraction such as:

```text
includes/DigitalStorageService.php
```

It must support:

```text
local private storage for development
S3-compatible private storage for production
Cloudflare R2 or AWS S3 through environment configuration
```

## Environment variables

Add placeholders to `.env.example`:

```text
DIGITAL_STORAGE_DRIVER=local
DIGITAL_STORAGE_LOCAL_DIR=
DIGITAL_STORAGE_ENDPOINT=
DIGITAL_STORAGE_REGION=auto
DIGITAL_STORAGE_BUCKET=
DIGITAL_STORAGE_ACCESS_KEY=
DIGITAL_STORAGE_SECRET_KEY=
DIGITAL_STORAGE_PRESIGN_TTL_SECONDS=300
```

Do not commit real values.

## Download flow

For a cloud file:

```text
Customer clicks Download
-> PHP validates session
-> PHP validates entitlement token
-> PHP verifies order belongs to customer
-> PHP verifies payment_status=paid
-> PHP verifies expiry and download limit
-> PHP increments access count atomically
-> PHP generates short-lived signed cloud URL
-> PHP redirects customer to signed URL
```

For a Canva, Figma, or approved external URL:

```text
Customer clicks Open Resource
-> PHP validates session
-> PHP validates entitlement token
-> PHP verifies ownership and paid order
-> PHP records access
-> PHP redirects to approved HTTPS external URL
```

For instructions:

```text
Customer opens My Downloads
-> Authenticated API returns the instruction text
```

## Required endpoints

Implement or refactor endpoints similar to:

```text
api/order/resources.php
api/download/access.php
api/admin/resources/list.php
api/admin/resources/save.php
api/admin/resources/delete.php
api/admin/resources/upload.php
```

You may preserve `api/download/file.php` as a backward-compatible wrapper.

## Admin UI requirements

Add a `Digital Resources` section to both:

```text
Admin -> Products -> Add/Edit Product
Admin -> Bundles -> Add/Edit Bundle
```

Admin must be able to add multiple resource rows:

```text
Title
Resource type
Delivery mode
Upload private file OR paste protected external link
Instructions
Download limit
Expiry days
Sort order
Active toggle
```

Examples:

```text
Brand Guide PDF        | pdf   | upload file
Complete Design Pack   | zip   | upload file
Editable Canva Kit     | canva | paste HTTPS template link
Figma Source File      | figma | paste HTTPS link
Getting Started        | instructions | enter text
```

Rules:

- Cover/gallery media remains separate from paid downloadable resources.
- Private files must not be stored in public `/img`, `/assets`, `/downloads`, or `/uploads`.
- Validate upload type, MIME type, extension, size, and generated storage key.
- Validate external URLs as HTTPS.
- Do not expose protected resource URLs in public APIs or HTML.
- Canva/Figma links can be shared after a customer opens them, so explain this limitation in admin help text.

## Customer UI requirements

Use one customer destination:

```text
orders.php
```

Optionally add:

```text
my-downloads.php
```

Show resources grouped by purchased product or bundle:

```text
Premium UX Bundle
  [Download ZIP]
  [Download PDF]
  [Open Canva Template]
  [Open Figma File]
  Getting Started instructions
```

Do not display `File pending` for a resource that admin never configured. Instead show a controlled support message only if fulfillment exists but storage configuration is incomplete.

---

# Phase 6: Fix Order Confirmation, Emails, Reviews, and Redirects

## 6.1 Server-authenticated order confirmation

Refactor:

```text
order-confirmation.php
script.js
api/order/get.php
```

Requirements:

- Redirect after purchase to:

```text
order-confirmation.php?order_id=<internal order id>
```

- Require login.
- Load the order from server by `order_id AND user_id`.
- Treat local storage only as optional cosmetic cache, never as proof.
- Show state-specific messaging:

```text
awaiting_payment -> Payment not completed. Show Pay now.
paid with resources -> Payment confirmed. Your downloads are ready.
paid without configured resources -> Payment confirmed. Files are being prepared. Contact support if delayed.
pending COD physical-only -> Order placed. Payment due on delivery.
failed -> Payment failed. Show retry when allowed.
```

- Do not show a success timeline for an unverified online payment.
- Do not show review buttons for unpaid orders.

## 6.2 Correct email templates

Refactor:

```text
includes/EmailService.php
```

Requirements:

- Use `APP_URL`, never hardcoded production links.
- Separate digital paid-order messaging from physical order messaging.
- Digital email should link to authenticated My Orders / My Downloads.
- Do not put permanent cloud URLs in emails.
- Do not claim a direct download link exists if the email only links to the account.
- Use consistent support email from environment settings.
- Prevent duplicate paid-order email sends with an idempotent marker such as `confirmation_email_sent_at`.

## 6.3 Fix review eligibility

Refactor:

```text
includes/reviews.php
order-confirmation.php
orders.php
api/reviews/submit.php
```

Allowed review statuses:

```text
paid
processing
shipped
delivered
```

Exclude:

```text
pending
awaiting_payment
failed
cancelled
refunded
```

Enforce this server-side even if the UI is manipulated.

## 6.4 Harden redirect handling

Refactor sign-in and signup redirects in:

```text
script.js
signin.php
signup.php
```

Requirements:

- Add one reusable client helper and, where needed, a server helper.
- Allow only exact internal paths from an explicit allowlist.
- Reject protocol-relative, absolute, encoded, traversal, and substring tricks.
- Do not use `includes()` as the allowlist check.

---

# Phase 7: Production Cleanup and UI/UX Corrections

## Security and deployment

1. Add `ErrorDocument 404 /Shop/UX_SHOP/UX_Shop_New/404.php` for local Apache, and document the production-root equivalent.
2. Block or remove `apiold/`.
3. Remove diagnostic scripts from production deployment or keep them blocked for Apache and document equivalent Nginx rules.
4. Move `.env` outside the public web root for production, or document a deployment strategy that guarantees denial at the web server.
5. Rotate any credential that has ever been committed or shared.
6. Set `APP_ENV=local` in local example configuration and `APP_ENV=production` on live server.
7. Remove runtime DDL after migrations are complete.
8. Validate freebie redirects against an approved HTTPS policy. Avoid an open redirect.
9. Review CSP after final CDN usage and keep Razorpay domains working.

## Content and navigation

1. Remove test catalog content such as gibberish bundle names before launch.
2. Fix or remove dead footer links.
3. Add an About page if navigation or footer promises one.
4. Use one support email domain consistently.
5. Keep the copyright year consistent.
6. Wire the branded 404 page.

## Auth placeholders

For features not actually implemented:

```text
Google sign-in
Google signup
Remember me
OTP modal
```

Either implement them correctly or remove/hide the controls before launch. Do not show a success toast for an unavailable feature.

## Accessibility

Fix at minimum:

- Programmatic labels for search inputs.
- Descriptive alt text where images communicate meaning.
- Decorative images use empty alt intentionally.
- OTP dialog removed or hidden correctly from assistive technology when not active.
- Keyboard access and focus return for dialogs, cart drawer, mobile menu, review dialog, and payment method controls.
- Visible focus styles.
- Touch target sizing on mobile.

## Encoding cleanup

Repair mojibake carefully in user-facing PHP, CSS, JS, and Markdown:

```text
â‚¹ -> ₹
Â© -> ©
â€” -> —
â†’ -> →
corrupted bullets and emoji sequences -> intended text or plain ASCII
```

Use UTF-8 consistently. Do not perform a blind replacement that corrupts valid content.

## SEO and performance

- Add canonical URLs.
- Add Open Graph and Twitter metadata.
- Add product URLs to sitemap generation.
- Return a real 404 for invalid product IDs where appropriate.
- Compress oversized product images.
- Audit the large `style.css` and split only where it improves maintainability without causing regressions.
- Ensure scripts use sensible `defer` behavior where safe.
- Run Lighthouse after fixes.

---

# Phase 8: Test Matrix

Do not claim the project is ready until these tests are run and results are documented.

## 8.1 Static verification

Run:

```text
PHP lint across every PHP file
JavaScript syntax check for script.js and admin/admin-dashboard.js
Review migration SQL against the actual MariaDB/MySQL target
Search for hardcoded secrets
Search for public private-file paths
Search for runtime CREATE TABLE / ALTER TABLE
Search for mojibake
```

## 8.2 Customer cart and checkout tests

Test:

| Scenario | Expected |
| --- | --- |
| Guest adds digital product | Persists locally |
| Guest adds bundle | Persists locally with `item_type=bundle` |
| Guest signs in | Cart merges without proxy products |
| Signed-in user adds bundle whose ID equals a product ID | Correct bundle is added |
| Digital-only product cart | One checkout layout, no address, no COD |
| Bundle-only cart | Same checkout layout, no address, no COD |
| Digital product + bundle | Same checkout layout, no COD |
| Physical-only cart | Address required, COD allowed |
| Physical + digital mixed cart | Address required, COD rejected |
| Remove bundle | Product with same numeric ID remains untouched |
| Reorder bundle | Correct bundle returns to cart |

## 8.3 Razorpay test-mode tests

Use Razorpay test keys only:

| Scenario | Expected |
| --- | --- |
| Successful payment | Order becomes paid and resources unlock |
| Browser closes after payment but before verify callback | Webhook fulfills order |
| Verify callback and webhook arrive together | No duplicate entitlements or emails |
| Modal dismissed | Order remains awaiting payment and Pay now works |
| Payment failed webhook | Payment failed state and stock reservation released |
| Amount tampering | Rejected server-side |
| Order ownership tampering | Rejected |
| Invalid signature | Rejected |
| Missing webhook secret in production mode | Webhook rejected |
| Test-pay endpoint in production mode | 404 / unavailable |

## 8.4 Digital resources tests

Test product and bundle resources separately:

| Resource | Expected |
| --- | --- |
| Private PDF | Short-lived signed download or private local stream |
| Private ZIP | Short-lived signed download or private local stream |
| Canva link | Paid customer can open through protected endpoint |
| Figma link | Paid customer can open through protected endpoint |
| Instructions | Paid customer can read |
| Anonymous user | Blocked |
| Different signed-in user | Blocked |
| Unpaid order owner | Blocked |
| Expired entitlement | Blocked |
| Download limit reached | Blocked |
| Disabled resource | Not shown or accessible |

## 8.5 Admin tests

Test:

```text
Admin login and logout
Create digital product with PDF + Canva link
Edit product resources
Create bundle with ZIP + PDF + Figma link + instructions
Edit bundle resources
Delete or disable one resource
Mark an eligible order paid and verify entitlement generation
Prevent CSRF-less mutation
Reject unsafe upload
Reject non-HTTPS external resource link
```

## 8.6 Browser and responsive checks

Verify in browser:

```text
Homepage
Shop
Product popup and detail
Bundles popup
Cart
Checkout
Razorpay test flow
Order confirmation
My Orders / Downloads
Signin
Signup
Forgot/reset password
Account
Contact
Admin dashboard
404 page
```

Check:

```text
Desktop
Tablet
Mobile
Chrome
Firefox
Edge
Keyboard-only navigation
Console errors
Network failures
Empty states
Loading states
```

---

# Required Output From Claude

Work phase by phase. After each phase, report:

```text
Files changed
Migration changes
Behavior fixed
Tests run
Test results
Remaining risks
```

At the end, create:

```text
PRODUCTION_FIX_IMPLEMENTATION_REPORT.md
PRODUCTION_DEPLOYMENT_CHECKLIST.md
```

The final deployment checklist must include:

```text
Run all migrations in order
Set production APP_ENV and APP_DEBUG
Install live Razorpay keys
Configure Razorpay webhook URL and secret
Disable test-pay
Configure private R2/S3 storage
Move or protect .env
Block apiold and diagnostic files
Upload real resources
Remove test catalog records
Verify SMTP
Run full Razorpay test-mode regression before live switch
Run one controlled live payment after live switch
Verify paid product download
Verify paid bundle multi-resource access
Verify webhook fulfillment with browser closed
Verify backups and rollback plan
```

## Completion Definition

The task is complete only when:

1. Products and bundles use one typed cart and checkout flow.
2. No bundle proxy products are created.
3. COD is impossible for any cart containing digital delivery.
4. Razorpay browser verify, webhook, local test-pay, and admin paid transitions all use one idempotent fulfillment service.
5. A product or bundle can contain multiple protected resources.
6. Paid users can download private cloud files and open protected Canva/Figma links.
7. Unpaid users and other users cannot access resources.
8. Awaiting-payment users can retry payment safely.
9. Confirmation UI and email copy reflect real server state.
10. Reviews are blocked until paid.
11. Production cleanup and launch checklist are complete.

