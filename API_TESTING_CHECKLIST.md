# API Testing Checklist
**Project:** UX Pacific Shop  
**Last updated:** 2026-05-28

Use these curl examples against `http://localhost/Shop/UX_SHOP/UX_Shop_New`

---

## Step 0: Get CSRF Token

```bash
# Store CSRF token and session cookie for all subsequent requests
curl -c cookies.txt -s http://localhost/Shop/UX_SHOP/UX_Shop_New/api/auth/csrf.php | jq .
# Expected: {"status":"success","message":"CSRF token ready.","data":{"token":"<hex>"}}
CSRF=$(curl -c cookies.txt -s http://localhost/Shop/UX_SHOP/UX_Shop_New/api/auth/csrf.php | python -c "import sys,json; print(json.load(sys.stdin)['data']['token'])")
```

---

## 1. Auth Endpoints

### 1.1 Signup
```bash
# ✅ Valid signup
curl -b cookies.txt -c cookies.txt -X POST \
  -H "Content-Type: application/json" \
  -d "{\"firstName\":\"Test\",\"lastName\":\"User\",\"email\":\"test@example.com\",\"password\":\"password123\",\"csrf_token\":\"$CSRF\"}" \
  http://localhost/Shop/UX_SHOP/UX_Shop_New/api/auth/signup.php
# Expected: {"status":"success","message":"Account created successfully.","data":{"user_id":N}}

# ❌ Duplicate email → 409
# ❌ Short password (< 8) → 422
# ❌ Invalid email → 422
# ❌ Missing CSRF → 403
# ❌ GET request → 405
```

### 1.2 Login
```bash
# ✅ Valid login
curl -b cookies.txt -c cookies.txt -X POST \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"test@example.com\",\"password\":\"password123\",\"csrf_token\":\"$CSRF\"}" \
  http://localhost/Shop/UX_SHOP/UX_Shop_New/api/auth/login.php
# Expected: {"status":"success",...,"data":{"user":{...},"csrf_token":"<new_token>"}}
# IMPORTANT: Extract the new csrf_token from response and use it for subsequent requests

# ❌ Wrong password → 401
# ❌ Blocked user → 403
# ❌ GET request → 405
```

### 1.3 Session
```bash
curl -b cookies.txt http://localhost/Shop/UX_SHOP/UX_Shop_New/api/auth/session.php
# Expected (logged in): {"status":"success","data":{"user":{"id":N,...}}}
```

### 1.4 Logout
```bash
curl -b cookies.txt -c cookies.txt -X POST \
  -H "X-CSRF-TOKEN: $CSRF" \
  http://localhost/Shop/UX_SHOP/UX_Shop_New/api/auth/logout.php
# Expected: {"status":"success","message":"Signed out successfully."}
```

### 1.5 Forgot Password
```bash
curl -b cookies.txt -X POST \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"test@example.com\",\"csrf_token\":\"$CSRF\"}" \
  http://localhost/Shop/UX_SHOP/UX_Shop_New/api/auth/forgot-password.php
# Expected: {"status":"success","message":"If that email is registered, a reset link has been sent."}
# Check logs/app_errors.log for reset link if email not configured
```

---

## 2. User Endpoints (must be logged in)

### 2.1 Get Profile
```bash
curl -b cookies.txt http://localhost/Shop/UX_SHOP/UX_Shop_New/api/user/profile.php
# Expected: {"status":"success","data":{"id":N,"email":"...","firstName":"...","lastName":"...",...}}
```

### 2.2 Update Profile
```bash
curl -b cookies.txt -X POST \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: $CSRF" \
  -d '{"first_name":"John","last_name":"Doe","phone":"9876543210"}' \
  http://localhost/Shop/UX_SHOP/UX_Shop_New/api/user/update_profile.php
# Expected: {"status":"success","message":"Profile saved."}
```

### 2.3 Change Password
```bash
curl -b cookies.txt -X POST \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: $CSRF" \
  -d '{"current_password":"password123","new_password":"newpass456","confirm_password":"newpass456"}' \
  http://localhost/Shop/UX_SHOP/UX_Shop_New/api/user/update_password.php
# Expected: {"status":"success","message":"Password updated successfully."}
# ❌ Wrong current password → 401
# ❌ Short new password → 422
# ❌ Mismatch → 422
```

---

## 3. Address Endpoints

```bash
# Add address
curl -b cookies.txt -X POST \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: $CSRF" \
  -d '{"firstName":"John","lastName":"Doe","address":"123 Main St","city":"Mumbai","state":"Maharashtra","zip":"400001","country":"IN","phone":"9876543210"}' \
  http://localhost/Shop/UX_SHOP/UX_Shop_New/api/address/add.php
# Expected: {"status":"success","data":{"id":N}}

# List addresses
curl -b cookies.txt http://localhost/Shop/UX_SHOP/UX_Shop_New/api/address/get.php
```

---

## 4. Cart Endpoints

```bash
# Add product (must use existing active product ID)
curl -b cookies.txt -X POST \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: $CSRF" \
  -d '{"product_id":1,"quantity":2,"available_type":"digital"}' \
  http://localhost/Shop/UX_SHOP/UX_Shop_New/api/cart/add.php
# Expected: {"status":"success","message":"Item added to cart.","data":{"product_id":1}}

# ❌ Fake product (no product_id, with name/price) → 404 (security fix)
curl -b cookies.txt -X POST \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: $CSRF" \
  -d '{"name":"Fake Product","price":0}' \
  http://localhost/Shop/UX_SHOP/UX_Shop_New/api/cart/add.php
# Expected: {"status":"error","message":"Product not found or unavailable.","data":null} (HTTP 404)

# List cart
curl -b cookies.txt http://localhost/Shop/UX_SHOP/UX_Shop_New/api/cart/list.php

# Update quantity
curl -b cookies.txt -X POST \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: $CSRF" \
  -d '{"product_id":1,"quantity":3,"available_type":"digital"}' \
  http://localhost/Shop/UX_SHOP/UX_Shop_New/api/cart/update.php

# Remove item
curl -b cookies.txt -X POST \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: $CSRF" \
  -d '{"product_id":1,"available_type":"digital"}' \
  http://localhost/Shop/UX_SHOP/UX_Shop_New/api/cart/remove.php
```

---

## 5. Order Endpoints

```bash
# Create order (add item to cart first)
curl -b cookies.txt -X POST \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: $CSRF" \
  -d '{
    "items":[{"id":1,"item_type":"product","quantity":1,"available_type":"digital"}],
    "paymentMethod":"cod",
    "shipping":{"firstName":"John","address":"123 Main","city":"Mumbai"}
  }' \
  http://localhost/Shop/UX_SHOP/UX_Shop_New/api/order/create.php
# Expected: {"status":"success","data":{"orderNumber":"UXP-YYYYMMDD-XXXXXX","orderId":N,...}}

# Get orders
curl -b cookies.txt http://localhost/Shop/UX_SHOP/UX_Shop_New/api/order/get.php
# Expected: array of orders, each with items[] loaded via JOIN (not N+1)
```

---

## 6. Payment Endpoints (requires Razorpay keys in .env)

```bash
# Create Razorpay order (order must exist and be Pending/awaiting_payment)
curl -b cookies.txt -X POST \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: $CSRF" \
  -d '{"order_id":1}' \
  http://localhost/Shop/UX_SHOP/UX_Shop_New/api/payment/razorpay-create-order.php
# Expected (if keys configured): {"status":"success","data":{"razorpay_order_id":"order_XXX","amount_paise":N,"key_id":"rzp_..."}}
# Expected (if not configured): 503

# Verify payment (signature from Razorpay JS callback)
curl -b cookies.txt -X POST \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: $CSRF" \
  -d '{"order_id":1,"razorpay_order_id":"order_XXX","razorpay_payment_id":"pay_XXX","razorpay_signature":"<hmac>"}' \
  http://localhost/Shop/UX_SHOP/UX_Shop_New/api/payment/razorpay-verify.php
```

---

## 7. Admin Endpoints (login as admin first)

```bash
# Admin login
curl -b cookies.txt -c cookies.txt -X POST \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"admin@example.com\",\"password\":\"adminpass\",\"csrf_token\":\"$CSRF\"}" \
  http://localhost/Shop/UX_SHOP/UX_Shop_New/api/auth/admin-login.php
# Update $CSRF with returned csrf_token

# Dashboard stats
curl -b cookies.txt http://localhost/Shop/UX_SHOP/UX_Shop_New/api/admin/stats/overview.php

# Order list (paginated)
curl -b cookies.txt "http://localhost/Shop/UX_SHOP/UX_Shop_New/api/admin/order/list.php?page=1&limit=10"
curl -b cookies.txt "http://localhost/Shop/UX_SHOP/UX_Shop_New/api/admin/order/list.php?q=UXP-&status=Pending"

# Toggle product status
curl -b cookies.txt -X POST \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: $CSRF" \
  -d '{"id":1,"is_active":0}' \
  http://localhost/Shop/UX_SHOP/UX_Shop_New/api/admin/product/toggle_status.php

# Block user
curl -b cookies.txt -X POST \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: $CSRF" \
  -d '{"id":5,"action":"block"}' \
  http://localhost/Shop/UX_SHOP/UX_Shop_New/api/admin/user/block.php
# ❌ Self-block → 422
```

---

## 8. Security Tests

```bash
# ❌ SQL injection attempt in search
curl "http://localhost/Shop/UX_SHOP/UX_Shop_New/api/catalog/list.php?q='; DROP TABLE products;--"
# Expected: returns empty results, no error

# ❌ Access admin endpoint without admin session
curl http://localhost/Shop/UX_SHOP/UX_Shop_New/api/admin/stats/overview.php
# Expected: {"status":"error","message":"Unauthorized: Admin access required"}

# ❌ Access .env directly
curl http://localhost/Shop/UX_SHOP/UX_Shop_New/.env
# Expected: 403 Forbidden (blocked by .htaccess)

# ❌ Access logs directory
curl http://localhost/Shop/UX_SHOP/UX_Shop_New/logs/app_errors.log
# Expected: 403 Forbidden

# ❌ CSRF bypass (no token)
curl -b cookies.txt -X POST \
  -H "Content-Type: application/json" \
  -d '{"product_id":1,"quantity":1}' \
  http://localhost/Shop/UX_SHOP/UX_Shop_New/api/cart/add.php
# Expected: {"status":"error","message":"Invalid CSRF token"} (HTTP 403)

# ❌ Method not allowed
curl http://localhost/Shop/UX_SHOP/UX_Shop_New/api/cart/add.php
# Expected: {"status":"error","message":"Method not allowed."} (HTTP 405)
```

---

## 9. Endpoints Needing Manual Browser Testing

The following require browser-based flows and cannot be fully tested with curl:

- [ ] Razorpay checkout widget rendering and payment flow
- [ ] Password reset email delivery (configure SMTP in `.env` and test)
- [ ] Welcome email on signup
- [ ] Order confirmation email
- [ ] Contact form email delivery
- [ ] Admin dashboard product image upload (`multipart/form-data`)
- [ ] Cart merge on login (localStorage → server)
- [ ] Checkout page + address selection + order placement end-to-end

---

## 10. Pre-Production Checklist

- [ ] `APP_DEBUG=false` in `.env`
- [ ] `RAZORPAY_KEY_ID`, `RAZORPAY_KEY_SECRET`, `RAZORPAY_WEBHOOK_SECRET` set in `.env`
- [ ] SMTP credentials configured and tested
- [ ] `core/Mailer.php` implemented with PHPMailer
- [ ] HTTPS enabled and HSTS header active
- [ ] Remove `check-users.php`, `test-login.php`, `test-session.php`, `create-test-user.php`, `seed-bundles.php` from production
- [ ] Set Razorpay webhook URL: `https://your-domain/api/payment/webhook.php`
- [ ] Subscribe webhook to: `payment.captured`, `payment.failed`
- [ ] Verify `.htaccess` blocks `.env` on live server
- [ ] Run `api/admin/stats/overview.php` and confirm no PHP errors in log
