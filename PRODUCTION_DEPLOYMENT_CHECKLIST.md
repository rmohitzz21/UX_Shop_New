# UX Pacific Shop - Production Deployment Checklist

Use this checklist before switching the shop to live customers and live Razorpay payments.

## 1. Code and Database

- [ ] Back up production files and database.
- [ ] Deploy the latest code.
- [ ] Run migrations in order, including `migrations/004_unified_cart_digital_resources_and_fulfillment.sql` if not already applied.
- [ ] Verify these tables exist: `cart`, `orders`, `order_items`, `digital_resources`, `digital_downloads`, `inventory_reservations`.
- [ ] Verify these columns exist:
  - [ ] `cart.item_type`, `cart.product_id`, `cart.bundle_id`, `cart.selected_format`
  - [ ] `order_items.item_type`, `order_items.bundle_id`, `order_items.selected_format`
  - [ ] `orders.payment_status`, `orders.paid_at`
  - [ ] `digital_downloads.resource_id`
- [ ] Ensure `.migration-complete` exists if runtime compatibility DDL is still present.
- [ ] Schedule `scripts/release-expired-reservations.php` every 10-15 minutes for awaiting-payment cleanup.

## 2. Production Environment

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_URL=https://your-production-domain`
- [ ] `ENABLE_TEST_PAYMENT=false`
- [ ] Database credentials set only in production environment or protected `.env`.
- [ ] `.env` is outside web root or denied by server config.
- [ ] Rotate any credential that has ever been committed, shared, or exposed.

## 3. Razorpay

```ini
RAZORPAY_KEY_ID=rzp_live_...
RAZORPAY_KEY_SECRET=...
RAZORPAY_WEBHOOK_SECRET=...
```

- [ ] Configure Razorpay webhook URL: `{APP_URL}/api/payment/webhook.php`.
- [ ] Enable payment captured and payment failed webhook events.
- [ ] Confirm webhook secret is set in production.
- [ ] Confirm unsigned webhooks are rejected in production.
- [ ] Run one Razorpay test-mode payment before live switch.
- [ ] Close the browser after payment and verify webhook still fulfills the order.
- [ ] Verify browser verify + webhook racing does not duplicate entitlements or emails.
- [ ] After live switch, run one controlled low-value live payment.

## 4. SMTP and Emails

```ini
SMTP_HOST=mail.uxpacific.com
SMTP_PORT=465
SMTP_ENCRYPTION=ssl
SMTP_USER=support@uxpacific.com
SMTP_PASS=<server only>
SMTP_FROM_EMAIL=support@uxpacific.com
SMTP_FROM_NAME=UX Pacific
SUPPORT_EMAIL=support@uxpacific.com
ADMIN_EMAIL=<admin inbox>
```

- [ ] Send a test welcome email.
- [ ] Send a forgot-password email and verify reset link uses production `APP_URL`.
- [ ] Verify paid digital order confirmation links to My Orders / downloads page, not direct file URLs.
- [ ] Verify free digital confirmation email.
- [ ] Verify COD physical order confirmation.
- [ ] Verify payment failed email.
- [ ] Verify shipped, delivered, and cancelled admin-status emails.
- [ ] Verify duplicate paid fulfillment sends only one confirmation and invoice.
- [ ] Verify SMTP failures are logged without exposing SMTP password.

## 5. Private Digital Storage

Recommended production driver: Cloudflare R2.

```ini
DIGITAL_STORAGE_DRIVER=r2
DIGITAL_STORAGE_ENDPOINT=https://<account_id>.r2.cloudflarestorage.com
DIGITAL_STORAGE_REGION=auto
DIGITAL_STORAGE_BUCKET=<private-bucket>
DIGITAL_STORAGE_ACCESS_KEY=<server only>
DIGITAL_STORAGE_SECRET_KEY=<server only>
DIGITAL_STORAGE_PRESIGN_TTL_SECONDS=300
```

- [ ] Bucket has no public read policy.
- [ ] Direct bucket/object URL returns 403 without a signed URL.
- [ ] Admin file upload stores a private `storage_key`, not a public URL.
- [ ] Customer download creates a short-lived signed URL.
- [ ] Signed URL expires after the configured TTL.
- [ ] No `storage_key`, cloud URL, or direct external resource URL appears in customer HTML, JS, API JSON, or email.
- [ ] Canva/Figma links are stored in `digital_resources.external_url` and opened only through `api/download/access.php`.
- [ ] Admin understands external platform limitation: after a user opens a Canva/Figma link, they may be able to reshare it.

For local-only testing:

```ini
DIGITAL_STORAGE_DRIVER=local
DIGITAL_STORAGE_LOCAL_DIR=C:/path/outside/htdocs/ux-private
```

- [ ] Local private directory is outside web root, or protected with deny rules.

## 6. Catalog and Admin

- [ ] Remove or deactivate test products, bundles, and QA records.
- [ ] Upload real resources for every paid digital product and bundle.
- [ ] Verify every paid product/bundle has at least one active resource.
- [ ] Verify free digital products also use private resources and entitlements.
- [ ] Create a product with PDF + Canva link and test customer access.
- [ ] Create a bundle with ZIP + PDF + Figma link + instructions and test customer access.
- [ ] Reject non-HTTPS external resource links.
- [ ] Reject unsafe uploads: PHP, EXE, SH, oversized, wrong MIME.
- [ ] Confirm admin logout remains POST + CSRF.

## 7. Customer Smoke Tests

- [ ] Guest adds digital product, then signs in; cart merges.
- [ ] Guest adds bundle, then signs in; cart keeps `item_type=bundle`.
- [ ] Product ID and bundle ID collision does not mix items.
- [ ] Digital-only checkout hides address and COD.
- [ ] Bundle-only checkout hides address and COD.
- [ ] Physical-only checkout requires address and allows COD.
- [ ] Mixed physical + digital checkout requires address and rejects COD.
- [ ] Free digital checkout skips payment and unlocks downloads.
- [ ] Paid digital checkout with Razorpay unlocks downloads.
- [ ] Awaiting-payment order shows Pay Now.
- [ ] Payment failed order does not show downloads.
- [ ] Paid order shows download/open/instruction actions.
- [ ] Wrong signed-in user cannot use another user's token.
- [ ] Anonymous user cannot use a token.
- [ ] Expired entitlement is blocked.
- [ ] Download limit is enforced.
- [ ] Reviews are blocked until the order is paid/processing/shipped/delivered.

## 8. Browser and UX Checks

- [ ] Homepage
- [ ] Shop
- [ ] Product detail
- [ ] Bundles
- [ ] Cart
- [ ] Checkout
- [ ] Order confirmation
- [ ] My Orders / downloads
- [ ] Sign in
- [ ] Sign up
- [ ] Forgot/reset password
- [ ] Account
- [ ] Contact
- [ ] Admin dashboard
- [ ] Branded 404 page
- [ ] Desktop viewport
- [ ] Tablet viewport
- [ ] Mobile viewport
- [ ] Keyboard-only navigation
- [ ] No console errors in critical flows

## 9. Security Checks

- [ ] `.env` blocked from browser access.
- [ ] `apiold/` blocked or removed.
- [ ] Diagnostic scripts removed or blocked.
- [ ] `storage/private/` blocked from browser access.
- [ ] No Razorpay secret in JS.
- [ ] No SMTP password in PHP/JS.
- [ ] No cloud secret in PHP/JS outside environment reads.
- [ ] CSRF required on cart, checkout, review, contact, and admin mutations.
- [ ] Admin endpoints require admin session.
- [ ] Redirects use exact allowlist paths only.
- [ ] `APP_DEBUG=false` in production.
- [ ] Security headers still allow Razorpay checkout and required CDNs only.

## 10. Go-Live Decision

Go live only when all of these pass:

- [ ] One Razorpay test-mode order fulfilled by browser verify.
- [ ] One Razorpay test-mode order fulfilled by webhook with browser closed.
- [ ] One live low-value Razorpay order completed after live key switch.
- [ ] Paid product private file download works through signed URL.
- [ ] Paid bundle multi-resource access works.
- [ ] Free digital private file download works.
- [ ] Anonymous and wrong-user token access are blocked.
- [ ] SMTP sends welcome, order confirmation, invoice, and failed-payment emails.
- [ ] No paid files exist under public web directories.
- [ ] Private cloud bucket denies public access.
- [ ] Backup and rollback plan is ready.

