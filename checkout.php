<?php
require_once 'includes/config.php';
if (empty($_SESSION['user_id'])) {
    header('Location: signin.php?redirect=checkout.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>Checkout – UX Pacific Shop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
      rel="stylesheet"
    />
      <link rel="icon" type="image/x-icon" href="img/faviconUXP444@4x-789.png" />
    <link rel="stylesheet" href="style.css" />
    <style>
      /* Frontend-only: COD disabled message styling */
      .cod-disabled-message {
        display: block;
        color: #ef4444;
        font-size: 0.875rem;
        margin-top: 0.5rem;
        font-weight: 500;
      }
      #cod-option.disabled {
        opacity: 0.6;
        cursor: not-allowed;
        pointer-events: none;
      }
    </style>
  </head>

  <body>
    <div class="page">
      <!-- NAVBAR -->
      <header class="site-header" id="navbar">
        <nav class="nav-bar">
          <div class="nav-logo">
            <a href="index.php">
              <img src="img/logo1.webp" alt="UX Pacific" />
            </a>
          </div>

          <ul class="nav-links">
            <li><a href="index.php" class="nav-link">Home</a></li>
            <li><a href="index.php#story" class="nav-link">About Us</a></li>
            <li><a href="index.php#products" class="nav-link">New</a></li>
            <li><a href="shopAll.php" class="nav-link">Buy Now</a></li>
          </ul>

          <div class="nav-actions">
            <a href="cart.php" class="nav-cart">
              <img src="img/cart-icon.webp" alt="Shopping cart" />
              <span id="cart-count">0</span>
            </a>
            <a href="signin.php" class="nav-cta">Sign in</a>
            <div class="nav-user">
              <div class="user-avatar"></div>
              <div class="user-info">
                <span class="user-name">User</span>
                <span class="user-role">Customer</span>
              </div>
              <div class="user-dropdown">
                <a href="account.php" class="user-dropdown-item">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                  </svg>
                  <span>My Account</span>
                </a>
                <a href="cart.php" class="user-dropdown-item">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                  </svg>
                  <span>My Cart</span>
                </a>
                <a href="orders.php" class="user-dropdown-item">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                    <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
                  </svg>
                  <span>My Orders</span>
                </a>
                <div class="user-dropdown-divider"></div>
                <a href="#" onclick="handleSignOut(); return false;" class="user-dropdown-item logout">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                  </svg>
                  <span>Sign Out</span>
                </a>
              </div>
            </div>
          </div>

          <button
            id="mobile-menu-btn"
            class="nav-toggle"
            aria-label="Toggle navigation"
          >
            <span></span>
            <span></span>
            <span></span>
          </button>
        </nav>

        <div id="mobile-menu" class="nav-mobile-menu">
          <a href="index.php" class="nav-mobile-link">Home</a>
          <a href="index.php#story" class="nav-mobile-link">About Us</a>
          <a href="index.php#products" class="nav-mobile-link">New</a>
          <a href="shopAll.php" class="nav-mobile-link">Buy Now</a>
          <a href="signin.php" class="nav-mobile-link nav-mobile-cta">
            Sign in
          </a>
        </div>
      </header>

      <!-- MAIN CONTENT -->
      <main class="main">
        <section class="checkout-section">
          <div class="checkout-container">
            <h1 class="checkout-title">Checkout</h1>

            <div class="checkout-layout">
              <!-- Checkout Form -->
              <div class="checkout-form-wrapper">
                <form class="checkout-form" id="checkout-form" onsubmit="handleCheckout(event)">
                  <!-- Saved Addresses Selector (shown when user has saved addresses) -->
                  <!-- <div id="saved-addresses-section" class="checkout-block" style="display: none;">
                    <h2 class="block-title">Delivery Address</h2>

                    <div id="saved-addresses-list" class="address-cards">
                      
                    </div>

                    <button type="button" id="use-new-address-btn" class="btn-outline btn-small" style="margin-top: 1rem;">
                      + Use a Different Address
                    </button>
                  </div> -->

                  <!-- Shipping Information (Manual Entry Form) -->
                  <div id="new-address-section" class="checkout-block">
                    <h2 class="block-title">Shipping Information</h2>

                    <!-- Digital delivery info (shown when cart has digital items) -->
                    <div id="digital-delivery-info" class="digital-delivery-info" style="display: none;">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                      </svg>
                      <span>Digital products will be delivered to your email address after purchase.</span>
                    </div>

                    <div class="form-row">
                      <div class="form-field">
                        <label for="first-name">First Name *</label>
                        <input
                          id="first-name"
                          name="firstName"
                          type="text"
                          required
                          minlength="2"
                        />
                      </div>
                      <div class="form-field">
                        <label for="last-name">Last Name *</label>
                        <input
                          id="last-name"
                          name="lastName"
                          type="text"
                          required
                          minlength="2"
                        />
                      </div>
                    </div>

                    <div class="form-field">
                      <label for="email">Email Address *</label>
                      <input
                        id="email"
                        name="email"
                        type="email"
                        required
                        autocomplete="email"
                      />
                    </div>

                    <div class="form-field">
                      <label for="phone">Phone Number *</label>
                      <input
                        id="phone"
                        name="phone"
                        type="tel"
                        required
                        pattern="[\d\s\-\+\(\)]+"
                        placeholder="+91 xxxxx- xxxxx"
                      />
                    </div>

                    <!-- Shipping address fields (hidden for digital-only orders) -->
                    <div id="shipping-fields">
                      <div class="form-field">
                        <label for="address">Street Address *</label>
                        <input
                          id="address"
                          name="address"
                          type="text"
                          placeholder="House/Flat No., Building Name"
                          required
                        />
                      </div>

                      <div class="form-row">
                        <div class="form-field">
                          <label for="city">City *</label>
                          <input
                            id="city"
                            name="city"
                            type="text"
                            required
                          />
                        </div>
                        <div class="form-field">
                          <label for="state">State *</label>
                          <input
                            id="state"
                            name="state"
                            type="text"
                            required
                          />
                        </div>
                      </div>

                      <div class="form-row">
                        <div class="form-field">
                          <label for="zip">ZIP/Postal Code *</label>
                          <input
                            id="zip"
                            name="zip"
                            type="text"
                            pattern="[\d]+"
                            required
                          />
                        </div>
                        <div class="form-field">
                          <label for="country">Country *</label>
                          <select id="country" name="country" style="background-color: #050519; color: #fff;">
                            <option value="IN" style="color: #fff;">India</option>
                            <option value="US" style="color: #fff;">United States</option>
                            <option value="UK" style="color: #fff;">United Kingdom</option>
                            <option value="CA" style="color: #fff;">Canada</option>
                            <option value="AU" style="color: #fff;">Australia</option>
                          </select>
                        </div>
                      </div>
                    </div>

                    <!-- Save address checkbox -->
                    <div id="save-address-row" class="form-field checkbox-field" style="margin-top: 1rem;">
                      <label class="checkbox-label">
                        <input type="checkbox" id="save-address-checkbox" name="saveAddress" />
                        <span class="checkbox-text">Save this address for future orders</span>
                      </label>
                    </div>
                  </div>

                  <!-- Email field (always shown, needed for order confirmation) -->
                  <div id="email-only-section" class="checkout-block" style="display: none;">
                    <h2 class="block-title">Contact Information</h2>
                    <div class="form-field">
                      <label for="email-saved">Email Address *</label>
                      <input
                        id="email-saved"
                        name="emailSaved"
                        type="email"
                        autocomplete="email"
                        placeholder="For order confirmation & updates"
                      />
                    </div>
                  </div>

                  <!-- Payment Method -->
                  <div class="checkout-block">
                    <h2 class="block-title">Payment Method</h2>
                    
                    <div class="payment-methods">
                      <label class="payment-option">
                        <input type="radio" name="paymentMethod" value="card" checked required />
                        <div class="payment-option-content">
                          <span class="payment-icon">
                            <img src="img/icard.webp" alt="card" width="30" height="30">
                          </span>
                          <span>Credit/Debit Card</span>
                        </div>
                      </label>
                      <label class="payment-option">
                        <input type="radio" name="paymentMethod" value="upi" />
                        <div class="payment-option-content">
                          <span class="payment-icon">
                            <img src="img/iupi.webp" alt="upi" width="30" height="30">
                          </span>
                          <span>UPI</span>
                        </div>
                      </label>
                      <label class="payment-option" id="cod-option">
                        <input type="radio" name="paymentMethod" value="cod" id="cod-radio" />
                        <div class="payment-option-content">
                          <span class="payment-icon">
                            <img src="img/cash.webp" alt="cash" width="30" height="30" >
                          </span>
                          <span>Cash on Delivery</span>
                        </div>
                        <span id="cod-disabled-message" class="cod-disabled-message" style="display: none; color: #ef4444; font-size: 0.875rem; margin-top: 0.5rem;">
                          Cash on Delivery is not available for digital products
                        </span>
                      </label>
                    </div>

                    <!-- Razorpay handles card/UPI — shown when card or UPI is selected -->
                    <div id="card-details" class="card-details" style="background:rgba(111,75,255,0.06);border:1px solid rgba(111,75,255,0.25);border-radius:8px;padding:16px;margin-top:12px">
                      <p style="margin:0;color:#ccc;font-size:0.9rem;">
                        💳 You will be redirected to <strong style="color:#9b8cff">Razorpay</strong> to complete your payment securely. Supports Cards, UPI, Net Banking, and Wallets.
                      </p>
                    </div>
                  </div>

                  <div class="checkout-actions">
                    <a href="cart.php" class="btn-ghost">← Back to Cart</a>
                    <button type="submit" class="btn-primary" id="place-order-btn">
                      <span id="order-text">Place Order</span>
                      <span id="order-loader" style="display:none;">Processing...</span>
                    </button>
                  </div>
                </form>
              </div>

              <!-- Order Summary -->
              <div class="checkout-summary-wrapper">
                <div class="checkout-summary">
                  <h2 class="summary-title">Order Summary</h2>
                  
                  <div id="checkout-items" class="checkout-items">
                    <!-- Items loaded from cart -->
                  </div>

                  <div class="summary-details">
                    <div class="summary-row">
                      <span>Subtotal</span>
                      <span id="checkout-subtotal">$0</span>
                    </div>
                    <div class="summary-row">
                      <span>Shipping</span>
                      <span id="checkout-shipping">$50</span>
                    </div>
                    <div class="summary-row">
                      <span>Tax</span>
                      <span id="checkout-tax">$0</span>
                    </div>
                    <div class="summary-divider"></div>
                    <div class="summary-row total-row">
                      <span>Total</span>
                      <span id="checkout-total">$0</span>
                    </div>
                  </div>

                  <div class="checkout-security">
                    <div class="security-item">
                      <img src="img/m4.webp" alt="Secure" />
                      <span>256-bit SSL Encrypted</span>
                    </div>
                    <div class="security-item">
                      <img src="img/m3.webp" alt="Safe" />
                      <span>Safe & Secure Payment</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>
      </main>

      <!-- FOOTER -->
      <footer class="site-footer">
        <div class="footer-main">
          <div class="footer-top">
            <div class="footer-brand">
              <img src="img/logo1.webp" alt="UX Pacific" />
              <p>
                Design resources and merchandise trusted by creators worldwide 
                built to be used, worn, and valued.
              </p>
              <div class="footer-socials">
                <a
                  href="https://dribbble.com/social-ux-pacific"
                  target="_blank"
                  rel="noopener"
                >
                  <img src="img/bl.webp" alt="Dribbble" />
                </a>
                <a
                  href="https://www.instagram.com/official_uxpacific/"
                  target="_blank"
                  rel="noopener"
                >
                  <img src="img/i.webp" alt="Instagram" />
                </a>
                <a
                  href="https://www.linkedin.com/company/uxpacific/"
                  target="_blank"
                  rel="noopener"
                >
                  <img src="img/in1.png" alt="LinkedIn" />
                </a>
                <a
                  href="https://in.pinterest.com/uxpacific/"
                  target="_blank"
                  rel="noopener"
                >
                  <img src="img/p.webp" alt="Pinterest" />
                </a>
                <a
                  href="https://www.behance.net/ux_pacific"
                  target="_blank"
                  rel="noopener"
                >
                  <img src="img/be.webp" alt="Behance" />
                </a>
              </div>
            </div>

            <div class="footer-contact">
              <p>Support : +91 9274061063&nbsp;&nbsp;&nbsp;&nbsp;|</p>
              <p>
                Email :
                <a
                  href="https://mail.google.com/mail/?view=cm&fs=1&to=hello@uxpacific.com"
                  style="text-decoration: none; color: inherit"
                  target="_blank"
                  >hello@uxpacific.com</a
                >
                &nbsp;&nbsp;&nbsp;&nbsp;
              </p>
            </div>
          </div>
        </div>

        <div class="footer-bottom">
          <p>©2026 UXPacific. All rights reserved.</p>
          <div class="footer-links">
            <a href="policies.php" target="">Our Policies </a>
            <span>•</span>
            <a href="contact.php" style="text-decoration: none;">Contact Us</a>
          </div>
        </div>
      </footer>
    </div>

    <script src="script.js"></script>

  </body>
</html>


