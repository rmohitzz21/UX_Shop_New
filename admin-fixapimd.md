# Admin API audit — Products (Create / Read / Update / Delete)

**Audited:** 2026-05-28  
**Scope:** `api/admin/product/*` + `admin/admin-dashboard.js` product flows  
**Verdict:** **Usable for production with fixes applied below** — auth + CSRF are in place; soft-delete for ordered products is correct. Several edge-case bugs were found; three are **fixed in code** (see § Fixed).

---

## Endpoint map

| Action | Endpoint | Method | Auth | CSRF |
|--------|----------|--------|------|------|
| List | `api/admin/product/list.php` | GET | Admin session | No |
| View one | `api/admin/product/get.php?id=` | GET | Admin session | No |
| Create | `api/admin/product/create.php` → `save.php` | POST | Admin session | Yes |
| Update | `api/admin/product/update.php` → `save.php` | POST | Admin session | Yes |
| Delete / archive | `api/admin/product/delete.php` | POST | Admin session | Yes |
| Toggle active | `api/admin/product/toggle_status.php` | POST | Admin session | Yes |
| Duplicate | `api/admin/product/duplicate.php` | POST | Admin session | Yes |

Dashboard uses `fetchJson()` with `X-CSRF-Token` from `<meta name="csrf-token">` (set in `admin-dashboard.php`).

---

## What works well (production-ready)

1. **Admin gate** — All routes use `requireAdmin()` via `api/admin/_admin.php`.
2. **CSRF on mutations** — `save.php`, `delete.php`, `toggle_status.php`, `duplicate.php` call `validateCsrf()`.
3. **Delete safety** — Products with `order_items` are **archived** (`is_active = 0`), not hard-deleted.
4. **Image uploads** — Type/size checks, random filenames, stored under `img/products/`.
5. **Inventory audit** — Stock changes logged to `inventory_movements` on create/update.
6. **JSON responses** — Consistent `{ status, message, data? }` via `sendResponse()`.
7. **List filters** — `q`, `status`, `category` query params work.

---

## Fixed in this pass

| ID | Severity | Issue | Fix |
|----|----------|-------|-----|
| P1 | **High** | **Update wiped `additional_images`** when the modal save did not send that field (empty `[]` written). | `save.php` restores gallery from DB when `additional_images` is omitted on update. |
| P2 | **Medium** | **`toggle_status.php`** returned 404 “Product not found” when status was already the requested value (`affected_rows === 0`). | Check row exists first; return success if already in target state. |
| P3 | **Medium** | **`delete.php` archive path** returned success for invalid product IDs. | Verify product exists before soft-archive. |

---

## Open issues (recommended follow-up)

| ID | Severity | Area | Description | Suggested fix |
|----|----------|------|-------------|----------------|
| P4 | ~~Medium~~ | UI | ~~Product modal has no gallery upload~~ | **Done:** `media[]` multi-file input + gallery preview/remove in product modal. |
| P5 | Medium | UI | `editProduct()` does not load `slug` (field not in modal). Slug auto-regenerates from name on every save. | Add optional slug input; only auto-slug on create. |
| P6 | Low | API | No **price &gt; 0** or **stock** server validation beyond `max(0, stock)`. | Reject `price <= 0` with 422. |
| P7 | Low | API | **Duplicate slug** on create/update surfaces raw MySQL error in 500 response. | Catch duplicate key; return 409 + friendly message. |
| P8 | Low | API | **Hard delete** does not remove `cart` / `wishlist` rows for that `product_id`. | `DELETE FROM cart WHERE product_id = ?` (and wishlist) before `DELETE FROM products`. |
| P9 | Low | API | **`list.php`** returns full table with no pagination. | Add `page` / `limit` for large catalogs. |
| P10 | Info | UI | Admin `money()` still shows **₹** while storefront is moving to **$**. | Align admin labels + `money()` with storefront currency. |
| P11 | Info | API | `get.php` returns raw DB row in `data` (not `{ product: ... }`). Dashboard uses `data.product \|\| data` — OK. | Optional: normalize to `{ product: row }` for consistency with other APIs. |
| P12 | Info | UI | `duplicateProduct()` exists in JS but **no Duplicate button** in products table. | Add action or remove dead export. |

---

## Per-endpoint notes

### `list.php` (view all)

- **Stable:** Yes for typical catalog sizes.
- **Response:** `data` = array of product rows.
- **Gap:** No pagination (P9).

### `get.php` (view one)

- **Stable:** Yes.
- **Response:** `data` = full product row + `additional_images_list` (decoded array).
- **Note:** Includes inactive/archived products (correct for admin edit).

### `save.php` / `create.php` / `update.php`

- **Stable:** Yes after P1 fix.
- **Input:** `adminInput()` — `$_POST` for `multipart/form-data`, JSON body otherwise.
- **Required:** `name` (422 if empty).
- **Images:** `image` / `media` file fields; `existing_image` on update.
- **Defaults:** `available_type` → `digital`; missing image → `img/poster.webp`.
- **Risk:** Nullable `old_price` / `commercial_price` bound as `d` — verify MySQL driver stores NULL correctly on your PHP version.

### `delete.php`

- **Stable:** Yes after P3 fix.
- **Behavior:** Orders exist → archive; else hard delete.
- **Gap:** Cart/wishlist cleanup (P8).

### `toggle_status.php`

- **Stable:** Yes after P2 fix.

### `duplicate.php`

- **Stable:** Mostly — creates inactive copy (`is_active = 0`, `is_featured = 0`).
- **Gap:** No check on `$copy->execute()` failure; no inventory movement row (acceptable).

---

## Dashboard wiring checklist

| UI action | JS function | API |
|-----------|-------------|-----|
| Add Product | `openCreateProductModal` → `saveProductForm` | `create.php` |
| Edit | `editProduct` → `saveProductForm` | `get.php` + `update.php` |
| Archive / Restore | `toggleProductStatus` | `toggle_status.php` |
| Delete | `deleteProduct` | `delete.php` |

Form submit is bound in `bindDashboard()` (`form.onsubmit = saveProductForm`), overriding inline `handleUpdateProduct` in HTML.

---

## Manual test plan (admin UI)

1. Log in at `admin/admin-login.php` (or your admin auth route).
2. **Create** — Products → Add Product → fill name, category, price, stock → Save → appears in table.
3. **View / Edit** — Edit → change price → Save → reload edit → values persist.
4. **Gallery** — Set `additional_images` in DB (JSON array) → edit name only → Save → gallery unchanged (P1).
5. **Archive** — Archive → status Archived → Restore → Active.
6. **Delete (no orders)** — Delete → row removed from list.
7. **Delete (with orders)** — Place test order for product → Delete → archived message, row still in list as Archived.

---

## Production readiness summary

| Area | Rating | Notes |
|------|--------|-------|
| Security (auth + CSRF) | Good | |
| Delete / orders | Good | Soft-delete when ordered |
| Create / update core fields | Good | After P1 |
| Media / gallery admin UX | Fair | P4, P5 |
| Scale (list) | Fair | P9 |
| Operational polish | Fair | P6–P8, P10 |

**Recommendation:** Ship product admin APIs after deploying P1–P3 fixes; schedule P4–P8 in next admin sprint.

---

# Admin API audit — Bundles (Create / Read / Update / Delete)

**Audited:** 2026-05-28  
**Scope:** `api/admin/bundles/*` + bundle form in `admin/admin-dashboard.php` / `admin-dashboard.js`

## Endpoint map

| Action | Endpoint | Method | Auth | CSRF |
|--------|----------|--------|------|------|
| List | `api/admin/bundles/list.php` | GET | Admin session | No |
| Create / Update | `api/admin/bundles/save.php` | POST | Admin session | Yes |
| Delete | `api/admin/bundles/delete.php` | POST | Admin session | Yes |
| Toggle featured/active | `api/admin/bundles/save.php` (JSON body) | POST | Admin session | Yes |

## Fixed in bundles pass

| ID | Issue | Fix |
|----|-------|-----|
| B1 | **Gallery wiped on edit** when `additional_images` was empty (saved `null`). | Preserve DB gallery on update when field empty and no new uploads. |
| B2 | **No multi-file upload** — only path textarea. | `media[]` multi-select + cover file input + preview/remove UI (matches products). |
| B3 | Cover image could be lost when only gallery uploaded. | `existing_image` + `image` upload handling aligned with product save. |
| B4 | List API did not decode gallery for JS. | `additional_images_list` on each row in `list.php`. |

## Bundle media (admin UI)

- **Cover Image** — single file (`name="image"`).
- **Gallery Images** — multiple files (`name="media[]" multiple`).
- Hidden `additional_images` JSON synced before submit.
- Thumbnails with remove; pending upload preview before save.

## Manual test plan

1. Bundles → **Add Bundle** → upload cover + 2+ gallery files → Save → edit bundle → gallery still listed.
2. Edit bundle → remove one gallery thumb → Save → only removed image gone.
3. Toggle **Best Seller** / **Active** from table → bundle data (including images) unchanged.
4. Delete bundle → removed from list.

## Open follow-ups (bundles)

| ID | Description |
|----|-------------|
| B5 | `delete.php` hard-deletes always; no order/archive check (unlike products). |
| B6 | Toggle via JSON sends full row; ensure `additional_images` stays valid JSON string. |
| B7 | Uploaded images stored under `img/products/` (shared uploader). Optional: `img/bundles/`. |

---

# Admin API audit — Orders (List / View / Update Status / Delete)

**Audited:** 2026-05-28  
**Scope:** `api/admin/order/*` + Orders tab in `admin-dashboard.js`

## Endpoint map

| Action | Endpoint | Method | Auth | CSRF |
|--------|----------|--------|------|------|
| List | `api/admin/order/list.php` | GET | Admin session | No |
| View details | `api/admin/order/get_details.php?id=` | GET | Admin session | No |
| Update status | `api/admin/order/update_status.php` | POST | Admin session | Yes |
| Delete | `api/admin/order/delete.php` | POST | Admin session | Yes |

## Verdict

**Production-ready with fixes applied** — Core flows work; auth + CSRF on mutations. Aligns with checkout statuses (`pending`, `awaiting_payment`, `paid` after Razorpay).

## Fixed in this pass

| ID | Severity | Issue | Fix |
|----|----------|-------|-----|
| O1 | **High** | Dashboard loaded only **25 orders** (API default) — search/filter/analytics missed older orders. | `getOrders()` requests `limit=500`; shows hint if more exist. |
| O2 | **High** | Status update mapped **`paid` → `processing`**, breaking Razorpay-paid orders and filters. | `update_status.php` stores canonical statuses; `paid` stays `paid`. Shared `_helpers.php`. |
| O3 | **Medium** | Status update used **order number from modal text** instead of ID. | Hidden `modal-order-id`; API called with `{ id, status }`. |
| O4 | **Medium** | List status filter exact match missed legacy casing (`Pending`, `Paid`). | `list.php` uses `IN (...)` alias groups per filter. |
| O5 | Low | Order detail modal lacked shipping/payment breakdown. | `viewOrder` shows shipping, payment IDs, subtotal/shipping/tax. |
| O6 | Low | Delete/status errors not surfaced in UI. | `try/catch` + toast on failure. |

## What works well

1. **Admin gate** on all routes.
2. **CSRF** on `update_status` and `delete`.
3. **List API** — pagination metadata (`total`, `page`, `limit`, `has_more`), search (`q`), status filter.
4. **get_details** — line items with product/bundle names and images; guest shipping from JSON.
5. **delete** — transactional delete of `order_items` then `orders`; existence check first.
6. **Overview stats** — separate from list; recent orders on dashboard.

## Status values (canonical)

| Status | When used |
|--------|-----------|
| `pending` | COD / non-Razorpay checkout |
| `awaiting_payment` | Razorpay order created, not paid |
| `paid` | Razorpay verified (`OrderPaymentService`) |
| `processing` | Admin fulfillment step |
| `shipped` / `delivered` | Fulfillment |
| `cancelled` | Cancelled / failed |

Admin filter dropdown and status modal use these keys (with legacy alias matching in list filter).

## Manual test plan

1. **List** — Orders tab loads; search by order # / email works across all loaded orders.
2. **View** — View → customer, items, shipping, payment method shown.
3. **Status** — Status → set `Processing` → `Shipped` → badge updates in table.
4. **Paid orders** — Razorpay order shows `Paid`; filter “Paid” finds it; setting Paid does not change to Processing.
5. **Delete** — Delete test order → removed from list and overview count.

## Open follow-ups (orders)

| ID | Severity | Description |
|----|----------|-------------|
| O7 | Medium | **Hard delete** only — no archive; deleting paid orders removes financial history. Prefer soft-delete or block delete when `status = paid`. |
| O8 | Low | No **pagination UI** — cap 500 rows; hint shown if `has_more`. |
| O9 | Low | **Analytics tab** still computes from loaded orders subset, not full DB. Use stats API or paginate all pages. |
| O10 | Info | Old DB dumps may use ENUM `Pending`/`Processing`; filters include legacy casing. |

---

# Admin API audit — Categories (List / Create / Update / Delete)

**Audited:** 2026-05-28  
**Scope:** `api/admin/categories/*` + Categories tab in admin dashboard

## Endpoint map

| Action | Endpoint | Method | Auth | CSRF |
|--------|----------|--------|------|------|
| List | `api/admin/categories/list.php` | GET | Admin session | No |
| Create / Update | `api/admin/categories/save.php` | POST | Admin session | Yes |
| Delete | `api/admin/categories/delete.php` | POST | Admin session | Yes |
| Toggle active | `api/admin/categories/save.php` (JSON) | POST | Admin session | Yes |

Products store category as **name string** (`products.category`), not FK — rename must sync related tables.

## Verdict

**Production-ready with fixes applied** — Auth, CSRF, delete guards, and rename sync are in place.

## Fixed in this pass

| ID | Severity | Issue | Fix |
|----|----------|-------|-----|
| C1 | **High** | **Renaming category** updated `categories.name` only — products still used old name (wrong counts, broken filters). | `save.php` syncs `products`, `bundles`, `freebies` when name changes. |
| C2 | **Medium** | **Delete** only checked products; bundles/freebies with same category name could be orphaned. | `delete.php` counts all three tables; clearer error message. |
| C3 | **Medium** | Duplicate name/slug returned raw MySQL 500. | Returns **409** with friendly message. |
| C4 | Low | Toggle sent full list row (`product_count`, etc.). | Minimal JSON payload + error toast. |
| C5 | Low | Hidden categories still in product dropdown. | `populateCategoryControls()` uses **active only**. |
| C6 | Low | No icon preview on edit. | Thumbnail preview + remove on category form. |

## What works well

1. **Admin gate** + **CSRF** on mutations.
2. **List** — search (`q`), status filter, `product_count` via join on category name.
3. **Delete protection** — blocks delete when category name is in use.
4. **Icon upload** — `image` field via shared `adminUploadImages()`.
5. **Dashboard** — create/edit form, Hide/Show toggle, refreshes product filters after save.

## Manual test plan

1. **Create** — Add Category with name + icon → appears in list and product category dropdown.
2. **Edit** — Change name → products in that category show new name in Products tab filter.
3. **Hide** — Hide category → removed from product dropdown; still in categories table as archived.
4. **Delete blocked** — Try delete category with products → error with count.
5. **Delete empty** — Delete unused category → success.

## Open follow-ups (categories)

| ID | Severity | Description |
|----|----------|-------------|
| C7 | Medium | Products use **string category**, not `category_id` FK — typos/mismatches possible. Long-term: FK migration. |
| C8 | Low | No dedicated `get.php` — edit uses list row data (OK for small catalogs). |
| C9 | Low | Form has no **is_active** toggle — only table Hide/Show (acceptable). |
| C10 | Info | Icons stored under `img/products/` (shared uploader). |

---

# Admin API audit — Freebies (List / Create / Update / Delete)

**Audited:** 2026-05-28  
**Scope:** `api/admin/freebies/*` + Freebies tab + public `api/freebies/download.php`

## Endpoint map

| Action | Endpoint | Method | Auth | CSRF |
|--------|----------|--------|------|------|
| List | `api/admin/freebies/list.php` | GET | Admin session | No |
| Create / Update | `api/admin/freebies/save.php` | POST | Admin session | Yes |
| Delete | `api/admin/freebies/delete.php` | POST | Admin session | Yes |
| Public download | `api/freebies/download.php?id=` | GET | Public | No |

Storefront: `freebies.php` reads `is_active = 1` rows; downloads redirect via `api/freebies/download.php`.

## Verdict

**Production-ready with fixes applied** — CRUD, search, and download flow are aligned.

## Fixed in this pass

| ID | Severity | Issue | Fix |
|----|----------|-------|-----|
| F1 | **High** | **`file_url` not validated** on server — freebies could be saved without a working link. | Required valid `http`/`https` URL in `save.php` + form `required`. |
| F2 | Medium | Duplicate slug/name → raw MySQL 500. | Returns **409** friendly error. |
| F3 | Medium | **Add Freebie** opened form without reset (stale edit data). | `resetFreebieForm()` on open/close. |
| F4 | Medium | Toggle sent full row (`download_count`, timestamps). | Minimal JSON payload + success toast. |
| F5 | Low | No cover image preview on edit. | Thumbnail preview + remove. |
| F6 | Low | Slug from hidden field could drift from name. | Slug always derived from name on save. |
| F7 | Low | `delete.php` no `affected_rows` check. | 404 if not deleted. |
| F8 | Low | Table `CREATE` only in `list.php`. | Shared `adminEnsureFreebiesTable()` in `_helpers.php` for list/save/delete. |

## What works well

1. **Admin gate** + **CSRF** on mutations.
2. **List** — server search (`q`), optional `status` filter, sort by `sort_order`.
3. **Checkboxes** — `is_active` / `is_featured` synced via FormData in JS.
4. **Image upload** — cover via `adminUploadImages('image')`; preserved on edit.
5. **Download counter** — public API increments `download_count` safely.
6. **Hide/Show** — maps to `is_active`; hidden freebies excluded from `freebies.php`.

## Manual test plan

1. **Create** — Name + https Figma/Canva link + optional image → appears in admin list and on `/freebies.php`.
2. **Edit** — Change link and cover → save → storefront reflects changes.
3. **Hide** — Hide freebie → gone from public page, still in admin list as archived.
4. **Featured** — Mark featured → sorts higher on freebies page (`is_featured DESC`).
5. **Download** — Click download on storefront → count increments in admin.
6. **Delete** — Remove test freebie → gone from admin and storefront.
7. **Search** — Admin search box filters via API `?q=`.

## Open follow-ups (freebies)

| ID | Severity | Description |
|----|----------|-------------|
| F9 | Low | No `get.php` — edit uses list row (OK for typical catalog size). |
| F10 | Low | No **featured toggle** in table (only in form). |
| F11 | Info | Cover images stored under `img/products/` (shared uploader). |
| F12 | Info | `file_url` must be absolute https — relative paths not allowed (matches download security). |

---

# Admin API audit — Users (List / Block / Update / Delete)

**Audited:** 2026-05-28  
**Scope:** `api/admin/user/*` + Users tab in admin dashboard

## Endpoint map

| Action | Endpoint | Method | Auth | CSRF |
|--------|----------|--------|------|------|
| List | `api/admin/user/list.php` | GET | Admin session | No |
| Update profile | `api/admin/user/save.php` | POST | Admin session | Yes |
| Block / Unblock | `api/admin/user/block.php` | POST | Admin session | Yes |
| Delete | `api/admin/user/delete.php` | POST | Admin session | Yes |

**Dashboard UI today:** list + block/unblock only. `save.php` and `delete.php` exist for API/future UI but are not wired in the table.

## Verdict

**Production-ready with fixes applied** — Customer list, block flow, and login integration (`is_blocked` checked on sign-in) are sound.

## Fixed in this pass

| ID | Severity | Issue | Fix |
|----|----------|-------|-----|
| U1 | **High** | **Admin accounts** appeared in customer list (same `users` table as storefront login). | List excludes `admin` / `super_admin` roles by default. |
| U2 | **Medium** | Block/unblock returned **404** when status already matched (`affected_rows === 0`). | Idempotent success if already blocked/active. |
| U3 | **Medium** | Could block **admin** accounts or self via API. | Guards: no self-action; privileged roles cannot be blocked/deleted/edited. |
| U4 | Medium | List used raw query, no server search. | Prepared statements + `?q=` and `?blocked=` filters. |
| U5 | Low | `delete.php` used awkward `NOT EXISTS` subquery in DELETE. | Explicit order count check + clear 409 message. |
| U6 | Low | `save.php` allowed promoting users to `admin`. | Customer panel updates locked to `customer` role only. |
| U7 | Low | Block toggle had no error toast. | `try/catch` + success/error toasts. |
| U8 | Low | Search was client-only. | Search calls API `?q=` (server-side). |

## What works well

1. **Admin gate** + **CSRF** on mutations.
2. **List** — `order_count` and `lifetime_value` per customer.
3. **Block** — sets `is_blocked`; `api/auth/login.php` rejects blocked users.
4. **Delete** — prevented when user has orders (409); cannot delete self.
5. **Self-protection** — cannot block/delete your own admin session user.

## Manual test plan

1. **List** — Users tab shows customers (not your admin login row).
2. **Search** — Type email in search → matching users via API.
3. **Block** — Block user → they cannot sign in; status shows Blocked.
4. **Unblock** — Unblock → user can sign in again.
5. **Double block** — Block again → success message (no false 404).
6. **Self** — Cannot block your own admin account (API 422).

## Open follow-ups (users)

| ID | Severity | Description |
|----|----------|-------------|
| U9 | Low | No **edit user** or **delete user** buttons in dashboard (APIs exist). |
| U10 | Low | No pagination on user list (OK until thousands of users). |
| U11 | Info | `lifetime_value` returned by API but not shown in table (only order count). |
| U12 | Info | `?include_admins=1` on list API to show admin rows if ever needed. |

---

# Admin API audit — Reviews (List / Approve / Delete)

**Audited:** 2026-05-28  
**Scope:** `api/admin/reviews/*` + Reviews tab in admin dashboard

## Endpoint map

| Action | Endpoint | Method | Auth | CSRF |
|--------|----------|--------|------|------|
| List | `api/admin/reviews/list.php` | GET | Admin session | No |
| Approve / Unapprove / Delete | `api/admin/reviews/moderate.php` | POST | Admin session | Yes |

Storefront only shows reviews where `is_approved = 1` (`product.php`, `bundles.php`, `api/catalog/detail.php`).

## Verdict

**Production-ready with fixes applied** — Moderation list, approve, and delete work; bundle reviews now visible.

## Fixed in this pass

| ID | Severity | Issue | Fix |
|----|----------|-------|-----|
| R1 | **High** | **Bundle reviews** missing from list (only `products` join). | `LEFT JOIN bundles`; `product_name` from product or bundle. |
| R2 | Medium | Approve/unapprove could **404** when status unchanged (`affected_rows` not checked but UX confusing). | Idempotent success if already approved/pending. |
| R3 | Medium | **Delete/approve** without verifying review exists. | Fetch review first; 404 if missing. |
| R4 | Low | No **search or status filter** in admin UI (API supported status only). | Search + Pending/Approved filter in dashboard; server `?q=` & `?status=`. |
| R5 | Low | Empty customer name showed blank. | Falls back to email or `Guest`. |
| R6 | Low | Toggle/delete errors not shown. | `try/catch` + toasts. |
| R7 | Low | List capped at 100 rows. | Raised to **200** (still no pagination UI). |

## What works well

1. **Admin gate** + **CSRF** on `moderate.php` (header via `fetchJson` + optional body token).
2. **List** — product/bundle name, rating stars, comment, approval status.
3. **Approve** — sets `is_approved`; unapproved reviews hidden on storefront.
4. **Delete** — hard delete from `reviews` table.
5. **Search** — product, bundle, email, name, comment.

## Manual test plan

1. **Reviews** tab loads (if DB has review rows).
2. **Approve** pending review → status Approved; visible on product/bundle page.
3. **Unapprove** → status Pending; hidden on storefront.
4. **Delete** → row removed from list.
5. **Search** by product name or email.
6. **Filter** Pending / Approved.
7. Bundle review (if any) shows bundle name + “Bundle” label.

## Open follow-ups (reviews)

| ID | Severity | Description |
|----|----------|-------------|
| R8 | Medium | DB default `is_approved = 1` — new reviews auto-publish without moderation. Consider default `0` for new submissions when review submit API is added. |
| R9 | Low | No public **submit review** API in codebase yet (reviews may be seeded manually). |
| R10 | Low | No pagination beyond 200 rows. |
| R11 | Info | Approving does not recalculate product `rating` column — storefront may use static product rating vs review average. |

---

# Admin API audit — Messages (List / Update)

**Audited:** 2026-05-28  
**Scope:** `api/admin/messages/*` + Messages tab in admin dashboard + `api/contact/send.php`

## Endpoint map

| Action | Endpoint | Method | Auth | CSRF |
|--------|----------|--------|------|------|
| List | `api/admin/messages/list.php` | GET | Admin session | No |
| Read / Unread / Archive / Delete | `api/admin/messages/update.php` | POST | Admin session | Yes |
| Contact form submit | `api/contact/send.php` | POST | Public | Yes |

## Verdict

**Production-ready with fixes applied** — CLI smoke test passed (`scripts/test-admin-messages-api.php`).

## Fixed in this pass

| ID | Severity | Issue | Fix |
|----|----------|-------|-----|
| M1 | **High** | `contact_messages` created without `is_read` / `archived` on fresh DB → list query fails. | Columns in `CREATE TABLE` + `addColumnIfMissing` runtime guard in `marketplace.php`. |
| M2 | Medium | **Update** did not verify message exists (silent no-op). | `_helpers.php` + 404 when missing. |
| M3 | Medium | **Mark read** not idempotent (confusing re-clicks). | Success if already read; same for archive/unread. |
| M4 | Low | List had no **prepare/execute** error handling. | Log + 500 JSON error. |
| M5 | Low | List capped at **100** rows. | Raised to **500**. |
| M6 | Low | Dashboard had no **search/filter** (API supported `q`, `status`). | Search + Unread/Read filter in Messages tab. |
| M7 | Low | **Archive** API unused; mark/delete errors not toasted. | Archive button; unified `messageAction` + toasts. |
| M8 | Low | No **Mark unread** or phone display. | `unread` action + phone in table. |

## What works well

1. **Admin gate** on list/update; **CSRF** on mutations (`fetchJson` sends `X-CSRF-Token`).
2. **Contact form** — validation, rate limit (3/hour/session), saves to DB, notification email (non-fatal).
3. **Inbox** — excludes archived; unread sorted first.
4. **Search** — name, email, subject, message body.

## Manual test plan

1. Submit message on **Contact** page while logged out.
2. Admin → **Messages** tab — row appears with unread dot.
3. **Search** by email or subject.
4. **Filter** Unread / Read.
5. **Mark Read** → dot removed; filter to Read shows row.
6. **Mark Unread** → dot returns.
7. **Archive** → row disappears from inbox.
8. **Delete** → permanent removal.

## Open follow-ups (messages)

| ID | Severity | Description |
|----|----------|-------------|
| M9 | Low | No **view full message** modal (preview truncated at 130 chars). |
| M10 | Low | No pagination beyond 500 rows. |
| M11 | Info | Archived messages not viewable in admin UI (only hidden from list). |

---

# Admin API audit — Overview / Stats

**Audited:** 2026-05-28  
**Scope:** `api/admin/stats/overview.php` + Overview & Analytics tabs

## Endpoint map

| Action | Endpoint | Method | Auth | CSRF |
|--------|----------|--------|------|------|
| Dashboard stats | `api/admin/stats/overview.php` | GET | Admin session | No |

## Verdict

**Production-ready with fixes applied** — CLI smoke test passed (`scripts/test-admin-overview-api.php`).

## Fixed in this pass

| ID | Severity | Issue | Fix |
|----|----------|-------|-----|
| S1 | **High** | **Top products** counted units from **all** orders (including cancelled / awaiting payment). | `INNER JOIN orders` with paid-status filter only. |
| S2 | **High** | **Bundle sales** missing from top products (products join only). | `LEFT JOIN bundles`; `item_type` label in UI. |
| S3 | Medium | **Pending orders** missed `paid` / `processing` (orders needing fulfillment). | Expanded pending status list. |
| S4 | Medium | **Analytics tab** revenue from client-side order list (wrong totals, included unpaid). | Uses `stats.revenue.today`, `month`, `avg_order` from API. |
| S5 | Low | No **prepare/execute** error handling on stat queries. | `_helpers.php` with logging + safe defaults. |
| S6 | Low | **Product count** included inactive / proxy rows. | Overview shows **active** products only. |
| S7 | Low | No **unread messages** on overview. | `messages.unread` + clickable stat card → Messages tab. |
| S8 | Low | Recent orders not clickable. | Row opens order detail modal. |

## What works well

1. **Six core KPIs** — customers, active products, orders, revenue, pending, low stock.
2. **Month-over-month** change strings for users, orders, revenue.
3. **Recent orders** (last 6) and **top sellers** (last 8, paid orders only).
4. **Quick actions** — add product, manage orders, view store, add bundle.

## Manual test plan

1. Open admin → **Overview** — stat cards populate (not stuck on “Loading”).
2. **Total Revenue** matches sum of paid/processing/shipped/delivered orders (not awaiting payment).
3. **Top Products** — bundle lines show “Bundle · …” when bundle orders exist.
4. **Recent Orders** — click row → order detail modal opens.
5. **Unread Messages** card → switches to Messages tab.
6. **Analytics** tab — today/month revenue matches overview API (not inflated by unpaid carts).

## Open follow-ups (overview)

| ID | Severity | Description |
|----|----------|-------------|
| S9 | Low | Low-stock / inventory activity returned by API but not shown on overview UI. |
| S10 | Low | `bundles` / `categories` counts in API not displayed on overview cards. |
| S11 | Info | Overview uses lifetime AOV; Analytics tab uses dedicated API with month AOV. |

---

# Admin API audit — Analytics

**Audited:** 2026-05-28  
**Scope:** `api/admin/stats/analytics.php` + Analytics tab (previously reused `overview.php`)

## Endpoint map

| Action | Endpoint | Method | Auth | CSRF |
|--------|----------|--------|------|------|
| Analytics metrics | `api/admin/stats/analytics.php` | GET | Admin session | No |
| Overview (separate) | `api/admin/stats/overview.php` | GET | Admin session | No |

Shared helpers: `api/admin/stats/_helpers.php` (paid status list, SQL helpers).

## Verdict

**Production-ready** — Dedicated endpoint; CLI smoke test passed (`scripts/test-admin-analytics-api.php`).

## Fixed in this pass

| ID | Severity | Issue | Fix |
|----|----------|-------|-----|
| A1 | **High** | No dedicated analytics API — tab called heavy `overview.php`. | New `analytics.php` with analytics-only payload. |
| A2 | **High** | **Conversion** = all orders ÷ customers (included unpaid/cancelled). | **Paying customers** ÷ total customers (`DISTINCT user_id` on paid orders). |
| A3 | Medium | **Avg order** used lifetime revenue ÷ all paid orders. | **This month** revenue ÷ paid orders this month. |
| A4 | Low | Errors only logged to console. | Toast + table error row. |
| A5 | Low | No context on stat cards. | Subtitles: paid orders today/month, MoM change, conversion hint. |

## Response shape (`data`)

- `revenue` — `today`, `month`, `last_month`, `total`, `change`, `avg_order_month`, `avg_order_lifetime`
- `orders` — `paid_today`, `paid_month`, `paid_total`, `all_month`
- `customers` — `total`, `with_paid_order`, `conversion_rate`
- `top_products` — up to 10 rows (paid orders only; products + bundles)

Revenue statuses: `paid`, `processing`, `shipped`, `delivered` (plus legacy casing).

## Manual test plan

1. Admin → **Analytics** — cards load (not stuck on ₹0 / —).
2. **Today's revenue** only includes orders paid today.
3. **This month** subtitle shows MoM % vs last month.
4. **Avg order** matches month revenue ÷ paid orders this month.
5. **Conversion** shows % and “X of Y customers placed a paid order”.
6. **Top Sellers** lists bundles with “Bundle ·” prefix when applicable.

## Open follow-ups (analytics)

| ID | Severity | Description |
|----|----------|-------------|
| A6 | Low | No date-range picker or charts (daily revenue series). |
| A7 | Info | Guest checkout orders excluded from conversion numerator (no `user_id`). |
