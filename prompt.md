# UX Pacific Shop - Master Audit & Fix Prompt

## Project Overview
- **Stack:** PHP 8.x + MySQL + Vanilla JS
- **Path:** `c:\xampp\htdocs\Shop\UX_SHOP\UX_Shop_New`
- **Database:** `uxmerchandise` (MySQL via XAMPP)
- **Admin:** `admin/admin-dashboard.php`
- **Config:** `includes/config.php`, `.env`

---

## 1. PAGE-BY-PAGE ANALYSIS

### Public Pages (Frontend)
| Page | File | Test Areas |
|------|------|------------|
| Home | `index.php` | Hero CTA, Top Products grid, Category cards, Bundle preview, Footer links |
| Shop All | `shopAll.php` | Product grid, filters, pagination, search, sorting |
| Product Detail | `product.php` | Image gallery, price display, add to cart, reviews, related products |
| Bundles | `bundles.php` | Best Seller slider, bundle grid, popup modal, add to cart |
| Cart | `cart.php` | Item list, quantity update, remove item, price calculation, checkout CTA |
| Checkout | `checkout.php` | Address form, payment integration, order summary, validation |
| Orders | `orders.php` | Order history, status display, order details |
| Order Confirmation | `order-confirmation.php` | Success message, order summary, continue shopping |
| Wishlist | `wishlist.php` | Add/remove items, move to cart |
| Account | `account.php` | Profile info, address management, password change |
| Search | `search.php` | Search results, filters, empty state |
| Contact | `contact.php` | Form submission, validation, success message |
| Policies | `policies.php` | Privacy, Terms, Cookie policy content |
| Category | `category.php` | Category-specific product listing |
| Freebies | `freebies.php` | Free resources display |

### Auth Pages
| Page | File | Test Areas |
|------|------|------------|
| Sign In | `signin.php` | Email/password validation, remember me, Google OAuth, OTP modal |
| Sign Up | `signup.php` | Registration form, validation, duplicate check |
| Forgot Password | `forgot-password.php` | Email submission, token generation |
| Reset Password | `reset-password.php` | Token validation, password update |

### Admin Pages
| Page | File | Test Areas |
|------|------|------------|
| Admin Login | `admin/admin-login.php` | Admin auth, session management |
| Dashboard | `admin/admin-dashboard.php` | All tabs: Overview, Products, Bundles, Categories, Orders, Users, Reviews, Messages |

### Error Pages
| Page | File | Test Areas |
|------|------|------------|
| 404 | `404.php` | Error display, back to home link |
| 500 | `500.php` | Server error display |

---

## 2. ADMIN PANEL OPERATIONS CHECKLIST

### Products Tab
- [ ] **List:** All products display with correct data
- [ ] **Create:** Add new product with image upload
- [ ] **Edit:** Update product details, image replacement
- [ ] **Delete/Archive:** Soft delete (is_active = 0)
- [ ] **Toggle Status:** Active/Inactive switch
- [ ] **Featured:** Mark as featured (appears on homepage)
- [ ] **Duplicate:** Clone product

### Bundles Tab
- [ ] **List:** All bundles display
- [ ] **Create:** Add bundle with products, image, pricing
- [ ] **Edit:** Update bundle details
- [ ] **Delete:** Remove bundle
- [ ] **Best Seller:** Toggle featured for slider
- [ ] **Form opens via CTA button** (not always visible)

### Categories Tab
- [ ] **List:** All categories display
- [ ] **Create:** Add category with icon/image
- [ ] **Edit:** Update category
- [ ] **Delete:** Remove category
- [ ] **Toggle Active:** Show/hide category
- [ ] **Form opens via CTA button**

### Orders Tab
- [ ] **List:** All orders with status badges
- [ ] **View Details:** Order items, customer info, address
- [ ] **Update Status:** pending → processing → shipped → delivered
- [ ] **Delete:** Remove order (admin only)

### Users Tab
- [ ] **List:** All registered users
- [ ] **Block/Unblock:** Disable user access
- [ ] **Delete:** Remove user account

### Reviews Tab
- [ ] **List:** Product/bundle reviews
- [ ] **Approve/Reject:** Moderation
- [ ] **Delete:** Remove review

### Messages Tab
- [ ] **List:** Contact form submissions
- [ ] **Mark Read:** Update status
- [ ] **Delete:** Remove message

### Overview Tab
- [ ] **Stats:** Total users, products, orders, revenue
- [ ] **Recent Orders:** Quick view
- [ ] **Top Products:** Best sellers

---

## 3. DATABASE VERIFICATION

### Core Tables
```sql
-- Check these tables exist and have correct structure:
SHOW TABLES;

-- Products
DESCRIBE products;
-- Required: id, name, slug, description, category, price, old_price, image, stock, rating, is_active, is_featured, view_count, sales_count

-- Bundles
DESCRIBE bundles;
-- Required: id, name, slug, description, price, old_price, image, rating, is_active, is_featured, whats_included, badge_text

-- Bundle Items
DESCRIBE bundle_items;
-- Required: id, bundle_id, product_id

-- Categories
DESCRIBE categories;
-- Required: id, name, slug, description, image, accent, is_active, sort_order

-- Users
DESCRIBE users;
-- Required: id, email, password, first_name, last_name, phone, is_blocked, created_at

-- Orders
DESCRIBE orders;
-- Required: id, user_id, total, status, payment_status, created_at

-- Order Items
DESCRIBE order_items;
-- Required: id, order_id, product_id, bundle_id, quantity, price

-- Reviews
DESCRIBE reviews;
-- Required: id, user_id, product_id, bundle_id, rating, comment, is_approved

-- Addresses
DESCRIBE addresses;
-- Required: id, user_id, name, phone, address_line1, city, state, pincode, is_default

-- Cart (session-based or DB)
DESCRIBE cart;
-- Or check session handling in cart.php

-- Contact Messages
DESCRIBE contact_messages;
-- Required: id, name, email, message, is_read, created_at
```

### Data Integrity Checks
```sql
-- Orphan bundle items
SELECT bi.* FROM bundle_items bi 
LEFT JOIN bundles b ON bi.bundle_id = b.id 
WHERE b.id IS NULL;

-- Orphan order items
SELECT oi.* FROM order_items oi 
LEFT JOIN orders o ON oi.order_id = o.id 
WHERE o.id IS NULL;

-- Products without category match
SELECT p.id, p.name, p.category FROM products p 
LEFT JOIN categories c ON p.category = c.name 
WHERE c.id IS NULL AND p.category != '';
```

---

## 4. API ENDPOINTS TESTING

### Auth APIs (`api/auth/`)
| Endpoint | Method | Test |
|----------|--------|------|
| `login.php` | POST | Valid/invalid credentials |
| `signup.php` | POST | New user, duplicate email |
| `logout.php` | POST | Session destruction |
| `forgot-password.php` | POST | Token generation, email |
| `reset-password.php` | POST | Token validation, password update |
| `session.php` | GET | Current user data |
| `csrf.php` | GET | Token generation |

### Cart APIs (`api/cart/`)
| Endpoint | Method | Test |
|----------|--------|------|
| `add.php` | POST | Add product/bundle |
| `update.php` | POST | Change quantity |
| `remove.php` | POST | Remove item |
| `list.php` | GET | Get cart contents |
| `merge.php` | POST | Guest to user cart merge |

### Order APIs (`api/order/`)
| Endpoint | Method | Test |
|----------|--------|------|
| `create.php` | POST | Place order |
| `get.php` | GET | Order details |

### Admin APIs (`api/admin/`)
- All require admin session
- Test CSRF token validation
- Test response format consistency

---

## 5. UI/UX AUDIT CHECKLIST

### Visual Consistency
- [ ] **Color Palette:** Primary purple #6d3dff used consistently
- [ ] **Typography:** Inter font, consistent sizes
- [ ] **Spacing:** 8px grid system
- [ ] **Border Radius:** Consistent (8px cards, 24px buttons)
- [ ] **Shadows:** Subtle, consistent depth

### Component Consistency
- [ ] **Primary CTAs:** Same style (purple bg, hover animation, glow)
- [ ] **Secondary CTAs:** Outline style consistency
- [ ] **Form Inputs:** Same height, border, focus states
- [ ] **Cards:** Consistent padding, hover effects
- [ ] **Modals:** Same close button, animation
- [ ] **Badges:** Status colors consistent (green=active, red=inactive)

### Performance (Not Too Heavy)
- [ ] **Images:** Lazy loading (`loading="lazy"`)
- [ ] **CSS:** No duplicate rules, minimize specificity
- [ ] **JS:** No blocking scripts, defer where possible
- [ ] **Animations:** Use `transform` and `opacity` only
- [ ] **Fonts:** Preload critical fonts
- [ ] **Icons:** SVG inline or single sprite

### Accessibility
- [ ] **Focus States:** Visible on all interactive elements
- [ ] **ARIA Labels:** Buttons, links, modals
- [ ] **Color Contrast:** 4.5:1 minimum
- [ ] **Keyboard Navigation:** Tab order logical
- [ ] **Screen Reader:** Semantic HTML

### Responsive Design
- [ ] **Mobile:** All pages usable at 320px
- [ ] **Tablet:** Grid adjustments at 768px
- [ ] **Desktop:** Max-width containers

### Loading States
- [ ] **Buttons:** "Loading..." text or spinner
- [ ] **Tables:** Skeleton or "Loading..." row
- [ ] **Forms:** Disabled during submission
- [ ] **Modals:** Loading state before content

### Error Handling
- [ ] **Form Errors:** Inline validation messages
- [ ] **API Errors:** Toast notifications
- [ ] **Empty States:** Friendly messages with CTA
- [ ] **404/500:** Branded error pages

---

## 6. SPECIFIC FIXES NEEDED

### High Priority
1. **Verify all admin CRUD operations save to DB correctly**
2. **Test popup modals on all pages (products, bundles)**
3. **Ensure cart persists across sessions**
4. **Test checkout flow end-to-end**
5. **Verify featured items appear correctly on homepage**

### Medium Priority
1. **Standardize all primary CTA animations**
2. **Check category filter functionality**
3. **Verify search returns correct results**
4. **Test pagination on all listing pages**
5. **Ensure image uploads work in admin**

### Low Priority
1. **Review loading animation consistency**
2. **Audit console for JS errors**
3. **Check for PHP warnings in logs**
4. **Optimize large CSS file**
5. **Add missing alt text for images**

---

## 7. TESTING COMMANDS

### PHP Syntax Check
```bash
cd "c:\xampp\htdocs\Shop\UX_SHOP\UX_Shop_New"
C:\xampp\php\php.exe -l index.php
C:\xampp\php\php.exe -l admin/admin-dashboard.php
```

### Database Quick Test
```bash
C:\xampp\php\php.exe -r "require 'includes/config.php'; echo 'Products: '.$conn->query('SELECT COUNT(*) c FROM products')->fetch_assoc()['c'].PHP_EOL;"
```

### Check Error Logs
```bash
type logs\app_errors.log
```

---

## 8. EXECUTION PLAN

### Phase 1: Database & Backend
1. Verify all tables exist with correct schema
2. Test all admin API endpoints
3. Check data integrity (no orphans)
4. Verify CSRF protection on all forms

### Phase 2: Frontend Pages
1. Load each public page, check for errors
2. Test all interactive elements
3. Verify data displays correctly from DB
4. Test responsive breakpoints

### Phase 3: Admin Panel
1. Test each tab functionality
2. Verify CRUD operations
3. Check form validations
4. Test file uploads

### Phase 4: User Flows
1. Sign up → Sign in → Browse → Add to cart → Checkout
2. Admin: Add product → Edit → Feature → Archive
3. Admin: Add bundle → Edit → Set Best Seller
4. User: Submit contact form → Admin view message

### Phase 5: UI/UX Polish
1. Standardize button styles
2. Fix inconsistent spacing
3. Add missing loading states
4. Improve error messages

---

## 9. SUCCESS CRITERIA

- [ ] All pages load without PHP errors
- [ ] All admin CRUD operations work
- [ ] Cart and checkout flow complete
- [ ] User authentication works
- [ ] Responsive on mobile/tablet/desktop
- [ ] No console JS errors
- [ ] Consistent visual design
- [ ] Page load under 3 seconds
- [ ] All forms validate correctly
- [ ] All modals open/close properly

---

## 10. FILES TO MONITOR

### Core PHP
- `includes/config.php` - Database connection
- `includes/marketplace.php` - Product/bundle helpers
- `includes/header.php` - Navigation
- `admin/admin-dashboard.php` - Admin panel
- `admin/admin-dashboard.js` - Admin JS logic

### Core CSS
- `style.css` - Main styles
- `assets/css/bundles.css` - Bundles page
- `css/auth-premium.css` - Auth pages
- `admin/admin.css` - Admin panel

### Core JS
- `script.js` - Main frontend JS

### API
- `api/admin/_admin.php` - Admin bootstrap
- `api/admin/product/save.php` - Product CRUD
- `api/admin/bundles/save.php` - Bundle CRUD
- `api/admin/categories/save.php` - Category CRUD

---

*Use this prompt as a checklist. Work through each section systematically. Fix issues as you find them. Document any changes made.*
