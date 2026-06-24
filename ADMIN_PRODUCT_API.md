# Admin Product APIs — Logic + Postman Testing Guide

**Files Covered:**
- `api/admin/_admin.php` — admin auth + shared helpers
- `api/admin/product/save.php` — create & edit
- `api/admin/product/get.php` — fetch one product
- `api/admin/product/list.php` — list all products
- `api/admin/product/delete.php` — delete or archive
- `api/admin/product/toggle_status.php` — show/hide
- `api/admin/product/duplicate.php` — clone a product

---

## HOW ADMIN AUTH WORKS (read this first)

Every admin API file starts with:
```php
require_once __DIR__ . '/../_admin.php';
```

`_admin.php` does three things:
1. Loads `_bootstrap.php` → which loads `config.php` → session + DB + CSRF
2. Calls `requireAdmin()` → checks `$_SESSION['admin_id']` exists, else 401
3. Defines helper functions available to all admin APIs

**This means: you must be logged in as admin before any of these APIs will work.**

### Helper Functions Defined in `_admin.php`

| Function | What it does |
|---|---|
| `adminInput()` | Reads `$_POST` if multipart form, else reads JSON body |
| `adminBool($val, $default)` | Converts "1","true","on","yes" → 1, anything else → 0 |
| `adminNullableFloat($val)` | Returns float or null (for optional price fields) |
| `adminUploadImages('field')` | Handles file uploads, validates MIME, saves to `img/products/`, returns path array |
| `adminRecordInventory(...)` | Logs stock changes to `inventory_movements` table |

---

## POSTMAN SETUP (do this once)

### Step 1: Get your CSRF token
Every POST request needs a CSRF token. Get it from any page:

**Request:**
```
GET http://localhost/Shop/UX_SHOP/UX_Shop_New/index.php
```
Look at the HTML — find:
```html
<meta name="csrf-token" content="abc123...64chars...">
```
Copy that value.

### Step 2: Set Postman headers for every admin POST
```
Content-Type    : application/json
X-CSRF-TOKEN   : <paste your token here>
Cookie         : PHPSESSID=<your session ID>
```

### Step 3: Get your session cookie
1. Login to admin panel in browser: `http://localhost/.../admin/admin-login.php`
2. Open DevTools → Application → Cookies → copy `PHPSESSID` value
3. Paste into Postman Cookie header

> **Tip:** In Postman, go to Settings → turn ON "Automatically follow redirects" and "Send cookies". Then browse to admin login in Postman browser and login — it will store the session for you.

---

## API 1 — LIST PRODUCTS

**File:** `api/admin/product/list.php`

```
GET /api/admin/product/list.php
GET /api/admin/product/list.php?q=figma
GET /api/admin/product/list.php?category=UI+Kit
GET /api/admin/product/list.php?status=1
GET /api/admin/product/list.php?q=template&status=0
```

**Method:** GET (no CSRF needed)
**Auth:** Admin session required

### Query Parameters
0
| Param | Type | Example | What it filters |
|---|---|---|---|
| `q` | string | `figma` | Searches name, SKU, category, tags (LIKE %q%) |
| `category` | string | `UI Kit` | Exact category match |
| `status` | 0 or 1 | `1` | 1 = active only, 0 = inactive only, omit = all |

### Logic Inside

```php
// Builds WHERE clause dynamically based on what you send:
$where = ['1=1'];  // always true — base condition
if ($q)        $where[] = '(p.name LIKE ? OR p.sku LIKE ? OR p.category LIKE ? OR p.tags LIKE ?)';
if ($category) $where[] = 'p.category = ?';
if ($status)   $where[] = 'p.is_active = ?';

// Also calculates extra stats via subqueries:
buyer_count   = how many unique paying users bought this product
download_total = total downloads served across all orders
```

### Success Response
```json
{
  "status": "success",
  "message": "Products loaded.",
  "data": [
    {
      "id": 5,
      "name": "Figma Dashboard Kit",
      "slug": "figma-dashboard-kit",
      "sku": "FDK-001",
      "category": "UI Kit",
      "price": "899.00",
      "old_price": "1299.00",
      "stock": 0,
      "is_active": 1,
      "is_featured": 0,
      "is_free": 0,
      "available_type": "digital",
      "buyer_count": 12,
      "download_total": 45,
      "image": "img/products/abc123.webp",
      "additional_images": "[\"img/products/xyz.webp\"]",
      "created_at": "2026-06-01 10:00:00"
    }
  ]
}
```

---

## API 2 — GET ONE PRODUCT

**File:** `api/admin/product/get.php`

```
GET /api/admin/product/get.php?id=5
```

**Method:** GET
**Auth:** Admin session required

### Logic Inside

```php
$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) → 422 error

SELECT * FROM products WHERE id = ?
// Also parses JSON fields for you:
$row['additional_images_list'] = json_decode($row['additional_images']) // array
$row['custom_fields_parsed']   = json_decode($row['custom_fields'])     // array
```

### Success Response
```json
{
  "status": "success",
  "message": "Product loaded.",
  "data": {
    "id": 5,
    "name": "Figma Dashboard Kit",
    "slug": "figma-dashboard-kit",
    "sku": "FDK-001",
    "description": "A complete dashboard template...",
    "whats_included": "50+ components, 10 screens",
    "file_specification": "Figma, 1920x1080",
    "category": "UI Kit",
    "tags": "figma, dashboard, ui",
    "price": "899.00",
    "old_price": "1299.00",
    "commercial_price": null,
    "stock": 0,
    "rating": "4.50",
    "available_type": "digital",
    "is_active": 1,
    "is_featured": 0,
    "is_free": 0,
    "high_resolution": "Yes",
    "compatible_software": "Figma",
    "software_version": "2024",
    "files_included": ".fig, .pdf",
    "grid_columns": "2",
    "layout_type": "Grid",
    "license_type": "Standard",
    "image": "img/products/abc123.webp",
    "additional_images": "[\"img/products/xyz.webp\"]",
    "additional_images_list": ["img/products/xyz.webp"],
    "custom_fields": "[{\"label\":\"Screens\",\"value\":\"10\"}]",
    "custom_fields_parsed": [{"label": "Screens", "value": "10"}]
  }
}
```

**Use case:** Call this before showing the edit form — pre-fill all fields with this response.

---

## API 3 — CREATE PRODUCT (save.php with no id)

**File:** `api/admin/product/save.php`

```
POST /api/admin/product/save.php
```

**Method:** POST
**Auth:** Admin session + CSRF token
**Content-Type:** `application/json` (or `multipart/form-data` if uploading images)

### How the API decides CREATE vs UPDATE

```php
$id = (int) ($input['id'] ?? 0);

if ($id > 0) {
    // UPDATE path — existing product
} else {
    // CREATE path — new product (id not sent or sent as 0)
}
```

### Request Body (JSON — no image upload)

```json
{
  "name": "Figma Dashboard Kit",
  "category": "UI Kit",
  "description": "A complete dashboard template with 50+ components.",
  "whats_included": "50+ components, 10 screens",
  "file_specification": "Figma source file, 1920x1080",
  "sku": "FDK-001",
  "tags": "figma, dashboard, ui, template",
  "price": 899,
  "old_price": 1299,
  "commercial_price": null,
  "stock": 0,
  "rating": 4.5,
  "available_type": "digital",
  "is_active": 1,
  "is_featured": 0,
  "is_free": 0,
  "high_resolution": "Yes",
  "compatible_software": "Figma",
  "software_version": "2024",
  "files_included": ".fig, .pdf",
  "grid_columns": "2",
  "layout_type": "Grid",
  "license_type": "Standard",
  "slug": "figma-dashboard-kit",
  "image_path": "img/products/existing.webp",
  "additional_images": "[\"img/products/img2.webp\",\"img/products/img3.webp\"]",
  "custom_fields": "[{\"label\":\"Screens\",\"value\":\"10\"},{\"label\":\"Components\",\"value\":\"50+\"}]"
}
```

### Required Fields

| Field | Required | Notes |
|---|---|---|
| `name` | YES | Product name — cannot be empty |
| `category` | no | Default: "Uncategorized" |
| `price` | no | Default: 0. Set to 0 if is_free = true |
| `available_type` | no | `"digital"`, `"physical"`, or `"both"`. Default: `"digital"` |

### Field-by-Field Logic

**`name`**
```php
$name = trim((string) ($input['name'] ?? ''));
if ($name === '') sendResponse('error', 'Product name is required.', null, 422);
```
The only truly required field. Everything else has a default.

**`is_free` — forces other fields**
```php
if ($isFree) {
    $price = 0.0;         // price locked to 0
    $oldPrice = null;     // no old price
    $commercialPrice = null;
    if ($availableType === 'physical') $availableType = 'digital';
    // free products can only be digital
}
```
Sending `"is_free": 1` with `"price": 999` → price is stored as 0.

**`slug`** — auto-generated if not provided
```php
$slug = slugify($input['slug'] ?? $name);
// "Figma Dashboard Kit" → "figma-dashboard-kit"
```

**`custom_fields`** — JSON array of label/value pairs
```json
// Send as a JSON string:
"custom_fields": "[{\"label\":\"Screens\",\"value\":\"10\"}]"

// What gets stored in DB (cleaned — empty label/value pairs removed):
[{"label": "Screens", "value": "10"}]
```

**`available_type`** — validated against allowlist
```php
if (!in_array($availableType, ['physical', 'digital', 'both'], true)) {
    $availableType = 'digital'; // default if invalid value sent
}
```

**`adminBool()` fields** — how to send boolean values
```php
// adminBool() accepts: "1", "true", "on", "yes" → stores 1
//                      anything else              → stores 0
// So in JSON you can send:
"is_active": 1        // works
"is_active": true     // works
"is_active": "true"   // works
"is_active": 0        // stores 0
```

**`old_price`, `commercial_price`** — nullable
```php
// adminNullableFloat() → null if empty string or null
"old_price": null     // stored as NULL in DB
"old_price": ""       // stored as NULL in DB
"old_price": 1299     // stored as 1299.00
```

**Image handling (JSON mode — no file upload)**
```php
// Use existing_image or image_path to set the main image:
"image_path": "img/products/myfile.webp"

// Use additional_images as a JSON string:
"additional_images": "[\"img/products/img2.webp\"]"
```

**Image handling (multipart mode — file upload)**
```
Content-Type: multipart/form-data
Field: image        → main product image file
Field: media[]      → additional images (multiple files)
```
Files are validated:
- Max size: 5MB
- Allowed types: jpg, jpeg, png, webp, gif
- MIME type verified (not just extension)
- Saved to `img/products/{random_hex}.{ext}`

**Stock recording**
```php
// After successful INSERT:
adminRecordInventory($conn, 'product', $newId, 0, $stock, 'Admin product create');
// Logs: item_type='product', item_id=newId, change=+stock, stock_before=0, stock_after=stock
// Only logs if stock > 0 (before !== after)
```

### Success Response
```json
{
  "status": "success",
  "message": "Product created.",
  "data": {
    "id": 12,
    "image": "img/products/abc123.webp"
  }
}
```

### Error Responses

| Situation | HTTP | Response |
|---|---|---|
| `name` is empty | 422 | `"Product name is required."` |
| Not logged in as admin | 401 | `"Unauthorized: Admin access required"` |
| CSRF token missing/wrong | 403 | `"Invalid CSRF token"` |
| Not a POST request | 405 | `"Method not allowed."` |
| DB error on insert | 500 | `"Could not create product: ..."` |
| Image > 5MB | 422 | `"Images must be 5MB or smaller."` |
| Invalid image type | 422 | `"Unsupported image type."` |

---

## API 4 — EDIT PRODUCT (save.php with id)

**File:** `api/admin/product/save.php` (same file as create)

```
POST /api/admin/product/save.php
```

**Difference from create:** include `"id": 5` in the body.

### Request Body
```json
{
  "id": 5,
  "name": "Figma Dashboard Kit v2",
  "price": 999,
  "is_active": 1,
  "category": "UI Kit"
}
```
All other fields optional — if you don't send them, they get their default/empty values. **This is a full replace, not a partial patch.** If you don't send `description`, it gets stored as empty string.

### What happens differently in UPDATE path

**Step 1: Fetch existing product**
```php
$before = SELECT stock, image, additional_images FROM products WHERE id = ?
if (!$before) → 404 error
```

**Step 2: Preserve image if not changing it**
```php
// If no new image uploaded AND no image_path sent → keep existing image
if (empty($imageUploads) && $mainImage === '') {
    $mainImage = $before['image'] ?: 'img/poster.webp';
}
```

**Step 3: Preserve additional_images if not changing them**
```php
// If no new additional_images sent → keep existing ones
if (empty($input['additional_images']) && empty($additionalImages)) {
    $additionalImages = json_decode($before['additional_images']) ?: [];
}
```

**Step 4: UPDATE all columns**
```sql
UPDATE products SET name=?, slug=?, sku=?, description=?, ... WHERE id=?
```

**Step 5: Record stock change**
```php
adminRecordInventory($conn, 'product', $id, $before['stock'], $stock, 'Admin product update');
// If stock changed from 10 to 25 → logs change=+15
// If stock unchanged → no log entry written
```

### Success Response
```json
{
  "status": "success",
  "message": "Product updated.",
  "data": {
    "id": 5,
    "image": "img/products/abc123.webp"
  }
}
```

---

## API 5 — DELETE PRODUCT

**File:** `api/admin/product/delete.php`

```
POST /api/admin/product/delete.php
```

**Method:** POST + CSRF
**Body:** `{ "id": 5 }`

### Two Different Delete Behaviors

This is the most important logic in this API — it chooses between SOFT DELETE and HARD DELETE based on order history.

```
Does this product have any orders?
  YES → SOFT DELETE (archive)
    UPDATE products SET is_active = 0 WHERE id = 5
    Product hidden from shop but order history preserved
    Response: { "action": "archived" }

  NO → HARD DELETE (permanent)
    BEGIN TRANSACTION
      1. Find all digital_resources for this product
      2. Delete their files from storage (Cloudflare R2 or local)
      3. DELETE FROM digital_resources WHERE product_id = 5
      4. DELETE FROM products WHERE id = 5
    COMMIT
    Response: { "action": "deleted" }
```

**Why this matters:**
- If you hard-delete a product that has orders, the `order_items.product_id` foreign key would break — old orders would have no product to reference.
- Soft delete keeps the record (is_active = 0) so order history is intact.
- Customers can't see inactive products in the shop.

### Request Body
```json
{ "id": 5 }
```

### Responses

**Soft delete (had orders):**
```json
{
  "status": "success",
  "message": "Product archived (has existing orders).",
  "data": { "action": "archived" }
}
```

**Hard delete (no orders):**
```json
{
  "status": "success",
  "message": "Product deleted.",
  "data": { "action": "deleted" }
}
```

**Not found:**
```json
{
  "status": "error",
  "message": "Product not found.",
  "data": null
}
```
HTTP status: 404

---

## API 6 — TOGGLE STATUS (Show/Hide)

**File:** `api/admin/product/toggle_status.php`

```
POST /api/admin/product/toggle_status.php
```

Simpler than delete. Just flips `is_active` without touching anything else.

### Request Body
```json
{ "id": 5, "is_active": 0 }
```

| `is_active` value | Sends | Result |
|---|---|---|
| `1` or `"true"` | Activate | Product shows in shop |
| `0` or `"false"` | Deactivate | Product hidden from shop |

### Logic
```php
// If already at that state → returns success with no change:
if ($row['is_active'] === $active) {
    sendResponse('success', 'Product is already active.');
}
// Otherwise:
UPDATE products SET is_active = ? WHERE id = ?
```

### Responses
```json
{ "status": "success", "message": "Product activated." }
{ "status": "success", "message": "Product archived." }
{ "status": "success", "message": "Product is already active." }
```

---

## API 7 — DUPLICATE PRODUCT

**File:** `api/admin/product/duplicate.php`

```
POST /api/admin/product/duplicate.php
```

### Request Body
```json
{ "id": 5 }
```

### What it does
```php
// Reads the original product:
SELECT * FROM products WHERE id = 5

// Makes a copy with these changes:
name = "Figma Dashboard Kit Copy"
slug = "figma-dashboard-kit-copy-a3f9"  // random suffix to avoid collision
sku  = "FDK-001-COPY"
is_active = 0    // duplicated product is HIDDEN by default (you must activate it)
is_featured = 0  // not featured by default

// Everything else is copied exactly:
price, description, images, category, tags, custom_fields, etc.
```

### Why `is_active = 0` on the copy?
Prevents accidentally publishing an incomplete duplicate. You edit the copy, then activate it.

### Success Response
```json
{
  "status": "success",
  "message": "Product duplicated.",
  "data": { "id": 13 }
}
```

---

## POSTMAN COLLECTION — Ready-to-Use Requests

### Collection Variables (set these once)
```
base_url   : http://localhost/Shop/UX_SHOP/UX_Shop_New
csrf_token : <get from page meta tag>
```

---

### Request 1: List all products
```
Method  : GET
URL     : {{base_url}}/api/admin/product/list.php
Auth    : Session cookie
```

### Request 2: List active products only
```
Method  : GET
URL     : {{base_url}}/api/admin/product/list.php?status=1
```

### Request 3: Search products
```
Method  : GET
URL     : {{base_url}}/api/admin/product/list.php?q=figma&category=UI+Kit
```

### Request 4: Get one product
```
Method  : GET
URL     : {{base_url}}/api/admin/product/get.php?id=5
```

### Request 5: Create product (minimal)
```
Method  : POST
URL     : {{base_url}}/api/admin/product/save.php
Headers :
  Content-Type  : application/json
  X-CSRF-TOKEN  : {{csrf_token}}
Body (raw JSON):
{
  "name": "Test Product",
  "price": 499,
  "available_type": "digital",
  "is_active": 1
}
```

### Request 6: Create product (full fields)
```
Method  : POST
URL     : {{base_url}}/api/admin/product/save.php
Headers :
  Content-Type  : application/json
  X-CSRF-TOKEN  : {{csrf_token}}
Body (raw JSON):
{
  "name": "Figma Dashboard Kit",
  "category": "UI Kit",
  "description": "A professional dashboard template with 50+ components.",
  "whats_included": "50 components, 10 screens, style guide",
  "file_specification": "Figma 2024, 1920x1080",
  "sku": "FDK-001",
  "tags": "figma, dashboard, ui, dark",
  "price": 899,
  "old_price": 1299,
  "commercial_price": null,
  "stock": 0,
  "rating": 4.5,
  "available_type": "digital",
  "is_active": 1,
  "is_featured": 1,
  "is_free": 0,
  "high_resolution": "Yes",
  "compatible_software": "Figma",
  "software_version": "2024",
  "files_included": ".fig, .pdf guide",
  "grid_columns": "2",
  "layout_type": "Grid",
  "license_type": "Standard",
  "custom_fields": "[{\"label\":\"Screens\",\"value\":\"10\"},{\"label\":\"Components\",\"value\":\"50+\"}]",
  "additional_images": "[]"
}
```

### Request 7: Create FREE product
```json
{
  "name": "Free Icon Pack",
  "category": "Icons",
  "description": "100 free icons",
  "is_free": 1,
  "available_type": "digital",
  "is_active": 1
}
```
Note: even if you send `"price": 999`, it becomes 0 because `is_free = 1`.

### Request 8: Edit product (update price and description)
```
Method  : POST
URL     : {{base_url}}/api/admin/product/save.php
Headers : Content-Type: application/json, X-CSRF-TOKEN: {{csrf_token}}
Body:
{
  "id": 5,
  "name": "Figma Dashboard Kit",
  "price": 999,
  "description": "Updated description here.",
  "category": "UI Kit",
  "available_type": "digital",
  "is_active": 1
}
```
**Important:** This is a full replace. Any field not included gets its default value. Always send all fields when editing — fetch with GET first, modify what you need, then send the full object back.

### Request 9: Delete product
```
Method  : POST
URL     : {{base_url}}/api/admin/product/delete.php
Headers : Content-Type: application/json, X-CSRF-TOKEN: {{csrf_token}}
Body:
{ "id": 5 }
```

### Request 10: Hide product (deactivate)
```
Method  : POST
URL     : {{base_url}}/api/admin/product/toggle_status.php
Headers : Content-Type: application/json, X-CSRF-TOKEN: {{csrf_token}}
Body:
{ "id": 5, "is_active": 0 }
```

### Request 11: Show product (activate)
```json
{ "id": 5, "is_active": 1 }
```

### Request 12: Duplicate product
```
Method  : POST
URL     : {{base_url}}/api/admin/product/duplicate.php
Headers : Content-Type: application/json, X-CSRF-TOKEN: {{csrf_token}}
Body:
{ "id": 5 }
```

---

## COMPLETE FIELD REFERENCE

All fields accepted by `save.php`:

| Field | Type | Required | Default | Notes |
|---|---|---|---|---|
| `id` | int | for edit | 0 | Include to UPDATE, omit to CREATE |
| `name` | string | YES | — | Cannot be empty |
| `slug` | string | no | auto from name | URL-friendly version of name |
| `sku` | string | no | "" | Stock Keeping Unit — your product code |
| `category` | string | no | "Uncategorized" | |
| `description` | string | no | "" | Long description |
| `whats_included` | string | no | "" | What files/items come with it |
| `file_specification` | string | no | "" | Technical specs (resolution, format) |
| `tags` | string | no | "" | Comma separated |
| `price` | float | no | 0 | Forced to 0 if is_free=1 |
| `old_price` | float/null | no | null | Struck-through original price |
| `commercial_price` | float/null | no | null | Commercial license price |
| `stock` | int | no | 0 | Physical stock count |
| `rating` | float | no | 4.5 | 0–5 |
| `available_type` | string | no | "digital" | "digital", "physical", or "both" |
| `is_active` | bool | no | 1 | 1=visible in shop, 0=hidden |
| `is_featured` | bool | no | 0 | Featured in homepage |
| `is_free` | bool | no | 0 | If 1: forces price=0 and digital |
| `high_resolution` | string | no | "" | e.g. "Yes", "300dpi" |
| `compatible_software` | string | no | "" | e.g. "Figma, Adobe XD" |
| `software_version` | string | no | "" | e.g. "2024" |
| `files_included` | string | no | "" | e.g. ".fig, .pdf" |
| `grid_columns` | string | no | "" | Layout hint for frontend |
| `layout_type` | string | no | "" | e.g. "Grid", "List" |
| `license_type` | string | no | "" | e.g. "Standard", "Extended" |
| `image_path` | string | no | "img/poster.webp" | Existing image path |
| `existing_image` | string | no | — | Same as image_path |
| `additional_images` | JSON string | no | "[]" | Array of image paths |
| `custom_fields` | JSON string | no | "[]" | Array of {label, value} objects |

---

## ERROR CODES QUICK REFERENCE

| HTTP | Meaning | Common cause |
|---|---|---|
| 200 | Success | Request worked |
| 400 | Bad request | Wrong body format |
| 401 | Unauthorized | Not logged in as admin |
| 403 | Forbidden | CSRF token missing or wrong |
| 404 | Not found | Product ID doesn't exist |
| 405 | Method not allowed | Sent GET instead of POST |
| 422 | Validation error | name is empty, invalid available_type |
| 500 | Server error | DB query failed |

---

## FLOW SUMMARY: HOW THESE APIS CONNECT

```
Admin opens product list page
    │
    GET /api/admin/product/list.php
    ← returns all products
    │
Admin clicks "Add Product"
    │
    Admin fills form → clicks Save
    │
    POST /api/admin/product/save.php  (no id)
    ← returns { id: 12 }
    │
Admin opens product to edit
    │
    GET /api/admin/product/get.php?id=12   ← pre-fill form
    │
    Admin changes price → clicks Update
    │
    POST /api/admin/product/save.php  (id: 12)
    ← returns { id: 12, image: "..." }
    │
Admin wants to hide product without deleting
    │
    POST /api/admin/product/toggle_status.php  { id:12, is_active:0 }
    │
Admin wants to clone product
    │
    POST /api/admin/product/duplicate.php  { id:12 }
    ← returns { id: 13 }  ← new copy, is_active=0
    │
Admin wants to delete product
    │
    POST /api/admin/product/delete.php  { id:12 }
    ← "archived" if has orders
    ← "deleted"  if no orders (also deletes files from storage)
```
