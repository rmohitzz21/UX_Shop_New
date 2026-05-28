# Website Full Audit and Fixing Plan

Project: UX Pacific custom PHP + MySQL e-commerce website  
Audit date: 2026-05-28  
Workspace: `C:\xampp\htdocs\Shop\UX_SHOP\UX_Shop_New`

## Executive Summary

This project is a custom PHP + MySQL e-commerce shop with public product pages, cart, checkout, authentication, account/orders pages, admin dashboard, APIs, email helpers, and Razorpay backend helper endpoints.

The codebase has many good foundations:

- Most active APIs use prepared SQL statements.
- Session cookies are configured with `httponly`, `samesite=Lax`, and conditional `secure`.
- CSRF tokens are used on most state-changing customer and admin API calls.
- Admin APIs are centralized through `api/admin/_admin.php`.
- Current local database already contains many evolved tables and columns needed by the newer code.
- PHP syntax lint passed for 167 PHP files using `C:\xampp\php\php.exe -l`.

However, the site is not production-ready yet. The most serious issue is the checkout/payment flow: `checkout.php` presents Card and UPI as Razorpay-backed payment methods, but `script.js` never calls `api/payment/razorpay-create-order.php`, never opens Razorpay checkout, and never calls `api/payment/razorpay-verify.php`. Card/UPI orders can therefore be created and shown as successful without collecting payment. There are also bundle cart failures, order status inconsistencies, public legacy/debug files, schema drift between SQL files and the real DB, heavy schema mutation on normal page requests, and several UI/UX consistency issues.

## Evidence Checked

| Evidence | Status |
| --- | --- |
| Folder and file structure | Checked |
| Active DB schema in local `uxmerchandise` database | Checked with MySQL CLI |
| SQL files and migrations | Checked: `uxmerchandise.sql`, `marketplace_schema.sql`, `migrations/001_admin_improvements.sql` |
| Public PHP pages | Checked from source |
| Active API files under `api/` | Checked from source |
| Legacy API files under `apiold/` | Identified as risky public duplicates |
| Admin dashboard and APIs | Checked from source |
| Razorpay helper code | Checked from source |
| Frontend checkout/cart/auth JavaScript | Checked from source |
| PHP lint | Passed for 167 PHP files with XAMPP PHP |

---

## 1. Project Structure Summary

| Area | Files/Folders | Purpose | Status | Issues |
| ---- | ------------- | ------- | ------ | ------ |
| Root public pages | `index.php`, `shopAll.php`, `product.php`, `bundles.php`, `freebies.php`, `cart.php`, `checkout.php`, `orders.php`, `account.php`, `signin.php`, `signup.php`, `forgot-password.php`, `reset-password.php`, `contact.php`, `wishlist.php`, `category.php`, `search.php`, `policies.php` | Customer storefront and account flows | Partly working | Payment, bundle cart, status filters, currency display, and stale/duplicate page logic issues |
| Admin pages | `admin/admin-login.php`, `admin/admin-dashboard.php`, `admin/admin-dashboard.js`, `admin/admin.css`, `admin/addproduct.php`, `admin/editproduct.php` | Admin login, dashboard, product/order/user/content management | Partly working | Admin logout is broken; old `addproduct.php` and `editproduct.php` appear duplicated beside the dashboard modal workflow |
| Active APIs | `api/auth`, `api/cart`, `api/order`, `api/payment`, `api/admin`, `api/address`, `api/catalog`, `api/product`, `api/contact`, `api/user`, `api/wishlist`, `api/freebies` | JSON endpoints for frontend/admin | Mixed | Good use of prepared statements and CSRF in many places, but payment APIs are unused by checkout frontend |
| Legacy APIs | `apiold/` | Older copies of auth/cart/order/payment/admin APIs | Risky | Public duplicate surface; remove or block before launch |
| Includes/core | `includes/config.php`, `includes/helpers.php`, `includes/marketplace.php`, `includes/RazorpayClient.php`, `includes/OrderPaymentService.php`, `core/Mailer.php` | App boot, DB, helper functions, schema guards, payment/email services | Important | `marketplaceEnsureSchema()` mutates schema and seeds data on every request; move to migrations |
| CSS/JS | `style.css`, `script.js`, `assets/css/*.css`, `admin/admin.css`, `admin/admin-dashboard.js`, `css/auth-premium.css` | Frontend/admin styling and behavior | Works but heavy | `style.css` is about 284 KB and `script.js` about 178 KB; many unrelated features in one global file |
| Images/uploads | `img/`, `img/products/`, `assets/` | Logos, products, UI assets | Works | One product image is about 7 MB; optimize and enforce image compression |
| Database files | `uxmerchandise.sql`, `marketplace_schema.sql`, `migrations/001_admin_improvements.sql`, `seed-bundles.php` | Schema dump, supplemental schema, migrations, seed tool | Inconsistent | Main SQL dump is stale versus current code/current DB |
| Environment/config | `.env`, `.env.example`, `.htaccess` | Runtime configuration and web protection | Risky | `.env` exists in web root and includes commented live DB credential values; move secrets outside public root |
| Logs | `logs/app_errors.log` | PHP app logs | Risky | Should not be public; `.htaccess` blocks logs under Apache but still better outside web root |
| Prior docs | `API_PRODUCTION_AUDIT_AND_FIX_REPORT.md`, `API_TESTING_CHECKLIST.md`, `prompt.md`, `README.md` | Previous audit/test notes | Useful | Keep docs, but do not treat previous audit as proof of current production readiness |
| Debug/test scripts | `check-users.php`, `create-test-user.php`, `test-login.php`, `test-session.php`, `seed-bundles.php` | Diagnostics/seeding | Risky | `.htaccess` blocks several, but remove from production or move to CLI-only tools |

### Important Files

- `includes/config.php`: Loads `.env`, starts session, sets security headers, connects MySQL, creates CSRF token, loads helpers, calls `marketplaceEnsureSchema($conn)`.
- `includes/marketplace.php`: Creates/updates many tables/columns and seeds products/bundles.
- `script.js`: Global frontend controller for CSRF, cart, auth, checkout, address handling, product popups, wishlist/search.
- `api/order/create.php`: Creates orders and order items, calculates totals from DB prices, decrements stock, clears cart items.
- `api/payment/razorpay-create-order.php`: Creates Razorpay order for an existing internal order.
- `api/payment/razorpay-verify.php`: Verifies Razorpay signature, fetches gateway payment amount, and marks internal order paid.
- `includes/OrderPaymentService.php`: Idempotent Razorpay capture logic.
- `admin/admin-dashboard.js`: Admin UI controller for products, bundles, categories, orders, users, reviews, messages, freebies.

### Files That Look Unused, Duplicated, or Legacy

| File/Folder | Reason |
| --- | --- |
| `apiold/` | Older duplicate API tree. It includes auth/cart/order/payment/admin files and should not remain publicly reachable. |
| `admin/addproduct.php`, `admin/editproduct.php` | Older standalone product screens while current dashboard has product modals and uses `api/admin/product/save.php`. Confirm usage; likely consolidate. |
| `includes/mail.php` | Empty file. Remove if unused. |
| `api/auth/login-process.php` | Form-post login flow beside JSON `api/auth/login.php`; not referenced by current `signin.php` form. |
| `shopAll.php` root wrapper | Only requires `includes/shopAll.php`; acceptable but unusual. |
| `check-users.php`, `create-test-user.php`, `test-login.php`, `test-session.php` | Debug scripts. Blocked by `.htaccess`, but should be deleted or moved out of web root. |
| `seed-bundles.php` | Seeder exposed in root. Blocked by `.htaccess`, but should be CLI-only. |

### Files That Should Not Be Public

- `.env`
- `logs/app_errors.log`
- `uxmerchandise.sql`
- `marketplace_schema.sql`
- `migrations/`
- `apiold/`
- Debug/test scripts in root
- Any local audit docs that reveal internal structure if deployed publicly

`.htaccess` blocks many sensitive file types and directories under Apache. Do not rely on this alone: move secrets, logs, migrations, SQL dumps, and debug tools outside the public document root.

---

## 2. Page-by-Page Audit

| Page | Purpose | Working Status | UI/UX Score /10 | Code Quality /10 | Issues Found | Priority |
| ---- | ------- | -------------: | --------------: | ---------------: | ------------ | -------- |
| `index.php` | Homepage/featured marketplace | Mostly working | 7 | 7 | Depends on schema auto-seed; likely heavy; mojibake characters in output | Medium |
| `shopAll.php` / `includes/shopAll.php` | Product listing with filters | Partly working | 7 | 7 | Server pagination plus client-only filters/sort; invalid category falls back to all products; currency mojibake; only products, not bundles | Medium |
| `product.php` | Product detail | Partly working | 7 | 6 | Invalid product redirects silently; commercial license price is frontend-only and ignored by backend; related product SQL is partly interpolated; random review count | High |
| `bundles.php` | Bundle listing and featured slider | Broken for cart/checkout | 7 | 6 | Logged-in bundle add-to-cart sends IDs like `bundle_1`, but cart API expects numeric product ID plus `item_type=bundle`; bundle checkout fails | Critical |
| `freebies.php` | Free resource listing | Mostly working | 7 | 6 | Creates `freebies` table on every page request; download redirects allow any `https://` or root-relative URL configured by admin | Medium |
| `cart.php` | Cart view | Partly working | 7 | 6 | Shows `$` in UI while backend uses INR; depends on localStorage/server cart sync; bundle items fail when merged/checked out | High |
| `checkout.php` | Checkout form and payment selection | Broken for online payments | 6 | 5 | Card/UPI shown as Razorpay but no Razorpay modal/API flow is called; saved address UI is commented; `$` currency; order confirmation happens without payment | Critical |
| `order-confirmation.php` | Confirmation after checkout | Misleading | 6 | 5 | Uses `localStorage.lastOrder`; can show success for unpaid Card/UPI orders; no server verification of order ownership/status | Critical |
| `orders.php` | User order history | Partly working | 8 | 7 | Protected by session and API; status filters expect lowercase while DB/API may return `Pending`; no order detail page/invoice | Medium |
| `account.php` | User profile | Mostly working | 7 | 7 | Profile update works; no password change/delete UI despite APIs; order stats status comparison uses lowercase only | Medium |
| `signin.php` | User login | Mostly working | 8 | 7 | Remember me UI exists but no token logic; Google button is placeholder unless implemented elsewhere | Medium |
| `signup.php` | User signup | Mostly working | 8 | 7 | One password field only; hidden confirm password mirrors same value; no auto-login after signup | Low |
| `forgot-password.php` | Request reset link | Mostly working | 8 | 7 | Requires email delivery config; no visible note if SMTP fails except generic response | Medium |
| `reset-password.php` | Reset password | Mostly working | 6 | 7 | Token verification endpoint does not require CSRF, acceptable read-like validation; page uses older auth styling | Low |
| `contact.php` | Contact form | Mostly working | 6 | 7 | Form lacks phone field even API accepts phone; basic layout; session-only rate limiting | Medium |
| `wishlist.php` | Wishlist | Partly working | 6 | 6 | Requires login; likely okay for products/bundles, but frontend wishlist integration needs regression testing | Medium |
| `category.php` | Category browser | Partly working | 6 | 5 | Duplicate category queries; one query ignores `c.is_active`/`p.is_active`; selected category uses `cat` but shop links sometimes use `category` | Medium |
| `search.php` | Search results | Partly working | 6 | 6 | Does not filter `is_active` or stock; can expose inactive/unavailable products | High |
| `policies.php` | Legal/policy pages | Mostly static | 6 | 7 | Very long static page; mojibake symbols | Low |
| `404.php`, `500.php` | Error pages | Present | 6 | 7 | Need server integration test; not confirmed as configured for all errors | Low |

### Detailed Page Notes

#### `index.php`

Works as the main storefront and uses products/bundles from the database. It relies on `includes/config.php`, which triggers schema checks and seed behavior. This is convenient in development but risky and slow in production.

Fixes:

- Remove runtime schema/seed mutation from normal page loads.
- Verify product cards use consistent currency formatting.
- Replace mojibake characters caused by encoding issues.

#### `shopAll.php` and `includes/shopAll.php`

The root `shopAll.php` just includes `includes/shopAll.php`. The page uses prepared statements for category filters and paginated product listing. Empty states exist. The filter sidebar and sort controls are client-side only for the currently loaded page, so users may think they are filtering the whole catalog when they are filtering only current pagination results.

Fixes:

- Make sort/filter server-side through query params or use `api/catalog/list.php`.
- Show a clear invalid category state instead of silently falling back to all products.
- Standardize INR formatting.

#### `product.php`

Fetches an active product by numeric ID. If no ID is provided it loads the first active product. If ID is invalid, it redirects to `shopAll.php`. Product details, gallery, options, and related products are rendered.

Issues:

- Commercial license price is calculated in JavaScript but backend order creation always refetches base product price from DB, so commercial pricing is not charged.
- Related products use interpolated SQL for category after `$conn->real_escape_string`; safer to use prepared statements fully.
- Review count uses `rand(50, 500)`, which is fake and unstable.
- Invalid product should show 404 or a useful empty state, not silent redirect.

#### `bundles.php`

Renders featured and regular bundles. Display mostly works, but cart flow is broken. Buttons call `addToCart("bundle_1", ...)` and do not send `item_type: "bundle"` to `api/cart/add.php`. `apiEnsureProduct()` expects a numeric product ID or a numeric bundle ID with `item_type=bundle`; `bundle_1` fails.

Fixes:

- Update frontend add-to-cart calls for bundles to send numeric `id`, `item_type: "bundle"`, and `available_type: "digital"`.
- Update `addToCart()` to accept item type separately from available type.
- Update cart, merge, checkout, and order create payloads to preserve bundle identity.

#### `cart.php`

Cart UI exists with digital/physical sections and checkout button auth checks. For logged-in users it fetches `api/cart/list.php`; for guests it uses localStorage.

Issues:

- Currency uses `$` in multiple places while product pages show INR.
- Bundle localStorage entries cannot be checked out correctly.
- Logged-in cart table stores only `product_id`, so bundle support is implemented through proxy products, which is fragile.

#### `checkout.php`

Protected by server-side session and redirects guests to signin. It has shipping/contact fields, payment method radios, order summary, and trust blocks.

Critical issues:

- Card and UPI are selected by default and described as Razorpay, but `script.js` never opens Razorpay checkout.
- Card/UPI path simply calls `api/order/create.php`, then clears cart and redirects to `order-confirmation.php`.
- `api/order/create.php` treats `card` and `upi` as non-Razorpay and creates `Pending` orders.
- Saved address UI is commented out; `savedAddressId` and `saveAddress` are sent by JS but ignored by backend.
- UI displays `$` amounts.

#### `orders.php`

Server-side auth guard is good. The API returns only orders for the current user. UI has skeletons, filters, stats, and empty state.

Issues:

- Status filters compare exact lowercase values (`pending`, `delivered`) but order API may return `Pending`, `Processing`, etc. Filters/stats can be wrong.
- Reorder link points to `checkout.php?reorder=...`, but no backend/frontend reorder implementation was found.

#### `account.php`

Server-side auth guard is good. It loads current user and order stats, and updates profile through `api/user/update_profile.php`.

Issues:

- Stats exclude lowercase `cancelled`/`failed`, but DB/admin may use title-case statuses.
- No UI for password update or account deletion though APIs exist.

#### `signin.php` / `signup.php`

Both pages use CSRF meta tokens and JSON APIs. Login uses `password_verify`, session regeneration, blocked-user check, and CSRF rotation.

Issues:

- "Remember me" does not create a persistent token.
- Google sign-in/sign-up buttons appear in UI, but no real OAuth backend was found.
- Signup does not auto-login user after success.

#### `forgot-password.php` / `reset-password.php`

Password reset APIs use hashed reset tokens, expiry, used-at marking, and generic responses. This is good.

Issues:

- SMTP failure only logs a reset link; this is okay for dev but production needs monitored email delivery.
- Reset page uses older styling than new auth pages.

#### `contact.php`

Uses `api/contact/send.php` with CSRF, validation, DB insert, session-based rate limiting, and non-fatal email notification.

Issues:

- Rate limit is session-only, so it can be bypassed with new sessions.
- UI does not include phone field while API and DB support it.

---

## 3. User Flow Audit

### Flow A: New User Signup

| Step | Expected Behavior | Actual Code Behavior | Issue | Fix Required |
| ---- | ----------------- | -------------------- | ----- | ------------ |
| Open signup | CSRF token available | `signup.php` sets meta token | OK | None |
| Submit form | Validate full name, email, password, terms | JS submits to `api/auth/signup.php`; API requires firstName/email/password | Mostly OK | Confirm terms is validated server-side if legally required |
| Duplicate email | Reject duplicate | API checks `users.email` | OK | Add DB unique constraint is already present in current DB |
| Password | Hash securely | Uses `password_hash()` | OK | None |
| Session after signup | Optional auto-login | API returns `user_id`, does not log in | UX gap | Decide whether signup should auto-login |
| Email | Welcome email | `sendWelcomeEmail()` non-fatal | OK | Monitor SMTP failures |

Working status: mostly working.  
Critical risks: none for core signup.  
Recommended priority: medium.

### Flow B: Login

| Step | Expected Behavior | Actual Code Behavior | Issue | Fix Required |
| ---- | ----------------- | -------------------- | ----- | ------------ |
| Submit login | Validate CSRF and credentials | `api/auth/login.php` validates CSRF, uses `password_verify` | OK | None |
| Blocked user | Reject blocked accounts | Checks `is_blocked` | OK | None |
| Session | Regenerate session ID | Uses `session_regenerate_id(true)` | OK | None |
| Remember me | Persist login when checked | UI checkbox exists; no implementation found | Incomplete | Implement secure remember-token flow or remove UI |
| Logout | Destroy session | Customer logout in `script.js` uses POST + CSRF | Mostly OK | Regression test |
| Admin logout | Destroy admin session | `admin/admin-dashboard.js` calls `fetch('../api/auth/logout.php')` as GET; endpoint requires POST | Broken | Send POST with CSRF, then redirect after success |

Working status: customer login mostly working; admin logout broken.  
Critical risks: admin session may not be destroyed.  
Recommended priority: high.

### Flow C: Browse Products

| Step | Expected Behavior | Actual Code Behavior | Issue | Fix Required |
| ---- | ----------------- | -------------------- | ----- | ------------ |
| Product listing | Show active products | `shopAll.php` filters `is_active = 1` and stock/digital | OK | None |
| Search | Show active products only | `search.php` does not filter `is_active` or stock | Risk | Add active/availability filters |
| Images | Fallback on broken images | Many pages use `onerror` fallback | OK | Optimize large images |
| Empty state | Helpful empty state | Present in shop/search/freebies | Mostly OK | Improve invalid category handling |
| Bundles | List active bundles | `bundles.php` loads active bundles | Display OK | Fix cart/checkout flow |

Working status: browsing works, search needs filtering.  
Recommended priority: medium/high.

### Flow D: Product Detail

| Step | Expected Behavior | Actual Code Behavior | Issue | Fix Required |
| ---- | ----------------- | -------------------- | ----- | ------------ |
| Product ID | Validate ID | Invalid ID redirects to shop | UX issue | Show 404 or product not found state |
| Details | Show DB fields | Uses `SELECT * FROM products WHERE id=? AND is_active=1` | OK | None |
| Options | Physical/digital/license options | UI changes price for commercial license | Backend ignores commercial price | Add server-side license/cart item pricing |
| Add to cart | Save selected options | Product size/license goes into `size`; type sent separately | Partly OK | Make option model explicit |
| Related products | Show related | Works but uses interpolated query for category fallback | Code quality | Use prepared statements |

Working status: usable for basic products, pricing options incomplete.  
Recommended priority: high.

### Flow E: Cart

| Step | Expected Behavior | Actual Code Behavior | Issue | Fix Required |
| ---- | ----------------- | -------------------- | ----- | ------------ |
| Guest add | Store item locally | LocalStorage cart works for products | OK | Validate bundle path |
| Logged-in add | Store in DB | `api/cart/add.php` requires login, CSRF, server product lookup | Good for products | Bundle payload broken |
| Duplicate product | Increase quantity | Quantity merged by user/product/size/type | OK | Add stock cap validation |
| Update quantity | Update server/local | API caps quantity at 10 | OK | Check stock on update |
| Remove item | Remove item | API removes by user/product/size/type | OK | Bundle support needs redesign |
| Cart total | Accurate total | Frontend uses cart prices; checkout API recalculates DB prices | Mixed | Always show server-calculated totals for logged-in checkout |

Working status: product cart mostly works; bundle cart is broken.  
Recommended priority: critical for bundles.

### Flow F: Checkout

| Step | Expected Behavior | Actual Code Behavior | Issue | Fix Required |
| ---- | ----------------- | -------------------- | ----- | ------------ |
| Access | Login required | `checkout.php` redirects guests | OK | None |
| Cart source | Server cart for logged-in user | JS uses global `cart`, fetches DB cart asynchronously | Race risk | Load server cart before enabling submit |
| Address | Manual/saved address | Manual form exists; saved address UI commented out; backend ignores `savedAddressId` and `saveAddress` | Incomplete | Implement saved address selection/save |
| Totals | Backend authoritative | `api/order/create.php` recalculates from DB | Good | Show same server totals in UI |
| Order creation | Create pending/COD or awaiting payment | API creates order and items | Partly OK | Do not clear cart/stock/email before online payment |
| Online payment | Razorpay flow | Not called from frontend | Critical broken | Implement full Razorpay frontend flow |

Working status: COD-like order creation can work; online payment is broken.  
Recommended priority: critical.

### Flow G: Payment / Razorpay / COD

| Check | Expected | Actual | Risk | Fix |
| ---- | -------- | ------ | ---- | --- |
| Backend Razorpay order | Create gateway order after internal order | `api/payment/razorpay-create-order.php` exists | Unused | Wire frontend to it |
| Checkout modal | Open Razorpay checkout | No `Razorpay(...)` call found in `script.js` | Critical | Add Razorpay checkout script and handler |
| Signature verification | Verify HMAC | `api/payment/razorpay-verify.php` does this | Good but unused | Call after payment success |
| Amount validation | Fetch gateway payment and compare amount | Implemented in `RazorpayClient.php` and `OrderPaymentService.php` | Good | Keep |
| Webhook | Verify webhook signature | Implemented, but accepts payload if secret missing | Critical if misconfigured | Fail closed in production when secret missing |
| Duplicate payment | Idempotent capture | Implemented for same payment ID | Good | Test |
| Cart clearing | Clear only after COD order or successful payment | `api/order/create.php` clears cart items immediately | Critical | Clear online cart only after verified payment |
| Stock decrement | Decrement only after paid/COD accepted | `api/order/create.php` decrements before payment | High | Reserve stock or decrement after payment capture |
| Emails | Send after order accepted/paid | Email sent immediately after order creation | High | Send COD confirmation immediately; online only after paid |
| COD | Works for non-digital | UI disables COD for digital in frontend only | Medium | Enforce COD rules server-side |

Working status: backend payment helpers are promising; live customer payment flow is not connected.  
Recommended priority: critical.

### Flow H: View Order / Order History

| Step | Expected Behavior | Actual Code Behavior | Issue | Fix Required |
| ---- | ----------------- | -------------------- | ----- | ------------ |
| Access | User sees own orders only | `orders.php` session guard; `api/order/get.php` filters by `user_id` | OK | None |
| List | Show items/status/payment | API fetches orders and items in two queries | OK | Normalize status format |
| Empty state | Show useful empty state | Present | OK | None |
| Unauthorized ID access | User cannot view other orders | No public order-detail-by-ID endpoint found; list is user-filtered | OK | If adding details endpoint, enforce ownership |
| Invoice/download | Optional | Not implemented | Incomplete | Add later if needed |

Working status: mostly working for order list.  
Recommended priority: medium.

---

## 4. API Audit

Recommended standard success format:

```json
{
  "success": true,
  "message": "Action completed successfully",
  "data": {}
}
```

Recommended standard error format:

```json
{
  "success": false,
  "message": "Human readable error message",
  "errors": {}
}
```

Current APIs use:

```json
{
  "status": "success",
  "message": "...",
  "data": {}
}
```

This is workable, but standardize one shape across all APIs before launch.

| API/File | Method | Purpose | Auth Required | Working Status | Security Risk | Issues | Priority |
| -------- | ------ | ------- | ------------- | -------------- | ------------- | ------ | -------- |
| `api/_bootstrap.php` | include | API boot helpers | N/A | Working | Low | Not an endpoint; helper detection only | Low |
| `api/auth/csrf.php` | GET | Return CSRF token | Public session | Working | Low | Good for frontend | Low |
| `api/auth/signup.php` | POST | Signup | Public + CSRF | Mostly working | Low | Does not auto-login | Low |
| `api/auth/login.php` | POST | Login | Public + CSRF | Working | Low | No remember-me support | Medium |
| `api/auth/logout.php` | POST | Logout | Session + CSRF | Customer OK; admin caller broken | Medium | Admin JS calls it as GET | High |
| `api/auth/admin-login.php` | POST | Admin login | Public + CSRF | Mostly working | Medium | Rate limit is session-only | Medium |
| `api/auth/session.php` | GET | Session info | Public | Working | Low | OK | Low |
| `api/auth/forgot-password.php` | POST | Create reset token | Public + CSRF | Working | Medium | Session-only abuse protection; email failure non-fatal | Medium |
| `api/auth/verify-reset-token.php` | POST | Validate reset token | Public | Working | Low | No CSRF acceptable because it does not mutate | Low |
| `api/auth/reset-password.php` | POST | Set new password | Public + CSRF + token | Working | Low | Good token hash/expiry/used_at | Low |
| `api/auth/login-process.php` | POST form | Legacy form login | Public + CSRF | Possibly unused | Medium | No explicit method guard; duplicate path | Low |
| `api/cart/add.php` | POST | Add item to DB cart | User + CSRF | Products work; bundles broken | Medium | Requires numeric product or proper bundle payload | Critical |
| `api/cart/list.php` | GET | List cart | User | Working | Low | Product-only cart table model | Medium |
| `api/cart/update.php` | POST | Update cart qty | User + CSRF | Working for products | Low | No stock validation on update | Medium |
| `api/cart/remove.php` | POST | Remove cart item | User + CSRF | Working for products | Low | Bundle model issue | Medium |
| `api/cart/merge.php` | POST | Merge local cart after login | User + CSRF | Products work; bundles broken | Medium | Invalid bundle IDs abort via `sendResponse` | Critical |
| `api/order/create.php` | POST | Create order | User + CSRF | Partly working | Critical | Clears cart, decrements stock, emails before online payment; card/UPI not paid | Critical |
| `api/order/get.php` | GET | Get user orders | User | Working | Low | Status casing inconsistent with UI | Medium |
| `api/payment/razorpay-create-order.php` | POST | Create Razorpay order | User + CSRF | Backend exists | Medium | Not called by checkout frontend | Critical |
| `api/payment/razorpay-verify.php` | POST | Verify payment | User + CSRF | Backend exists | Low | Not called by checkout frontend | Critical |
| `api/payment/webhook.php` | POST | Razorpay webhook | Public signature | Partly safe | Critical | Accepts unsigned payload if webhook secret missing | Critical |
| `api/address/add.php` | POST intended | Add address | User + CSRF | Working | Low | No explicit `apiRequirePost()` seen; add method guard | Medium |
| `api/address/get.php` | GET | List addresses | User | Working | Low | OK | Low |
| `api/address/update.php` | POST intended | Update address | User + CSRF | Likely working | Low | Verify method guard | Medium |
| `api/address/delete.php` | POST intended | Delete address | User + CSRF | Likely working | Low | Verify method guard | Medium |
| `api/address/set-default.php` | POST intended | Default address | User + CSRF | Likely working | Low | Verify method guard | Medium |
| `api/contact/send.php` | POST | Contact form | Public + CSRF | Working | Medium | Session-only rate limit | Medium |
| `api/catalog/list.php` | GET | Catalog list | Public | Working | Low | Good prepared filters | Low |
| `api/catalog/detail.php` | GET | Product/bundle detail | Public | Working | Low | OK | Low |
| `api/catalog/view.php` | GET | Track view | Public | Partly broken | Low | For bundles it increments `sales_count`, not `view_count` | Medium |
| `api/product/search.php` | GET | Search suggestions | Public | Likely working | Low | Verify active filtering | Medium |
| `api/product/get_details.php` | POST/GET mismatch | Fetch details for IDs | Public | Works if JSON POST | Low | No method guard; name suggests GET | Low |
| `api/user/profile.php` | GET | User profile | User | Working | Low | OK | Low |
| `api/user/update_profile.php` | POST | Update profile | User + CSRF | Working | Low | OK | Low |
| `api/user/update_password.php` | POST | Change password | User + CSRF | Working | Low | UI missing | Medium |
| `api/user/delete_account.php` | POST | Delete/anonymize account | User + CSRF | Working | Medium | Session destroy after response helper still OK; test | Medium |
| `api/wishlist/add.php` | POST | Add wishlist | User + CSRF | Likely working | Low | Bundle path expects proper bundle payload | Medium |
| `api/wishlist/list.php` | GET | List wishlist | User | Working | Low | OK | Low |
| `api/wishlist/remove.php` | POST | Remove wishlist | User + CSRF | Working | Low | OK | Low |
| `api/freebies/download.php` | GET | Redirect to freebie file | Public | Working | Medium | Allows admin-configured external URLs; validate domains if needed | Medium |
| `api/admin/_admin.php` | include | Admin auth/helpers/uploads | Admin | Working | Medium | Uploads not re-encoded; no audit log | Medium |
| `api/admin/product/*.php` | GET/POST | Product CRUD | Admin | Mostly working | Medium | Create/update wrappers include `save.php`; upload validation good but no image optimization | Medium |
| `api/admin/bundles/*.php` | GET/POST | Bundle CRUD | Admin | Mostly working | Medium | Bundle item updates are separate from bundle save transaction | Medium |
| `api/admin/categories/*.php` | GET/POST | Category CRUD | Admin | Likely working | Low | Regression test | Medium |
| `api/admin/order/*.php` | GET/POST | Order admin | Admin | Partly working | High | Status updater maps `paid` to `Processing`, `failed` to `Cancelled`, losing payment states | High |
| `api/admin/user/*.php` | GET/POST | User admin | Admin | Mostly working | Medium | Cannot block self; good. Add audit log | Medium |
| `api/admin/messages/*.php` | GET/POST | Contact messages | Admin | Working | Medium | `list.php` runs ALTER TABLE on request | Medium |
| `api/admin/reviews/*.php` | GET/POST | Review moderation | Admin | Working | Medium | Hard delete; no audit log | Medium |
| `api/admin/freebies/*.php` | GET/POST | Freebie CRUD | Admin | Working | Medium | File URL validation only basic | Medium |
| `api/admin/stats/overview.php` | GET | Dashboard stats | Admin | Working | Medium | Revenue status list inconsistent with order state model | Medium |
| `apiold/**` | Mixed | Legacy duplicate APIs | Mixed | Must remove/block | High | Public old API surface | Critical |

---

## 5. Database Audit

The local database `uxmerchandise` currently has these tables:

`addresses`, `admins`, `bundle_items`, `bundles`, `cart`, `categories`, `contact_messages`, `featured_items`, `freebies`, `inventory_movements`, `order_items`, `orders`, `password_reset_tokens`, `products`, `reviews`, `user_tokens`, `users`, `wishlist`.

Important: the active local DB is more advanced than `uxmerchandise.sql`. The SQL dump still defines `orders.status` as an enum with only `Pending`, `Processing`, `Shipped`, `Delivered`, `Cancelled`, but current code and current DB use modern states like `awaiting_payment`, `paid`, and `failed`. Fresh installs from `uxmerchandise.sql` will break newer checkout/payment/admin code unless migrations are run.

### 5.1 Tables Detected

| Table | Purpose | Used In Files | Issues |
| ----- | ------- | ------------- | ------ |
| `users` | Customer/admin accounts | auth APIs, account, admin user APIs | Current DB has unique email; SQL dump contains duplicate admin emails and bad sample data |
| `user_tokens` | Token storage/revocation | reset password, delete account | No active remember-me implementation found |
| `products` | Product catalog | listing, product detail, cart, order, admin | Many columns required by code are not in old SQL dump |
| `bundles` | Bundle catalog | bundles page, catalog APIs, admin bundles | Bundle cart/order integration is broken in frontend payloads |
| `bundle_items` | Bundle product composition | bundles admin/listing | No FK shown for bundle/product in actual DB output except unique/index; add constraints if safe |
| `cart` | Logged-in user cart | cart APIs | Product-only model; bundle support uses fragile proxy product behavior |
| `orders` | Orders | order APIs, payment, admin, account/orders | Status/payment schema drift; payment states inconsistent |
| `order_items` | Order line items | order APIs/admin/orders | Current DB supports bundles; old SQL dump does not |
| `addresses` | Saved user addresses | address APIs, checkout JS | Checkout saved address UI/backend integration incomplete |
| `contact_messages` | Contact form messages | contact API, admin messages | `is_read` and `archived` are added in migrations/runtime guards |
| `password_reset_tokens` | Password reset | auth reset APIs | Good hashed-token design |
| `categories` | Catalog categories | shop/category/admin | Runtime schema creation should move to migrations |
| `wishlist` | Wishlist | wishlist APIs/page | Bundle support needs regression testing |
| `reviews` | Product/bundle reviews | catalog detail/admin reviews | No public review submit flow found |
| `featured_items` | Featured content | schema exists | Not heavily used in inspected pages |
| `inventory_movements` | Stock audit | admin product/bundle save, stats | Good idea; needs admin audit expansion |
| `admins` | Separate admin table | schema exists | Active admin login uses `users.role`, not `admins` table |
| `freebies` | Free resources | freebies page/admin/download | Table is created on page load; move to migration |

### 5.2 Missing Columns or Schema Mismatches

| Table | Column Expected in Code | Exists in Current DB? | Impact | Fix |
| ----- | ----------------------- | --------------------- | ------ | --- |
| `orders` | `status` allowing `awaiting_payment`, `paid`, `failed` | Yes as `varchar(32)` in current DB; no in old dump | Fresh installs break payment flow | Add migration to change status to `VARCHAR(32)` |
| `orders` | `payment_id`, `razorpay_order_id`, `payment_verified_at`, `payment_currency` | Yes current DB; missing old dump | Payment APIs fail on fresh DB | Add migration |
| `orders` | `status_updated_at`, `customer_note` | Yes current DB; missing old dump | Admin status/order note features fail on fresh DB | Add migration |
| `order_items` | `bundle_id`, `product_name`, `product_image`, `item_type`, nullable `product_id` | Yes current DB; missing old dump | Bundle orders and historical display fail | Add migration |
| `products` | `slug`, `sku`, `tags`, `commercial_price`, `additional_images`, `whats_included`, `file_specification`, `related_products`, `is_active`, `is_featured`, `sales_count`, `view_count`, product specs columns | Yes current DB; mostly missing old dump | Product/admin/detail pages fail on fresh DB | Add migration |
| `bundles` | `whats_included`, `file_specification`, `additional_images`, `badge_text`, `view_count` | Yes current DB; partly missing supplemental schema | Bundle pages/admin fail on fresh DB | Add migration |
| `contact_messages` | `is_read`, `archived` | Yes current DB; added by migration/runtime | Admin messages fail on fresh DB without migration | Keep in migration, remove runtime ALTER |
| `freebies` | all table columns | Yes current DB; not in SQL dump/migration | Freebies fail on fresh DB unless page creates table | Add migration |
| `admins` | table | Yes current DB; unused by login | Confusing duplicate admin model | Either use it or remove from required schema |

### 5.3 Required Database Migrations

Create a new migration file such as `migrations/002_production_schema_alignment.sql`. Adjust `IF NOT EXISTS` syntax if the production MySQL/MariaDB version does not support it.

```sql
ALTER TABLE orders
  MODIFY COLUMN status VARCHAR(32) NOT NULL DEFAULT 'pending',
  ADD COLUMN IF NOT EXISTS payment_id VARCHAR(120) NULL AFTER payment_method,
  ADD COLUMN IF NOT EXISTS razorpay_order_id VARCHAR(120) NULL AFTER payment_id,
  ADD COLUMN IF NOT EXISTS payment_verified_at DATETIME NULL AFTER razorpay_order_id,
  ADD COLUMN IF NOT EXISTS payment_currency VARCHAR(10) NULL DEFAULT 'INR' AFTER payment_verified_at,
  ADD COLUMN IF NOT EXISTS status_updated_at TIMESTAMP NULL AFTER status,
  ADD COLUMN IF NOT EXISTS customer_note TEXT NULL AFTER shipping_address;

ALTER TABLE order_items
  MODIFY COLUMN product_id INT NULL,
  ADD COLUMN IF NOT EXISTS bundle_id INT NULL AFTER product_id,
  ADD COLUMN IF NOT EXISTS product_name VARCHAR(255) NULL AFTER size,
  ADD COLUMN IF NOT EXISTS product_image VARCHAR(500) NULL AFTER product_name,
  ADD COLUMN IF NOT EXISTS item_type ENUM('product','bundle') NOT NULL DEFAULT 'product' AFTER product_image;

ALTER TABLE products
  ADD COLUMN IF NOT EXISTS slug VARCHAR(255) NULL AFTER name,
  ADD COLUMN IF NOT EXISTS sku VARCHAR(80) NULL AFTER slug,
  ADD COLUMN IF NOT EXISTS tags VARCHAR(500) NULL AFTER category,
  ADD COLUMN IF NOT EXISTS commercial_price DECIMAL(10,2) NULL AFTER price,
  ADD COLUMN IF NOT EXISTS additional_images TEXT NULL AFTER image,
  ADD COLUMN IF NOT EXISTS whats_included TEXT NULL AFTER description,
  ADD COLUMN IF NOT EXISTS file_specification TEXT NULL AFTER whats_included,
  ADD COLUMN IF NOT EXISTS related_products TEXT NULL,
  ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS is_featured TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS sales_count INT NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS view_count INT NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS high_resolution VARCHAR(80) NULL DEFAULT 'Yes',
  ADD COLUMN IF NOT EXISTS compatible_software VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS software_version VARCHAR(120) NULL,
  ADD COLUMN IF NOT EXISTS files_included VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS grid_columns VARCHAR(80) NULL,
  ADD COLUMN IF NOT EXISTS layout_type VARCHAR(120) NULL,
  ADD COLUMN IF NOT EXISTS license_type VARCHAR(80) NULL DEFAULT 'Premium';

ALTER TABLE bundles
  ADD COLUMN IF NOT EXISTS whats_included TEXT NULL AFTER description,
  ADD COLUMN IF NOT EXISTS file_specification TEXT NULL AFTER whats_included,
  ADD COLUMN IF NOT EXISTS additional_images TEXT NULL AFTER image,
  ADD COLUMN IF NOT EXISTS badge_text VARCHAR(80) NULL DEFAULT 'Best Seller' AFTER image,
  ADD COLUMN IF NOT EXISTS view_count INT NOT NULL DEFAULT 0 AFTER sales_count;

ALTER TABLE contact_messages
  ADD COLUMN IF NOT EXISTS is_read TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS archived TINYINT(1) NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS freebies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL,
  description TEXT NULL,
  category VARCHAR(100) DEFAULT 'General',
  image VARCHAR(500) DEFAULT '',
  file_url VARCHAR(500) DEFAULT '',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  download_count INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status);
CREATE INDEX IF NOT EXISTS idx_orders_created ON orders(created_at);
CREATE INDEX IF NOT EXISTS idx_order_items_bundle ON order_items(bundle_id);
CREATE INDEX IF NOT EXISTS idx_products_active_featured ON products(is_active, is_featured);
CREATE INDEX IF NOT EXISTS idx_bundles_active_featured ON bundles(is_active, is_featured);
CREATE INDEX IF NOT EXISTS idx_contact_read_archived ON contact_messages(is_read, archived);
```

Also update `uxmerchandise.sql` or replace it with a clean production schema dump after migrations. Do not keep stale seed users with invalid password hashes or duplicate emails.

---

## 6. Admin Panel Audit

| Admin Page/Feature | Purpose | Working Status | Issues | Security Risk | Priority |
| ------------------ | ------- | -------------- | ------ | ------------- | -------- |
| `admin/admin-login.php` | Admin login UI | Mostly working | Session-only rate limit; dark orb-heavy visual style | Medium | Medium |
| `api/auth/admin-login.php` | Admin login API | Mostly working | Uses `users.role`; `admins` table unused | Medium | Medium |
| `admin/admin-dashboard.php` | Admin shell | Mostly working | Very large file; mojibake; includes many tabs/modals | Medium | Medium |
| `admin/admin-dashboard.js` | Admin controller | Partly working | Logout uses GET and does not destroy session | High | High |
| Product CRUD | Create/update/list/delete/duplicate/toggle | Mostly working | Image upload no compression; hard delete if no orders | Medium | Medium |
| Bundle CRUD | Create/update/list/delete | Mostly working | Bundle item changes not in same transaction as bundle save | Medium | Medium |
| Category CRUD | Category management | Likely working | Needs regression testing | Low | Medium |
| Order management | List/details/status/delete | Partly working | Status updater maps `paid` to `Processing`, `failed` to `Cancelled`; deletes orders permanently | High | High |
| User management | List/block/save/delete | Mostly working | Needs audit log; admin model unclear | Medium | Medium |
| Messages | Contact messages | Working | Runtime ALTER in list endpoint | Medium | Medium |
| Reviews | Moderation | Working | Hard delete, no audit log | Medium | Medium |
| Freebies | Free resource CRUD | Working | External file URL governance needed | Medium | Medium |
| File upload | Product/bundle/freebie images | Mostly safe | Allows GIF; no re-encoding or dimension normalization | Medium | Medium |

Admin protection:

- Normal users cannot access `admin/admin-dashboard.php` if `$_SESSION['admin_id']` is empty.
- Admin APIs include `api/admin/_admin.php`, which calls `requireAdmin()`.
- Admin mutation APIs generally call `validateCsrf()`.
- Delete actions have frontend `confirm()` usage in JS, but deletion is often hard delete.
- No broad admin audit log exists beyond inventory movements.

Critical admin fixes:

1. Fix admin logout to POST to `api/auth/logout.php` with CSRF.
2. Normalize order statuses across admin, customer, payment, and DB.
3. Add admin audit log for destructive actions.
4. Remove runtime schema changes from admin message list.
5. Decide whether `admins` table is real or legacy.

---

## 7. UI/UX Audit

| Area | Score /10 | Issues | Recommended Fix |
| ---- | --------: | ------ | --------------- |
| Homepage | 7 | Strong product/resource direction but heavy visuals and mojibake | Clean encoding, simplify assets, keep first viewport focused |
| Product listing | 7 | Client-only filters/sort on current page, inconsistent category params | Server-side filters and consistent `category` query param |
| Product detail page | 7 | Fake/random review count; commercial license price not backend-backed | Real review counts and server-side license pricing |
| Cart | 7 | `$` currency, bundle failures, possible stale totals | INR formatting and server total refresh |
| Checkout | 6 | Online payment false success, saved address disabled, `$` currency | Full Razorpay flow, saved addresses, clearer payment states |
| Login/signup | 8 | Polished but Google/remember features incomplete | Remove placeholders or implement them |
| Account page | 7 | Profile only; no password/address management UI | Add tabs for profile, security, addresses |
| Orders page | 8 | Good skeletons/filters, but status filtering broken by casing | Normalize statuses before filtering/stats |
| Admin dashboard | 7 | Feature-rich but dense, large files, dark visual noise | Improve responsiveness, reduce decorative effects, add empty/loading states |
| Mobile experience | 6 | Needs browser QA; dense admin and checkout likely need tuning | Mobile walkthrough on all main flows |
| Visual consistency | 6 | Mixed old/new pages, mojibake, different footer implementations | Shared components and encoding cleanup |
| Accessibility | 5 | Some labels exist, but many icon buttons/controls need checks | Keyboard/focus/contrast audit |
| Conversion flow | 4 | Payment trust broken because Card/UPI do not collect payment | Fix payment before any launch |

## UI/UX Critical Fixes

### Critical

- Fix checkout so Card/UPI cannot show success until Razorpay payment is verified.
- Fix bundle add-to-cart/checkout.
- Use one currency display format: INR across product, cart, checkout, orders, emails.

### High

- Implement saved address selection and save-address behavior or remove UI/data fields.
- Normalize order statuses in all customer/admin views.
- Replace fake/random review counts.
- Show clear invalid product/category states.

### Medium

- Consolidate repeated footers and page layouts.
- Clean mojibake characters throughout PHP/CSS/JS.
- Improve mobile checkout and admin dashboard layouts.
- Add loading and disabled states during cart/order API calls.

### Low

- Add richer SEO metadata to product/category pages.
- Add invoice/download UI for orders.
- Add account password/address management pages.

---

## 8. Security Audit

| Risk | Severity | Location | Why It Matters | Fix |
| ---- | -------- | -------- | -------------- | --- |
| Online payment bypass | Critical | `checkout.php`, `script.js`, `api/order/create.php` | Card/UPI orders can be marked/order-confirmed without payment | Wire Razorpay create/open/verify flow; block success until verified |
| `.env` in public web root contains secrets and commented live DB credentials | Critical | `.env` | Misconfigured server could expose production secrets | Move `.env` outside document root; rotate exposed credentials |
| Webhook accepts unsigned payload if secret missing | Critical | `api/payment/webhook.php` | An attacker could fake captured payments when secret is unset | Fail closed in production if `RAZORPAY_WEBHOOK_SECRET` is missing |
| Legacy API tree remains public | Critical | `apiold/` | Older endpoints increase attack surface | Delete or block `apiold/` with server rules |
| Runtime schema mutations on every request | High | `includes/marketplace.php`, `freebies.php`, `api/admin/messages/list.php` | Web requests have DDL privileges and can mutate production schema | Move all DDL to migrations; use least-privilege DB user |
| Admin logout broken | High | `admin/admin-dashboard.js`, `api/auth/logout.php` | Admin sessions remain active after logout click | POST with CSRF and verify response |
| Order/cart changes happen before payment | High | `api/order/create.php` | Stock/cart/email side effects occur for unpaid orders | Split draft order from payment capture; clear/decrement only after accepted payment/COD |
| Debug scripts in root | High | `check-users.php`, `create-test-user.php`, `test-login.php`, `test-session.php`, `seed-bundles.php` | Accidental exposure could leak or mutate data | Remove from production |
| Public SQL/log files in root | High | `*.sql`, `logs/` | `.htaccess` helps only under Apache | Move outside public root |
| Session-only rate limits | Medium | contact, admin login, password reset | Easy to bypass with new sessions | Add IP/account-based rate limit table |
| File uploads not re-encoded | Medium | `api/admin/_admin.php` | Malformed image payload risk and oversized dimensions | Re-encode images, strip metadata, generate thumbnails |
| Admin destructive actions lack audit log | Medium | admin delete/status APIs | Hard to investigate mistakes or abuse | Add `admin_audit_log` table |
| Search exposes inactive products | Medium | `search.php` | Hidden products may be visible | Add `is_active = 1` and availability filters |
| Inconsistent SQL style | Low | `product.php` related products | Mostly safe but avoid interpolation | Use prepared statements everywhere |

---

## 9. Performance Audit

| Issue | Location | Impact | Fix |
| ----- | -------- | ------ | --- |
| 7 MB product image | `img/products/b8b2547065519a3d9d872e3a244a1d8c.jpg` | Slow product/listing load | Compress/resize and generate WebP thumbnails |
| Huge global CSS | `style.css` about 284 KB | Slower first paint, hard maintenance | Split by page or build/minify |
| Huge global JS | `script.js` about 178 KB | Every page loads unrelated features | Split by page modules; defer non-critical logic |
| Runtime DDL/schema checks | `includes/marketplace.php` | Slow and risky on every request | Migrations only |
| Freebies table create on page load | `freebies.php` | DDL on public page | Move to migration |
| Admin messages ALTER on list | `api/admin/messages/list.php` | DDL on dashboard load | Move to migration |
| Repeated bundle review count queries | `bundles.php` | N+1 query pattern | Join aggregate review counts |
| No server caching | catalog pages/APIs | Repeated DB queries | Cache category/catalog summaries where safe |
| External font/icon scripts on many pages | page heads | Render blocking/network overhead | Self-host or reduce font families/icons |
| Duplicate footer markup | several root pages | Maintenance/performance overhead | Use shared `includes/footer.php` consistently |

---

## 10. Production Readiness Checklist

| Item | Status | Issue | Fix |
| ---- | ------ | ----- | --- |
| PHP syntax | Passed | 167 files linted with XAMPP PHP | Keep lint in deployment checklist |
| Database schema | Not ready | SQL dump/migrations do not fully represent active DB/code | Add production schema migration and update dump |
| Payment | Not ready | Razorpay frontend not wired | Must fix before launch |
| `.env` | Not ready | In public root and contains secret values/comments | Move out, rotate secrets |
| Error display | Mostly ready | Controlled by `APP_DEBUG`; `.htaccess` disables display_errors | Ensure `APP_DEBUG=false` live |
| Logging | Partial | Logs stored under public project path | Move logs outside web root |
| Upload permissions | Unknown | `img/products` writable by PHP | Verify least-privilege permissions |
| Payment keys | Partial | Env vars used | Ensure test/live separation and webhook secret |
| Email SMTP | Partial | Custom SMTP mailer exists | Test welcome/order/reset/contact emails |
| HTTPS | Partial | HSTS header sent | Force HTTPS at web server/load balancer |
| Security headers | Partial | CSP/security headers exist | Test CSP with all assets/payment scripts |
| `robots.txt` | Missing | Not found | Add production robots policy |
| `sitemap.xml` | Missing | Not found | Generate sitemap for public SEO pages |
| 404/500 | Present | Files exist | Configure server ErrorDocument and test |
| Backup strategy | Missing | No backup process found | Add DB/file backup plan |
| Maintenance mode | Missing | No switch found | Optional but useful |
| Composer/vendor | N/A | No Composer dependencies found | Document custom Curl Razorpay approach |
| Legacy files | Not ready | `apiold/`, debug scripts present | Remove/block |

---

## 11. Critical Fix Priority

### Must Fix Before Launch

| Priority | Issue | Impact | Files Involved | Fix Summary |
| -------- | ----- | ------ | -------------- | ----------- |
| P0 | Card/UPI payment bypass | Orders can be confirmed without payment | `checkout.php`, `script.js`, `api/order/create.php`, `api/payment/*`, `includes/OrderPaymentService.php` | Implement real Razorpay frontend and verified status transition |
| P0 | Bundle cart/checkout broken | Bundles cannot reliably be bought | `bundles.php`, `script.js`, `api/cart/*`, `api/order/create.php` | Add proper `item_type=bundle` model through cart/order |
| P0 | Secrets in public root | Credential exposure risk | `.env`, deployment config | Move env/log/sql outside public root and rotate exposed credentials |
| P0 | Webhook can accept unsigned payload if secret missing | Fake payments possible | `api/payment/webhook.php` | Fail closed in production |
| P0 | Schema drift | Fresh production install breaks | `uxmerchandise.sql`, `marketplace_schema.sql`, `migrations/*` | Add schema alignment migration |
| P1 | Cart/stock/email side effects before online payment | Stock loss and false confirmations | `api/order/create.php` | Split order draft from payment confirmation |
| P1 | Admin logout broken | Admin session remains active | `admin/admin-dashboard.js`, `api/auth/logout.php` | Use POST + CSRF |
| P1 | Runtime schema mutation | Risky DDL during normal requests | `includes/marketplace.php`, `freebies.php`, `api/admin/messages/list.php` | Move all DDL to migrations |
| P1 | `apiold/` public | Attack surface | `apiold/` | Delete or block |

### Should Fix Soon

| Priority | Issue | Impact | Files Involved | Fix Summary |
| -------- | ----- | ------ | -------------- | ----------- |
| P2 | Order status inconsistency | Broken filters, stats, admin state | `orders.php`, `account.php`, `api/admin/order/update_status.php`, `api/admin/stats/overview.php` | Standardize lowercase statuses |
| P2 | Search shows inactive products | Hidden products can appear | `search.php`, `api/product/search.php` | Add active/availability filters |
| P2 | Saved addresses incomplete | Checkout friction/confusion | `checkout.php`, `script.js`, `api/address/*`, `api/order/create.php` | Implement or remove saved address UI/data |
| P2 | Currency mismatch | Low trust | `script.js`, `cart.php`, `checkout.php`, emails | Use INR everywhere |
| P2 | Large assets/global CSS/JS | Slow mobile load | `style.css`, `script.js`, `img/products/*` | Optimize/split/minify |
| P2 | Admin hard deletes/no audit | Operational risk | admin APIs | Add audit log and soft-delete where needed |

### Nice to Improve

| Priority | Issue | Impact | Files Involved | Fix Summary |
| -------- | ----- | ------ | -------------- | ----------- |
| P3 | Fake review counts | Trust issue | `product.php` | Use real `reviews` count |
| P3 | Reorder link not implemented | Broken affordance | `orders.php` | Implement reorder or remove |
| P3 | Account security UI missing | Incomplete account UX | `account.php`, `api/user/update_password.php` | Add password/change/delete screens |
| P3 | SEO files missing | Lower discoverability | root | Add `robots.txt`, `sitemap.xml` |

---

## 12. Ready-to-Use Fixing Prompts for Claude

### Fix Prompt 1: Database Schema and Migration Alignment

You are working inside this PHP + MySQL e-commerce project. Inspect the active PHP code, `uxmerchandise.sql`, `marketplace_schema.sql`, and the current migrations. Do not guess. Fix only based on actual code references.

Goal:
Create a production-safe migration that aligns a fresh database with the columns/tables used by the current code.

Files to inspect:
- `includes/marketplace.php`
- `api/order/create.php`
- `includes/OrderPaymentService.php`
- `api/admin/product/save.php`
- `api/admin/bundles/save.php`
- `api/admin/messages/list.php`
- `freebies.php`
- `uxmerchandise.sql`
- `marketplace_schema.sql`
- `migrations/001_admin_improvements.sql`

Tasks:
1. Create `migrations/002_production_schema_alignment.sql`.
2. Include missing order/payment/order item/product/bundle/contact/freebie columns.
3. Change `orders.status` to support `pending`, `awaiting_payment`, `paid`, `processing`, `shipped`, `delivered`, `failed`, `cancelled`.
4. Remove the need for runtime `CREATE TABLE` and `ALTER TABLE` calls.
5. Update the main schema dump or document the required migration order.

Do not:
- Drop existing data.
- Remove order history.
- Hardcode credentials.
- Change unrelated UI.

Expected output:
- Files changed.
- SQL migration.
- Manual DB commands to run.
- Rollback notes.

Testing checklist:
- Fresh DB can load homepage.
- Product listing loads.
- Admin dashboard loads.
- Order creation does not fail due to missing columns.

### Fix Prompt 2: User Signup/Login and Session Hardening

Goal:
Make customer and admin authentication reliable and production-safe.

Files to inspect:
- `signin.php`
- `signup.php`
- `script.js`
- `api/auth/login.php`
- `api/auth/signup.php`
- `api/auth/logout.php`
- `api/auth/admin-login.php`
- `admin/admin-login.php`
- `admin/admin-dashboard.js`

Tasks:
1. Fix admin logout to use POST with CSRF and verify session destruction.
2. Decide whether to implement or remove "Remember me".
3. Remove or implement Google auth buttons.
4. Add IP/account-based login rate limiting for admin and optionally customer login.
5. Ensure signup either auto-logs in or clearly redirects to signin.

Safety rules:
- Do not weaken password hashing.
- Do not remove CSRF checks.
- Do not expose whether an email exists during password reset.

Testing checklist:
- Signup duplicate email.
- Signup success.
- Login success/failure.
- Blocked user login.
- Customer logout.
- Admin login/logout.

### Fix Prompt 3: Cart Flow and Bundle Support

Goal:
Fix product and bundle cart behavior for guests, logged-in users, merge after login, cart page, and checkout.

Files to inspect:
- `bundles.php`
- `product.php`
- `shopAll.php`
- `script.js`
- `api/cart/add.php`
- `api/cart/list.php`
- `api/cart/merge.php`
- `api/cart/update.php`
- `api/cart/remove.php`
- `api/_bootstrap.php`
- `api/order/create.php`

Tasks:
1. Make cart items explicitly carry `item_type` (`product` or `bundle`).
2. Stop sending IDs like `bundle_1` to APIs; send numeric `id` plus `item_type`.
3. Update guest localStorage cart shape without breaking existing carts; add migration/conversion code.
4. Update DB cart schema if needed to store `bundle_id` directly instead of proxy products.
5. Ensure quantity, remove, update, merge, and checkout work for both products and bundles.

Do not:
- Trust client price/name/image for order totals.
- Break product cart behavior.

Testing checklist:
- Guest product add/update/remove.
- Guest bundle add/update/remove.
- Login with local product cart.
- Login with local bundle cart.
- Checkout mixed product and bundle cart.

### Fix Prompt 4: Checkout and Order Creation

Goal:
Make checkout safe, server-authoritative, and consistent.

Files to inspect:
- `checkout.php`
- `script.js`
- `api/order/create.php`
- `api/cart/list.php`
- `api/address/*`
- `order-confirmation.php`
- `orders.php`

Tasks:
1. Make checkout load server cart before submit for logged-in users.
2. Display backend-calculated totals or use the same calculation source.
3. Enforce COD rules server-side, especially for digital-only carts.
4. Implement saved address selection/save or remove dead fields/UI.
5. Do not clear cart, decrement stock, or send online-payment confirmation email before verified payment.
6. Show order confirmation based on server order status, not only localStorage.

Testing checklist:
- Empty cart cannot checkout.
- Digital-only checkout.
- Physical checkout requiring address.
- COD order.
- Saved address add/select.
- Server rejects tampered totals.

### Fix Prompt 5: Razorpay Payment Flow

Goal:
Connect the existing Razorpay backend helpers to the checkout frontend safely.

Files to inspect:
- `checkout.php`
- `script.js`
- `api/order/create.php`
- `api/payment/razorpay-create-order.php`
- `api/payment/razorpay-verify.php`
- `api/payment/webhook.php`
- `includes/RazorpayClient.php`
- `includes/OrderPaymentService.php`

Tasks:
1. Normalize online payment method to `razorpay`.
2. Create internal order with status `awaiting_payment`.
3. Call `api/payment/razorpay-create-order.php`.
4. Open Razorpay checkout with returned order ID, amount, currency, and key ID.
5. On payment success, call `api/payment/razorpay-verify.php`.
6. Only after verify success: clear cart, update order status to `paid`, show confirmation.
7. On failure/cancel: keep order `awaiting_payment` or mark `failed` safely.
8. Make webhook fail closed when `RAZORPAY_WEBHOOK_SECRET` is missing in production.

Do not:
- Trust amount from the browser.
- Store key secret in frontend.
- Mark order paid before signature and gateway amount verification.

Testing checklist:
- Razorpay test success.
- Razorpay test failure/cancel.
- Duplicate verify request.
- Tampered amount.
- Wrong signature.
- Webhook captured event.

### Fix Prompt 6: View Order and Order History

Goal:
Make order history accurate and secure.

Files to inspect:
- `orders.php`
- `account.php`
- `api/order/get.php`
- `api/admin/order/list.php`
- `api/admin/order/get_details.php`

Tasks:
1. Normalize all status values before filtering/stats.
2. Add order detail view if needed, enforcing `orders.user_id = session user`.
3. Remove or implement reorder link.
4. Show payment status separately from fulfillment status if possible.

Testing checklist:
- User sees only own orders.
- Empty order history.
- Pending/paid/failed/delivered filters.
- Bundle item display.

### Fix Prompt 7: Admin Authentication and Sessions

Goal:
Harden admin login/session/logout.

Files to inspect:
- `admin/admin-login.php`
- `admin/admin-dashboard.php`
- `admin/admin-dashboard.js`
- `api/auth/admin-login.php`
- `api/auth/logout.php`
- `api/admin/_admin.php`

Tasks:
1. Fix logout.
2. Add IP/account rate limiting.
3. Decide whether admin auth should use `users.role` or `admins` table and remove ambiguity.
4. Add optional admin session timeout.

Testing checklist:
- Login valid admin.
- Login normal customer denied.
- Five bad attempts rate limited.
- Logout prevents dashboard/API access.

### Fix Prompt 8: Admin CRUD and Audit Log

Goal:
Make admin mutations safe, traceable, and consistent.

Files to inspect:
- `api/admin/product/*`
- `api/admin/bundles/*`
- `api/admin/categories/*`
- `api/admin/order/*`
- `api/admin/user/*`
- `api/admin/messages/*`
- `api/admin/reviews/*`
- `admin/admin-dashboard.js`

Tasks:
1. Add `admin_audit_log` migration/table.
2. Log create/update/delete/status/block actions.
3. Prefer soft delete for orders/products/users where history matters.
4. Make order status transitions consistent with payment states.
5. Remove runtime schema changes from admin endpoints.

Testing checklist:
- Product CRUD.
- Bundle CRUD.
- Category CRUD.
- Order status update.
- User block/unblock.
- Message archive.
- Audit log rows created.

### Fix Prompt 9: API Response Standardization

Goal:
Standardize JSON responses and status codes without breaking frontend.

Files to inspect:
- `includes/helpers.php`
- all files under `api/`
- `script.js`
- `admin/admin-dashboard.js`

Tasks:
1. Decide on `status` or `success` response shape.
2. Update helper to support consistent `success`, `message`, `data`, `errors`.
3. Keep backward compatibility temporarily if frontend expects `status`.
4. Ensure all APIs return JSON with correct HTTP status.

Testing checklist:
- All auth/cart/order/admin APIs parse in frontend.
- Error responses show correct messages.

### Fix Prompt 10: Security Hardening

Goal:
Remove production risks.

Files to inspect:
- `.env`
- `.htaccess`
- `apiold/`
- root debug scripts
- `api/payment/webhook.php`
- `api/admin/_admin.php`
- `includes/config.php`

Tasks:
1. Move `.env`, logs, SQL dumps, migrations, and debug scripts outside web root.
2. Delete or block `apiold/`.
3. Rotate credentials that were present in local `.env`.
4. Make webhook fail closed if secret missing in production.
5. Add IP/account rate limits.
6. Re-encode uploaded images and strip metadata.

Testing checklist:
- `.env` not reachable.
- `apiold/` not reachable.
- Debug scripts not reachable.
- Upload rejects bad files.
- Webhook rejects missing/invalid signature.

### Fix Prompt 11: UI/UX Improvements

Goal:
Improve trust, clarity, and consistency across customer flows.

Files to inspect:
- `style.css`
- `script.js`
- `checkout.php`
- `cart.php`
- `product.php`
- `shopAll.php`
- `orders.php`
- `account.php`
- `includes/header.php`
- `includes/footer.php`

Tasks:
1. Use INR formatting everywhere.
2. Remove mojibake and ensure UTF-8.
3. Replace fake/random review counts with real data.
4. Improve invalid/empty states.
5. Consolidate footer/header usage.
6. Add disabled/loading states for checkout/payment.

Testing checklist:
- Desktop and mobile cart/checkout/product/list pages.
- Form errors.
- Empty cart/orders/search.

### Fix Prompt 12: Mobile Responsiveness

Goal:
Make mobile shopping and admin flows usable.

Files to inspect:
- `style.css`
- `assets/css/orders.css`
- `assets/css/bundles.css`
- `assets/css/freebies.css`
- `admin/admin.css`
- public PHP pages with inline styles

Tasks:
1. Test 375px, 768px, and desktop widths.
2. Fix checkout layout, product gallery, cart summary, admin tables/modals.
3. Ensure buttons and form controls have usable touch sizes.
4. Avoid text overflow.

Testing checklist:
- Mobile product detail.
- Mobile cart.
- Mobile checkout.
- Mobile orders.
- Mobile admin order/product modals.

### Fix Prompt 13: Error Handling and Logging

Goal:
Make failures clear to users and useful to operators.

Files to inspect:
- `includes/config.php`
- `includes/Logger.php`
- `logs/app_errors.log`
- API catch blocks
- checkout/payment JS

Tasks:
1. Move logs outside web root.
2. Add consistent server-side logging for order/payment failures.
3. Show user-safe messages.
4. Add payment/order correlation IDs in logs.

Testing checklist:
- DB failure.
- SMTP failure.
- Payment failure.
- Invalid input.

### Fix Prompt 14: Production Environment Setup

Goal:
Prepare deploy checklist and server config.

Files to inspect:
- `.env.example`
- `.htaccess`
- `includes/config.php`
- deployment docs if any

Tasks:
1. Update `.env.example` with required variables only, no secrets.
2. Document required PHP extensions: mysqli, curl, openssl, mbstring.
3. Add `robots.txt` and `sitemap.xml`.
4. Document migration order.
5. Document file permissions.
6. Add backup/restore instructions.

Testing checklist:
- Fresh clone deploy.
- Fresh DB migration.
- Payment test mode.
- Email test.

### Fix Prompt 15: Final Regression Testing

Goal:
Run full customer/admin/security regression after fixes.

Files to inspect:
- All touched files
- `API_TESTING_CHECKLIST.md`
- this audit file

Tasks:
1. Run PHP lint.
2. Run manual API tests for auth/cart/order/payment/admin.
3. Use browser testing for customer and admin flows.
4. Verify no secrets/debug files are public.
5. Produce a final launch readiness note.

Expected output:
- Test results.
- Remaining risks.
- Go/no-go recommendation.

---

## 13. Final Regression Testing Checklist

### Customer Side

- [ ] Signup with valid data.
- [ ] Signup with duplicate email.
- [ ] Login with valid credentials.
- [ ] Login wrong password.
- [ ] Logout.
- [ ] Browse homepage.
- [ ] Browse product listing.
- [ ] Search active products.
- [ ] Open valid product detail.
- [ ] Open invalid product ID.
- [ ] Add product to cart as guest.
- [ ] Add bundle to cart as guest.
- [ ] Login and merge guest cart.
- [ ] Add product to cart as logged-in user.
- [ ] Add bundle to cart as logged-in user.
- [ ] Update cart quantity.
- [ ] Remove cart item.
- [ ] Checkout with empty cart blocked.
- [ ] Checkout digital-only cart.
- [ ] Checkout physical cart with address.
- [ ] Add/select saved address.
- [ ] COD order if available.
- [ ] Razorpay payment success.
- [ ] Razorpay payment failure/cancel.
- [ ] Payment tampering rejected.
- [ ] View order history.
- [ ] Filter order statuses.
- [ ] Account profile update.
- [ ] Password reset request.
- [ ] Password reset with valid token.
- [ ] Password reset expired/invalid token.
- [ ] Contact form submit.

### Admin Side

- [ ] Admin login.
- [ ] Normal customer denied admin access.
- [ ] Dashboard loads.
- [ ] Admin logout destroys session.
- [ ] Product add.
- [ ] Product edit.
- [ ] Product image upload.
- [ ] Product archive/delete.
- [ ] Bundle add/edit/delete.
- [ ] Category add/edit/delete.
- [ ] Order list.
- [ ] Order details.
- [ ] Order status update.
- [ ] User list.
- [ ] User block/unblock.
- [ ] Contact messages list/read/archive.
- [ ] Reviews approve/delete.
- [ ] Freebies add/edit/delete/download.
- [ ] Protected APIs reject unauthenticated calls.

### Security Testing

- [ ] Access admin dashboard without login.
- [ ] Access admin APIs without login.
- [ ] Submit admin mutation without CSRF.
- [ ] Submit customer mutation without CSRF.
- [ ] Access another user's order.
- [ ] Submit invalid product ID.
- [ ] Submit invalid order ID.
- [ ] Submit script tags in forms.
- [ ] Try wrong HTTP methods.
- [ ] Try direct API access to `apiold/`.
- [ ] Try payment amount tampering.
- [ ] Try invalid Razorpay signature.
- [ ] Try webhook without signature.
- [ ] Try uploading non-image file as image.
- [ ] Confirm `.env`, logs, SQL dumps, migrations, debug scripts are not reachable.

### UI/UX Testing

- [ ] Desktop Chrome.
- [ ] Desktop Firefox.
- [ ] Desktop Edge.
- [ ] Tablet width.
- [ ] Mobile width.
- [ ] Product cards fit text and actions.
- [ ] Cart totals and checkout totals match.
- [ ] Checkout payment states are clear.
- [ ] Form errors are visible and specific.
- [ ] Empty states are helpful.
- [ ] Loading states prevent double submit.
- [ ] CTA visibility on mobile.
- [ ] Keyboard navigation.
- [ ] Focus states.
- [ ] Color contrast.

---

## Final Recommendation

Do not launch this site until the P0 items are fixed and retested:

1. Real Razorpay frontend flow with verified payment before success.
2. Correct bundle cart/order model.
3. Production schema migration alignment.
4. Secret/log/debug/legacy file cleanup.
5. Webhook fail-closed behavior.
6. Admin logout fix.

After those are fixed, run the full regression checklist above and only then consider production deployment.
