# User-facing API audit (storefront)

**Started:** 2026-05-28  
**Scope:** Customer APIs under `api/` + matching pages in `script.js` / `*.php`  
**Companion:** Admin audits live in `admin-fixapimd.md`.

---

## Audit order (planned)

| # | Area | Screen / page | API(s) | Status |
|---|------|---------------|--------|--------|
| 1 | **Sign up** | `signup.php` | `api/auth/signup.php` | ✅ Audited |
| 2 | **Sign in** | `signin.php` | `api/auth/login.php` | ✅ Audited |
| 3 | **Sign out** | header / account | `api/auth/logout.php` | ✅ Audited |
| 4 | **Session / CSRF** | all pages | `api/auth/session.php`, `api/auth/csrf.php` | ✅ Audited |
| 5 | **Forgot / reset password** | `forgot-password.php`, `reset-password.php` | `api/auth/forgot-password.php`, `verify-reset-token.php`, `reset-password.php` | ✅ Audited |
| 6 | Profile | `account.php` | `api/user/profile.php`, `update_profile.php` | Pending |
| 7 | Change password | account | `api/user/update_password.php` | Pending |
| 8 | Delete account | account | `api/user/delete_account.php` | Pending |
| 9 | Cart | `cart.php` | `api/cart/*` | Pending |
| 10 | Wishlist | `wishlist.php` | `api/wishlist/*` | Pending |
| 11 | Addresses | checkout / account | `api/address/*` | Pending |
| 12 | Checkout / orders | `checkout.php`, `orders.php` | `api/order/*`, `api/payment/*` | Pending |
| 13 | Reviews | `orders.php` | `api/reviews/submit.php` | Pending |
| 14 | Contact | `contact.php` | `api/contact/send.php` | Pending |
| 15 | Catalog | shop pages | `api/catalog/*`, `api/product/*` | Pending |

---

# 1. Sign up

**Audited:** 2026-05-28  
**Screen:** `signup.php` → `handleSignUp()` in `script.js`  
**API:** `POST api/auth/signup.php`

## Endpoint map

| Action | Endpoint | Method | Auth | CSRF |
|--------|----------|--------|------|------|
| Create account | `api/auth/signup.php` | POST | Guest (session for CSRF) | Yes (`X-CSRF-Token` or body `csrf_token`) |
| CSRF token | `api/auth/csrf.php` | GET | Guest | No |
| Page | `signup.php` | GET | — | Meta `csrf-token` |

## Flow

1. User fills **full name**, **email**, **password**, agrees to terms.
2. Client validates (name ≥2 chars, email format, password ≥8, terms checked).
3. Hidden `confirmPassword` synced from password on submit (match check).
4. `getCsrfTokenAsync()` → `POST` JSON to `api/auth/signup.php`.
5. On success → redirect to `signin.php?message=Account created!` (optional `redirect` query preserved).
6. User must **sign in** separately (no auto-login after signup).

## Verdict

**Production-ready with fixes applied** — Core signup works; security hardening added.

## Fixed in this pass

| ID | Severity | Issue | Fix |
|----|----------|-------|-----|
| US1 | **High** | No **rate limiting** (old `apiold` had 5/hour). | `includes/auth_rate_limit.php` — 5 attempts per session hour → 429. |
| US2 | **High** | **Duplicate accounts** possible with email casing (`User@x.com` vs `user@x.com`). | Store/check `LOWER(email)`; normalize to lowercase on insert. |
| US3 | Medium | API ignored **`fullName`** if client sent only that field. | Server splits `fullName` into first/last when `firstName` empty. |
| US4 | Medium | **Login** used exact email match (signup fix mismatch). | `login.php` uses `LOWER(email)` + lowercase input. |
| US5 | Low | No **max length** on name/email/password/phone. | Server caps (100/255/128/40 chars). |
| US6 | Low | Welcome email failure could block unclear errors. | Email wrapped in try/catch; account still created. |
| US7 | Low | Sign-in form allowed **6-char** password client-side; API requires **8**. | `handleSignIn` minimum raised to 8 characters. |
| US8 | Low | Insert/prepare failures not logged. | `error_log` on DB errors. |
| US10 | Low | Hidden confirm password field not visible. | Visible confirm password field in `signup.php`. |
| US12 | Info | Users had to sign in separately after signup. | Auto-login on successful signup; `setUserSession()` client-side. |
| US13 | Info | Rate limit was session-only. | Combined session (5/hr) + IP (10/hr) via `authRateLimitCheck()`. |

## What works well

1. **CSRF** on POST (`validateCsrf()` + header from `fetch`).
2. **Password hashing** — `password_hash(PASSWORD_DEFAULT)`.
3. **Duplicate email** — 409 with clear message.
4. **Role** — new users always `customer`, `is_blocked = 0`.
5. **Welcome email** — `sendWelcomeEmail()` (non-fatal if mail fails).
6. **UI** — premium auth layout, inline errors, loading state, redirect after success.
7. **Terms** checkbox required client-side.

## Request body (JSON)

```json
{
  "firstName": "Jamie",
  "lastName": "Davis",
  "fullName": "Jamie Davis",
  "email": "designer@example.com",
  "phone": "",
  "password": "secret123",
  "csrf_token": "…"
}
```

Required server-side: `firstName` (or derivable from `fullName`), `email`, `password` (≥8 chars).

## Response

- **Success (200):** `{ "status": "success", "message": "Account created successfully.", "data": { "user_id": 123 } }`
- **409:** Email already exists  
- **422:** Validation errors  
- **403:** Invalid CSRF  
- **429:** Rate limit  

## Manual test plan

1. Open `signup.php` — form loads, CSRF meta present.
2. Submit empty form — inline validation errors.
3. Submit valid new email — success banner → redirect to signin.
4. Sign in with new credentials — works (case-insensitive email).
5. Submit same email again — “already exists” error.
6. Submit same email different casing — still rejected.
7. Rapidly submit 6+ signups — 429 on 6th attempt (same browser session).
8. **Google** button — toast “coming soon” (no API call).

## Open follow-ups (signup)

| ID | Severity | Description | Status |
|----|----------|-------------|--------|
| US9 | Medium | **Google OAuth** not implemented (`signUpWithGoogle()` stub). | Open — requires OAuth setup |
| ~~US10~~ | ~~Low~~ | ~~No **confirm password** field on UI (hidden sync only).~~ | **Fixed** — visible confirm password field added |
| US11 | Low | No **email verification** before first login. | Open — requires email token flow |
| ~~US12~~ | ~~Info~~ | ~~Signup does not **auto-login**; user must sign in.~~ | **Fixed** — auto-login after signup |
| ~~US13~~ | ~~Info~~ | ~~Rate limit is **per session**, not per IP.~~ | **Fixed** — combined session + IP rate limiting || US13 | Info | Rate limit is **per session**, not per IP (shared session edge case on shared devices). |

## CLI smoke test

```bash
c:\xampp\php\ e scripts/test-user-signup-api.php
```

---

# 2. Sign in

**Audited:** 2026-06-01  
**Screen:** `signin.php` → `handleSignIn()` in `script.js`  
**API:** `POST api/auth/login.php`

## Endpoint map

| Action | Endpoint | Method | Auth | CSRF |
|--------|----------|--------|------|------|
| Sign in | `api/auth/login.php` | POST | Guest (session for CSRF) | Yes |
| Sign out | `api/auth/logout.php` | POST | User session | Yes |
| Session check | `api/auth/session.php` | GET | Any | No |

## Flow

1. User enters **email** and **password**.
2. Client validates (email format, password ≥8 chars).
3. `POST` JSON to `api/auth/login.php` with CSRF.
4. Server checks rate limit → validates credentials → checks blocked status.
5. On success: session regenerated, user data + new CSRF returned.
6. Client stores user session, merges local cart, redirects.

## Verdict

**Production-ready with fixes applied** — Rate limiting added; password length aligned.

## Fixed in this pass

| ID | Severity | Issue | Fix |
|----|----------|-------|-----|
| SI1 | **High** | No **rate limiting** on login (brute force vulnerability). | Added `authRateLimitCheck()` — 5/session + 15/IP per 15 mins → 429. |
| SI2 | Medium | HTML `minlength="6"` vs JS validation of **8** chars. | HTML updated to `minlength="8"`. |
| SI3 | Medium | Server accepted any password length. | Server now validates `strlen($password) < 8` → 422. |

## What works well

1. **CSRF** on POST (`validateCsrf()` + header from `fetch`).
2. **Case-insensitive email** — `LOWER(email)` in query.
3. **Password hashing** — `password_verify()` against `password_hash`.
4. **Blocked account check** — Returns 403 if `is_blocked = 1`.
5. **Session regeneration** — `session_regenerate_id(true)` on login.
6. **CSRF rotation** — New token generated after login.
7. **Cart merge** — Local cart merged with server cart after login.
8. **Open redirect protection** — `handleSignInRedirect()` only allows whitelisted pages.
9. **UI** — Loading state, error/success messages, redirect param preserved.

## Request body (JSON)

```json
{
  "email": "user@example.com",
  "password": "secret123",
  "csrf_token": "…"
}
```

## Response

- **Success (200):** `{ "status": "success", "data": { "user": {...}, "csrf_token": "..." } }`
- **401:** Invalid email or password
- **403:** Account blocked
- **422:** Validation errors
- **429:** Rate limit exceeded

## Manual test plan

1. Open `signin.php` — form loads, CSRF meta present.
2. Submit empty form — inline validation errors.
3. Submit invalid email/password — "Invalid email or password" error.
4. Submit valid credentials — success, redirect to home (or `?redirect=` target).
5. Sign out → sign back in — cart merges if local items exist.
6. Test with blocked account — "Account is blocked" error.
7. Rapidly submit 6+ login attempts — 429 rate limit.
8. Test case variations (`User@Example.COM`) — should still work.

## Open follow-ups (sign-in)

| ID | Severity | Description | Status |
|----|----------|-------------|--------|
| SI4 | Medium | **Google OAuth** not implemented (`signInWithGoogle()` stub). | Open |
| SI5 | Low | **"Remember me"** checkbox is in UI but does nothing. | Open |
| SI6 | Low | **OTP modal** in `signin.php` is unused dead code. | Open — may be for future 2FA |

## CLI smoke test

```bash
c:\xampp\php\php.exe scripts/test-user-signin-api.php
```

---

# 3. Sign out

**Audited:** 2026-06-01  
**Screen:** Header dropdown → `handleSignOut()` in `script.js`  
**API:** `POST api/auth/logout.php`

## Endpoint

| Action | Endpoint | Method | Auth | CSRF |
|--------|----------|--------|------|------|
| Sign out | `api/auth/logout.php` | POST | User session | Yes |

## Flow

1. User clicks "Sign out" in header dropdown.
2. `handleSignOut()` sends `POST` with CSRF to `api/auth/logout.php`.
3. Server clears `$_SESSION`, destroys session cookie, calls `session_destroy()`.
4. Client clears local user session, shows toast, redirects to home.

## Verdict

**Production-ready** — CSRF protected, proper session cleanup.

## What works well

1. **CSRF** on logout to prevent logout CSRF attacks.
2. **Full session cleanup** — `$_SESSION = []`, cookie cleared, `session_destroy()`.
3. **Client cleanup** — `clearUserSession()` removes localStorage data.
4. **Graceful handling** — Logout proceeds even if API call fails.

## Open follow-ups (sign-out)

| ID | Severity | Description |
|----|----------|-------------|
| SO1 | Info | No server-side token invalidation (sessions are stateless PHP sessions). |

---

# 4. Session / CSRF

**Scope**: Server-side session management, CSRF token handling, client/server sync.

---

## 4.1. Endpoint Map

| Purpose | File | Method | Auth | CSRF |
|---------|------|--------|------|------|
| Session check | `api/auth/session.php` | GET | Public | No |
| CSRF token fetch | `api/auth/csrf.php` | GET | Public | No |

---

## 4.2. Server-Side Session Infrastructure

**File**: `includes/config.php`

| Aspect | Implementation | Status |
|--------|----------------|--------|
| Session start | Only if `session_status() === PHP_SESSION_NONE` | OK |
| Cookie httponly | `true` | OK |
| Cookie samesite | `'Lax'` | OK |
| Cookie secure | Dynamic based on HTTPS detection | OK |
| Session lifetime | 86400 seconds (24 hours) | OK |
| CSRF token init | `bin2hex(random_bytes(32))` if empty | OK |

---

## 4.3. Protected Pages (Server-Side Auth Guards)

| Page | Guard Logic | Redirect |
|------|-------------|----------|
| `account.php` | `empty($_SESSION['user_id'])` | `signin.php?redirect=account.php` |
| `orders.php` | `empty($_SESSION['user_id'])` | `signin.php?redirect=orders.php` |
| `checkout.php` | `empty($_SESSION['user_id'])` | `signin.php?redirect=checkout.php` |

All three pages properly require authentication and redirect unauthenticated users.

---

## 4.4. CSRF Token Coverage

### Pages with CSRF meta tag (verified):

| Page | Has `<meta name="csrf-token">`? |
|------|--------------------------------|
| `index.php` | ✅ Yes |
| `signin.php` | ✅ Yes |
| `signup.php` | ✅ Yes |
| `cart.php` | ✅ Yes |
| `checkout.php` | ✅ Yes |
| `orders.php` | ✅ Yes |
| `account.php` | ✅ Yes |
| `search.php` | ✅ Yes |
| `product.php` | ✅ Yes |
| `bundles.php` | ✅ Yes |
| `freebies.php` | ✅ Yes |
| `contact.php` | ✅ Yes |
| `wishlist.php` | ✅ Yes |
| `category.php` | ✅ Yes |
| `forgot-password.php` | ✅ Yes |
| `reset-password.php` | ✅ Yes |
| `order-confirmation.php` | ✅ Yes |
| `admin-dashboard.php` | ✅ Yes |
| `admin-login.php` | ✅ Yes |

All public-facing and admin pages include the CSRF meta tag.

---

## 4.5. Client-Side Session Management

**File**: `script.js`

| Function | Purpose |
|----------|---------|
| `getUserSession()` | Read from `localStorage.userSession` |
| `setUserSession(data)` | Write to localStorage + update UI |
| `clearUserSession()` | Remove from localStorage + update UI |
| `hydrateSession()` | Sync client state from `api/auth/session.php` |
| `syncHeaderAuth()` | Update header UI based on session state |

### Flow on page load:
1. `DOMContentLoaded` fires
2. `hydrateSession()` fetches `api/auth/session.php`
3. If server returns user → `setUserSession()` updates localStorage
4. If server returns null → `clearUserSession()` clears localStorage
5. `syncHeaderAuth()` updates header to show correct auth state

---

## 4.6. CSRF Token Client Handling

**File**: `script.js`

| Function | Purpose |
|----------|---------|
| `getCsrfToken()` | Sync read from cache or meta tag |
| `getCsrfTokenAsync()` | Async fetch from `api/auth/csrf.php` if needed |
| `setCsrfToken(token)` | Update cache + meta tag |
| `secureFetch(url, opts)` | Auto-inject `X-CSRF-Token` header |

### Token sources (priority):
1. In-memory cache (`_csrfToken`)
2. `<meta name="csrf-token">` attribute
3. Fetch from `api/auth/csrf.php` (fallback)

---

## 4.7. API Implementations

### `api/auth/session.php`

```php
<?php
require_once __DIR__ . '/../_bootstrap.php';

$user = apiUser();
sendResponse('success', $user ? 'Signed in.' : 'Guest session.', ['user' => $user]);
```

**Assessment**: Simple, correct. Returns user data from session or null.

### `api/auth/csrf.php`

```php
<?php
require_once __DIR__ . '/../_bootstrap.php';

sendResponse('success', 'CSRF token ready.', ['token' => $_SESSION['csrf_token'] ?? '']);
```

**Assessment**: Works, but could return empty string if session not initialized (edge case).

---

## 4.8. validateCsrf() Implementation

**File**: `includes/helpers.php`

```php
function validateCsrf() {
    if (empty($_SESSION['csrf_token'])) {
        sendResponse("error", "Session expired", null, 403);
    }
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($token)) {
        $body = json_decode(file_get_contents('php://input'), true);
        $token = $body['csrf_token'] ?? '';
    }
    if (!$token || !hash_equals($_SESSION['csrf_token'], $token)) {
        sendResponse("error", "Invalid CSRF token", null, 403);
    }
}
```

**Assessment**:
- ✅ Checks both `X-CSRF-Token` header and body `csrf_token` field
- ✅ Uses `hash_equals()` for timing-safe comparison
- ✅ Returns 403 on mismatch

---

## 4.9. What Works Well

1. **Session security** — `httponly`, `samesite: Lax`, dynamic `secure` flag.
2. **CSRF coverage** — All pages have meta tag; all mutation APIs call `validateCsrf()`.
3. **Timing-safe comparison** — `hash_equals()` prevents timing attacks.
4. **Client fallback** — `getCsrfTokenAsync()` fetches token if meta missing.
5. **Session hydration** — Client syncs with server on every page load.
6. **Graceful degradation** — Server-rendered auth state preserved if JS fails.
7. **Token rotation on login** — New CSRF token issued after successful login.

---

## 4.10. Open Follow-ups

| ID | Severity | Description |
|----|----------|-------------|
| SE1 | Low | `api/auth/csrf.php` can return empty string if session not started. |
| SE2 | Info | No session activity tracking (idle timeout not implemented). |
| SE3 | Info | No session fingerprinting (user-agent/IP binding for extra security). |
| SE4 | Info | CSRF token not rotated after all sensitive actions (only after login). |
| SE5 | Info | No concurrent session control (user can have unlimited sessions). |

---

## 4.11. Verdict

**Status**: ✅ **Production-ready**

Session and CSRF handling is solid:
- All protected pages have proper server-side auth guards
- All mutation APIs validate CSRF tokens
- Client/server session sync works correctly
- Cookie security flags properly configured
- Timing-safe token comparison in place

Minor improvements (SE2-SE5) are optional hardening measures, not critical issues.

---

# 5. Password Management (Forgot / Reset)

**Scope**: Forgot password flow, reset token validation, password reset.

---

## 5.1. Endpoint Map

| Purpose | File | Method | Auth | CSRF |
|---------|------|--------|------|------|
| Request reset link | `api/auth/forgot-password.php` | POST | Public | ✅ Yes |
| Verify reset token | `api/auth/verify-reset-token.php` | POST | Public | No (read-only) |
| Reset password | `api/auth/reset-password.php` | POST | Public | ✅ Yes |

---

## 5.2. Flow Overview

```
User → forgot-password.php → enters email
     → api/auth/forgot-password.php
         ├─ validates email format
         ├─ rate limit: 5/IP/hour, 3/user/hour
         ├─ generates 64-char token, stores SHA-256 hash
         ├─ sends email with link (logs if email fails)
         └─ returns generic message (no email enumeration)

User → clicks email link → reset-password.php?token=...&email=...
     → JS calls api/auth/verify-reset-token.php
         ├─ rate limit: 10/IP/15min
         ├─ validates token hash + email + expiry
         └─ returns valid/invalid

User → enters new password
     → api/auth/reset-password.php
         ├─ CSRF validation
         ├─ re-validates token
         ├─ validates password (min 8 chars)
         ├─ updates password hash
         ├─ marks token as used
         ├─ revokes other sessions (user_tokens)
         └─ returns success
```

---

## 5.3. Security Analysis

### Token Security

| Aspect | Implementation | Status |
|--------|----------------|--------|
| Token generation | `bin2hex(random_bytes(32))` = 64 hex chars | ✅ Strong |
| Token storage | Only SHA-256 hash stored in DB | ✅ Secure |
| Token expiry | 1 hour | ✅ Reasonable |
| Token single-use | `used_at` timestamp checked | ✅ Yes |
| Old tokens cleanup | Unused tokens deleted on new request | ✅ Yes |

### Rate Limiting

| Endpoint | IP Limit | User Limit | Window |
|----------|----------|------------|--------|
| `forgot-password.php` | 5/hour | 3/hour | ✅ Both |
| `verify-reset-token.php` | 10/15min | N/A | ✅ Added |
| `reset-password.php` | N/A (token-gated) | N/A | OK |

### Email Enumeration Prevention

- Generic response: "If that email is registered, a reset link has been sent."
- Same response for valid and invalid emails
- Rate limit applies even for non-existent emails

---

## 5.4. What Works Well

1. **Token never stored raw** — Only SHA-256 hash in DB, raw token in email link.
2. **CSRF on mutations** — `forgot-password.php` and `reset-password.php` validate CSRF.
3. **Generic responses** — No email enumeration possible.
4. **Per-user rate limiting** — Max 3 requests/hour per user_id.
5. **IP-based rate limiting** — Added to prevent flooding.
6. **Transaction safety** — Token creation and password update use transactions.
7. **Session revocation** — Deletes `user_tokens` on password reset.
8. **Graceful email failure** — Logs link if email fails, doesn't block user.
9. **Client-side validation** — Token verified before showing password form.
10. **Password match validation** — Both client and server check confirm password.

---

## 5.5. Fixes Applied

| ID | Issue | Fix |
|----|-------|-----|
| FP1 | No rate limit on `verify-reset-token.php` | ✅ Added IP-based limit (10/15min) |
| FP2 | No IP rate limit on `forgot-password.php` | ✅ Added IP-based limit (5/hour) |

---

## 5.6. Frontend Pages

### `forgot-password.php`

| Aspect | Status |
|--------|--------|
| CSRF meta tag | ✅ Present |
| Email input validation | ✅ `type="email" required` |
| Loading state | ✅ Button disabled + "Sending..." |
| Error display | ✅ Alert shown |
| Success display | ✅ Alert shown, form hidden |

### `reset-password.php`

| Aspect | Status |
|--------|--------|
| CSRF meta tag | ✅ Present |
| Token validation on load | ✅ JS calls verify-reset-token |
| Invalid token state | ✅ Shows error + link to request new |
| Password fields | ✅ minlength=8, autocomplete=new-password |
| Confirm password | ✅ Client validates match |
| Loading state | ✅ Button disabled + "Saving..." |
| Success state | ✅ Shows success + sign-in link |

---

## 5.7. Manual Test Plan

| # | Test Case | Expected |
|---|-----------|----------|
| 1 | Submit valid email | Generic success, email received (or logged) |
| 2 | Submit invalid email format | "Please enter a valid email address" |
| 3 | Submit non-existent email | Same generic success (no enumeration) |
| 4 | Request >5 times from same IP in 1 hour | "Too many reset requests" |
| 5 | Request >3 times for same user in 1 hour | Generic success (silent block) |
| 6 | Click valid reset link | Form shown |
| 7 | Click expired link (>1 hour) | "Invalid or expired reset link" |
| 8 | Click already-used link | "Invalid or expired reset link" |
| 9 | Verify token >10 times from same IP in 15min | "Too many verification attempts" |
| 10 | Submit password <8 chars | "Password must be at least 8 characters" |
| 11 | Submit mismatched passwords | "Passwords do not match" |
| 12 | Submit valid new password | "Password reset successfully" + sign-in link |
| 13 | Try to use same link again | "Invalid or expired reset link" |

---

## 5.8. Open Follow-ups

| ID | Severity | Description |
|----|----------|-------------|
| PM1 | Low | No password complexity rules (only length). Consider adding: uppercase, lowercase, number. |
| PM2 | Info | No "password has been changed" notification email sent after reset. |
| PM3 | Info | Token expiry (1 hour) is hardcoded. Could be configurable. |

---

## 5.9. Verdict

**Status**: ✅ **Production-ready**

The forgot/reset password flow is secure:
- Tokens are cryptographically strong and hashed
- Rate limiting prevents abuse (both IP and per-user)
- No email enumeration possible
- CSRF protection on state-changing endpoints
- Single-use tokens with expiry
- Other sessions revoked on password change

---

# 6. Profile / Change Password (next)

_To be filled when auditing `api/user/profile.php`, `api/user/update_profile.php`, `api/user/update_password.php`._
