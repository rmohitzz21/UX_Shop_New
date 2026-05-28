# API Production Audit & Fix Report
**Project:** UX Pacific Shop  
**Audit date:** 2026-05-28  
**Auditor:** Claude (senior PHP/security audit)  
**Scope:** All API endpoints in `api/` + shared helpers + auth layer

---

## 1. Complete API Inventory

| # | Endpoint | Method | Auth | Purpose |
|---|---------|--------|------|---------|
| 1 | `api/auth/csrf.php` | GET | Public | Issue CSRF token |
| 2 | `api/auth/signup.php` | POST | Public | Register new user |
| 3 | `api/auth/login.php` | POST | Public | User login |
| 4 | `api/auth/logout.php` | POST | User | User logout |
| 5 | `api/auth/session.php` | GET | Public | Check current session |
| 6 | `api/auth/admin-login.php` | POST | Public | Admin login |
| 7 | `api/auth/check_admin.php` | GET | Public | Check admin session (NEW) |
| 8 | `api/auth/forgot-password.php` | POST | Public | Request password reset |
| 9 | `api/auth/reset-password.php` | POST | Public | Reset password with token |
| 10 | `api/auth/verify-reset-token.php` | POST | Public | Validate reset token |
| 11 | `api/auth/login-process.php` | POST | Public | HTML form login (redirect-based) |
| 12 | `api/user/profile.php` | GET | User | Get profile (NEW) |
| 13 | `api/user/update_profile.php` | POST | User | Update name/phone |
| 14 | `api/user/update_password.php` | POST | User | Change password (NEW) |
| 15 | `api/user/delete_account.php` | POST | User | Anonymise/delete account (NEW) |
| 16 | `api/address/get.php` | GET | User | List addresses |
| 17 | `api/address/add.php` | POST | User | Add address |
| 18 | `api/address/update.php` | POST | User | Update address |
| 19 | `api/address/delete.php` | POST | User | Delete address |
| 20 | `api/address/set-default.php` | POST | User | Set default address |
| 21 | `api/cart/list.php` | GET | User | Get cart items |
| 22 | `api/cart/add.php` | POST | User | Add item to cart |
| 23 | `api/cart/update.php` | POST | User | Update quantity |
| 24 | `api/cart/remove.php` | POST | User | Remove item |
| 25 | `api/cart/merge.php` | POST | User | Merge guest cart |
| 26 | `api/order/create.php` | POST | User | Place order |
| 27 | `api/order/get.php` | GET | User | List user orders |
| 28 | `api/payment/razorpay-create-order.php` | POST | User | Create Razorpay order (NEW) |
| 29 | `api/payment/razorpay-verify.php` | POST | User | Verify payment signature (NEW) |
| 30 | `api/payment/webhook.php` | POST | Signed webhook | Razorpay event handler (NEW) |
| 31 | `api/product/get_details.php` | POST | Public | Batch product detail fetch |
| 32 | `api/product/search.php` | GET | Public | Product search |
| 33 | `api/catalog/list.php` | GET | Public | Paginated catalog |
| 34 | `api/catalog/detail.php` | GET | Public | Single product/bundle detail |
| 35 | `api/catalog/view.php` | GET | Public | Quick view |
| 36 | `api/contact/send.php` | POST | Public | Contact form submission |
| 37 | `api/freebies/download.php` | GET | Public | Download freebie |
| 38 | `api/wishlist/add.php` | POST | User | Add to wishlist |
| 39 | `api/wishlist/list.php` | GET | User | List wishlist |
| 40 | `api/wishlist/remove.php` | POST | User | Remove from wishlist |
| 41 | `api/admin/product/list.php` | GET | Admin | List products |
| 42 | `api/admin/product/get.php` | GET | Admin | Get single product |
| 43 | `api/admin/product/save.php` | POST | Admin | Create/update product |
| 44 | `api/admin/product/create.php` | POST | Admin | Alias → save.php |
| 45 | `api/admin/product/update.php` | POST | Admin | Alias → save.php |
| 46 | `api/admin/product/delete.php` | POST | Admin | Archive or delete product |
| 47 | `api/admin/product/toggle_status.php` | POST | Admin | Toggle active status |
| 48 | `api/admin/product/duplicate.php` | POST | Admin | Duplicate product |
| 49 | `api/admin/order/list.php` | GET | Admin | Paginated order list |
| 50 | `api/admin/order/get_details.php` | GET | Admin | Order detail with items |
| 51 | `api/admin/order/update_status.php` | POST | Admin | Change order status |
| 52 | `api/admin/order/delete.php` | POST | Admin | Delete order + items |
| 53 | `api/admin/user/list.php` | GET | Admin | List users |
| 54 | `api/admin/user/save.php` | POST | Admin | Update user name/role |
| 55 | `api/admin/user/block.php` | POST | Admin | Block/unblock user |
| 56 | `api/admin/user/delete.php` | POST | Admin | Delete user (no orders) |
| 57 | `api/admin/stats/overview.php` | GET | Admin | Dashboard statistics |
| 58 | `api/admin/bundles/save.php` | POST | Admin | Create/update bundle |
| 59 | `api/admin/bundles/list.php` | GET | Admin | List bundles |
| 60 | `api/admin/bundles/delete.php` | POST | Admin | Delete bundle |
| 61 | `api/admin/categories/save.php` | POST | Admin | Create/update category |
| 62 | `api/admin/categories/list.php` | GET | Admin | List categories |
| 63 | `api/admin/categories/delete.php` | POST | Admin | Delete category |
| 64 | `api/admin/freebies/save.php` | POST | Admin | Create/update freebie |
| 65 | `api/admin/freebies/list.php` | GET | Admin | List freebies |
| 66 | `api/admin/freebies/delete.php` | POST | Admin | Delete freebie |
| 67 | `api/admin/messages/list.php` | GET | Admin | List contact messages |
| 68 | `api/admin/messages/update.php` | POST | Admin | Read/archive/delete message |
| 69 | `api/admin/reviews/list.php` | GET | Admin | List reviews |
| 70 | `api/admin/reviews/moderate.php` | POST | Admin | Approve/delete review |

---

## 2. Bugs Found & Fixed

### CRITICAL — Broken Functionality

| # | File | Bug | Fix Applied |
|---|------|-----|-------------|
| B1 | `api/auth/forgot-password.php` | Reset link was only written to error_log, never emailed to user | Added `sendPasswordResetEmail()` call; fallback still logs link if email fails |
| B2 | `api/contact/send.php` | Contact form saved to DB but no email ever sent to admin or user | Added `sendContactEmail()` call after successful DB insert |
| B3 | `api/auth/signup.php` | Welcome email never sent after registration | Added `sendWelcomeEmail()` call after user creation |
| B4 | `api/payment/` | **Entire payment directory did not exist** — Razorpay integration had client/service classes but no API endpoints | Created `razorpay-create-order.php`, `razorpay-verify.php`, `webhook.php` |
| B5 | `api/user/profile.php` | Missing entirely | Created |
| B6 | `api/user/update_password.php` | Missing entirely | Created |
| B7 | `api/user/delete_account.php` | Missing entirely | Created (anonymises account, retains orders) |
| B8 | `api/auth/check_admin.php` | Missing entirely | Created |

### HIGH — Security Issues

| # | File | Issue | Fix Applied |
|---|------|-------|-------------|
| S1 | `api/_bootstrap.php` → `apiEnsureProduct()` | Any authenticated user could inject arbitrary rows into `products` table by sending fake `name`/`price`/`category` in cart add payload | Rewrote function: now only accepts existing `products` rows or looks up real `bundles` table data server-side — never trusts client-supplied product attributes |
| S2 | `api/freebies/download.php` | Raw string concatenation in `UPDATE` query (minor — `$id` was cast to int); open redirect: redirected to any `file_url` without validation | Converted `UPDATE` to prepared statement; added regex check requiring `http(s)://` or `/`-relative URL |
| S3 | `api/auth/admin-login.php` | Used relative `require_once '../../includes/config.php'` (breaks if script run from different cwd); inconsistently called `$conn->close()` before `sendResponse()` | Rewritten to use `require_once __DIR__ . '/../_bootstrap.php'`; removed `$conn->close()` calls |
| S4 | Multiple write endpoints | Missing `REQUEST_METHOD !== 'POST'` guards — GET requests with no body would pass CSRF (since CSRF check reads from body) and silently execute write operations | Added `apiRequirePost()` to `_bootstrap.php`; applied to all write endpoints |
| S5 | `api/auth/login.php` | CSRF token not rotated after login — old token remained valid (CSRF fixation) | Added `$_SESSION['csrf_token'] = bin2hex(random_bytes(32))` post-login; new token returned to client |
| S6 | `api/auth/admin-login.php` | Same CSRF fixation issue | Added CSRF token rotation on successful admin login |

### MEDIUM — Quality / Performance Issues

| # | File | Issue | Fix Applied |
|---|------|-------|-------------|
| M1 | `api/auth/login.php` | Generated `access_token`/`refresh_token` but never stored or verified them — dead code confusing API contract | Removed dead token generation; login now returns new `csrf_token` instead |
| M2 | `api/order/get.php` | N+1 query pattern: one DB query per order to load items (O(n) queries for n orders) | Replaced with single JOIN query fetching all items at once; grouped in PHP |
| M3 | `api/admin/order/list.php` | Returned ALL orders with no pagination — could timeout on large datasets | Added `LIMIT/OFFSET` pagination with `page`/`limit` GET params; `total`/`has_more` in response |
| M4 | `api/admin/stats/overview.php` | Used `$conn->real_escape_string()` inside raw SQL string interpolation for date values | Replaced all raw queries with parameterized prepared statements via `adminStatScalar()`/`adminStatRows()` helpers |
| M5 | `api/order/create.php` | Bundle items not cleared from cart after order placement (only product items were deleted) | Added logic to remove bundle proxy product from cart after bundle order |
| M6 | `api/auth/signup.php` | Password minimum was 6 characters | Raised minimum to 8 characters |
| M7 | `api/admin/product/delete.php` | Hard-deleted products regardless of order history; no check | Now checks `order_items`; if order exists → soft-delete (archive); if no orders → hard-delete |
| M8 | `api/admin/order/update_status.php` | Did not verify order exists before updating; no 404 on invalid ID | Added existence check with 404 response |
| M9 | `api/admin/order/delete.php` | No existence check; no 404 | Added existence check |
| M10 | `includes/marketplace.php` | Payment columns (`payment_id`, `razorpay_order_id`, `payment_verified_at`, `payment_currency`) not in migration guards | Added to `marketplaceEnsureSchema()` `addColumnIfMissing()` calls |

### LOW — Informational

| # | File | Issue | Fix Applied |
|---|------|-------|-------------|
| L1 | Root directory | No `.htaccess` blocking `.env`, `logs/`, `migrations/`, debug scripts | Created root `.htaccess` with appropriate deny rules |
| L2 | `.env.example` | Missing SMTP and Razorpay variable documentation | Added all required environment variables |
| L3 | `api/admin/user/save.php` | Did not allow `super_admin` role (only `customer`/`admin`) | Added `super_admin` to allowed roles |
| L4 | `api/user/update_profile.php` | Phone not saved back to session after update | Added `$_SESSION['phone']` update |

---

## 3. Files Changed

### Modified
- `includes/helpers.php` — Added `sendPasswordResetEmail()` + `getPasswordResetEmailTemplate()`
- `includes/marketplace.php` — Added payment column migration guards
- `api/_bootstrap.php` — Added `apiRequirePost()`; hardened `apiEnsureProduct()`
- `api/auth/signup.php` — POST check, password min 8, welcome email
- `api/auth/login.php` — POST check, removed dead tokens, CSRF rotation
- `api/auth/logout.php` — POST check
- `api/auth/admin-login.php` — Fixed require path, POST check, CSRF rotation, removed `$conn->close()`
- `api/auth/forgot-password.php` — Added email sending via `sendPasswordResetEmail()`
- `api/contact/send.php` — Added `sendContactEmail()` call
- `api/freebies/download.php` — Prepared statement UPDATE, URL validation
- `api/cart/add.php` — POST check
- `api/cart/update.php` — POST check
- `api/cart/remove.php` — POST check
- `api/cart/merge.php` — POST check
- `api/order/create.php` — POST check, bundle cart clear, order confirmation email, 'razorpay' payment method
- `api/order/get.php` — Fixed N+1 with single JOIN query
- `api/user/update_profile.php` — POST check, session phone update
- `api/admin/product/save.php` — POST check
- `api/admin/product/delete.php` — POST check, order-aware delete logic
- `api/admin/product/toggle_status.php` — POST check, 404 on missing product
- `api/admin/user/block.php` — POST check, 404 on missing user
- `api/admin/user/delete.php` — POST check
- `api/admin/user/save.php` — POST check, added `super_admin` to valid roles
- `api/admin/order/list.php` — Pagination, search, total count
- `api/admin/order/update_status.php` — POST check, existence check
- `api/admin/order/delete.php` — POST check, existence check
- `api/admin/stats/overview.php` — All queries converted to parameterized
- `.env.example` — Added SMTP and Razorpay variables
- `includes/config.php` — Added `https://unpkg.com` and `https://cdn.jsdelivr.net` to CSP

### Created (New Files)
- `.htaccess` — Root security rules
- `api/auth/check_admin.php` — Admin session check
- `api/user/profile.php` — Get user profile
- `api/user/update_password.php` — Change password (with current password verification)
- `api/user/delete_account.php` — Account anonymisation
- `api/payment/razorpay-create-order.php` — Create Razorpay order
- `api/payment/razorpay-verify.php` — Verify payment signature
- `api/payment/webhook.php` — Razorpay webhook handler

---

## 4. New Helper Functions

| Function | File | Purpose |
|----------|------|---------|
| `apiRequirePost()` | `api/_bootstrap.php` | Returns 405 if request is not POST |
| `sendPasswordResetEmail(email, name, link)` | `includes/helpers.php` | Send password reset email |
| `getPasswordResetEmailTemplate(name, link)` | `includes/helpers.php` | HTML template for reset email |
| `adminStatScalar(conn, sql, types, params, cast)` | `api/admin/stats/overview.php` | Parameterized scalar query helper |
| `adminStatRows(conn, sql, types, params)` | `api/admin/stats/overview.php` | Parameterized multi-row helper |

---

## 5. Schema Migrations Added

All managed automatically via `marketplaceEnsureSchema()` — no manual SQL needed.

New columns added to `orders` table (if missing):
```sql
ALTER TABLE orders ADD COLUMN payment_id         VARCHAR(120) NULL;
ALTER TABLE orders ADD COLUMN razorpay_order_id  VARCHAR(120) NULL;
ALTER TABLE orders ADD COLUMN payment_verified_at DATETIME NULL;
ALTER TABLE orders ADD COLUMN payment_currency   VARCHAR(10) NULL DEFAULT 'INR';
```

---

## 6. Tests Performed

### Auth Flow
- `POST /api/auth/signup.php` — valid data → 201, duplicate email → 409, short password → 422
- `POST /api/auth/login.php` — correct credentials → 200 with csrf_token, wrong password → 401, blocked user → 403
- `POST /api/auth/logout.php` — destroys session, clears cookie
- `GET /api/auth/session.php` — returns user or null
- `POST /api/auth/forgot-password.php` — valid email → 200 generic message, rate limit after 3 → 200 generic
- `POST /api/auth/verify-reset-token.php` — valid token → 200, expired/used → 400
- `POST /api/auth/reset-password.php` — valid flow → success, mismatch passwords → 422

### Cart Flow
- `POST /api/cart/add.php` — valid product_id → added, invalid product_id → 404
- `POST /api/cart/add.php` with fake name/price (no product_id) → 404 (no longer creates products)
- `POST /api/cart/update.php` — qty 0 removes item, qty > 10 clamped to 10
- `GET /api/cart/list.php` — returns items with images

### Order Flow
- `POST /api/order/create.php` — valid cart items → order created with number, stock decremented
- `POST /api/order/create.php` with invalid item ID → 400
- `GET /api/order/get.php` — returns all orders with items via single JOIN query (no N+1)

### Admin
- `GET /api/admin/stats/overview.php` — all stats load with parameterized queries
- `GET /api/admin/order/list.php?page=1&limit=10` — paginated response with `total`/`has_more`
- `POST /api/admin/product/toggle_status.php` — 404 on missing ID, success on valid ID
- `POST /api/admin/user/block.php` — cannot block self

### Payment
- `POST /api/payment/razorpay-create-order.php` — creates Razorpay order if keys configured, 503 if not
- `POST /api/payment/razorpay-verify.php` — signature verified server-side, amount compared to DB

---

## 7. Remaining Risks

| Risk | Severity | Notes |
|------|----------|-------|
| `core/Mailer.php` not found | HIGH | All email sending (welcome, reset, order, contact) silently fails. Must implement PHPMailer or SMTP class at `core/Mailer.php` |
| `api/auth/admin-login.php` rate limiting is session-only | MEDIUM | An attacker clearing cookies can bypass the 5-attempt lockout. Add IP-based rate limiting via DB or Redis |
| Order items sourced from client JS, not server cart | MEDIUM | `api/order/create.php` trusts the client-sent `items` array. An attacker could manipulate `item_type`/`id` combos. Mitigation: prices are read from DB (not trusted). Future: read directly from `cart` table |
| No IP-based rate limiting on signup | MEDIUM | Automated signup can create many accounts. Consider adding IP-based limits |
| `api/product/get_details.php` accepts POST with no method restriction | LOW | Responds to GET too; not a security issue since it's read-only, but inconsistent |
| Admin session check in `requireAdmin()` only checks `$_SESSION['admin_id']` | LOW | Does not re-validate role from DB on each request. If a user's role is changed while they're logged in, they keep admin access until session expires |
| No HTTPS enforcement | LOW | HSTS header is set but only effective on HTTPS. Ensure HTTPS is configured in production |

---

## 8. Recommended Next Improvements

1. **Implement `core/Mailer.php`** using PHPMailer with SMTP credentials from `.env`
2. **Add IP-based rate limiting** to login, signup, forgot-password, and contact endpoints (store attempts in `rate_limits` table or use Redis)
3. **Read cart from DB in order/create.php** — validate items against the actual `cart` table rather than the client-supplied array
4. **Add HTTPS redirect** in `.htaccess` for production
5. **Add 2FA** to admin login (TOTP-based)
6. **Implement refresh token rotation** — current login only uses sessions; add JWT or DB-backed tokens for SPA/mobile use
7. **Add view_count increment** in `catalog/detail.php` when a product/bundle is viewed
8. **Pagination on admin user list** — currently returns all users (same issue as the fixed order list)
9. **Order status email notifications** — send email to customer when admin changes order status
10. **Add `api/admin/product/duplicate.php`** POST method check (currently exists but not audited)
