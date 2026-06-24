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
    <?php include 'includes/auth-preload.php'; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="preconnect" href="https://checkout.razorpay.com" />
    <link rel="preconnect" href="https://api.razorpay.com" />
    <link rel="dns-prefetch" href="https://checkout.razorpay.com" />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
      rel="stylesheet"
    />
      <link rel="icon" type="image/png" href="img/fav.png" />
    <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('style.css')); ?>" />
    <script defer src="https://unpkg.com/@phosphor-icons/web"></script>
    <script defer src="https://checkout.razorpay.com/v1/checkout.js"></script>
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
      <?php include 'includes/header.php'; ?>

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

                  <!-- Digital Delivery Notice -->
                  <div id="digital-delivery-notice" style="padding: 12px 16px; background: rgba(37, 99, 235, 0.08); border: 1px solid rgba(37, 99, 235, 0.2); border-radius: 8px; margin-bottom: 16px; font-size: 14px; color: #93c5fd;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline;vertical-align:middle;margin-right:8px;">
                      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                      <polyline points="7 10 12 15 17 10"></polyline>
                      <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    Your digital products will be available for download immediately after payment in <strong>My Orders</strong>.
                  </div>

                  <!-- Free checkout (no payment) -->
                  <div id="free-checkout-notice" class="checkout-block" style="display:none;">
                    <h2 class="block-title">Free Download</h2>
                    <div style="background:rgba(34,197,94,0.08);border:1px solid rgba(34,197,94,0.25);border-radius:8px;padding:16px;">
                      <p style="margin:0;color:#86efac;font-size:0.95rem;">
                        No payment required. Enter your email above and click <strong>Get Free Download</strong> — your files will be available in <strong>My Orders</strong> right away.
                      </p>
                    </div>
                  </div>

                  <!-- Payment Method -->
                  <div class="checkout-block" id="checkout-payment-block">
                    <h2 class="block-title">Payment Method</h2>
                    
                    <div class="payment-methods" id="checkout-payment-section">
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
                      <!-- COD disabled for digital-only launch -->
                      <label class="payment-option" id="cod-option" style="display:none;" aria-hidden="true">
                        <input type="radio" name="paymentMethod" value="cod" id="cod-radio" disabled />
                        <div class="payment-option-content">
                          <span class="payment-icon">
                            <img src="img/cash.webp" alt="cash" width="30" height="30" >
                          </span>
                          <span>Cash on Delivery</span>
                        </div>
                        <span id="cod-disabled-message" class="cod-disabled-message" style="display: none;"></span>
                      </label>
                    </div>

                    <?php if (getenv('ENABLE_TEST_PAYMENT') === 'true' && getenv('APP_ENV') !== 'production'): ?>
                      <label class="payment-option" id="test-pay-option" style="border:2px dashed rgba(251,191,36,0.45);background:rgba(251,191,36,0.05);">
                        <input type="radio" name="paymentMethod" value="test" />
                        <div class="payment-option-content">
                          <span class="payment-icon" style="font-size:1.25rem;">🧪</span>
                          <span style="color:#fbbf24;">Test Payment <span style="font-size:0.78em;opacity:0.7;">(dev only)</span></span>
                        </div>
                      </label>
                    <?php endif; ?>

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
                      <span id="checkout-subtotal">₹0</span>
                    </div>
                    <div class="summary-row">
                      <span>Shipping</span>
                      <span id="checkout-shipping">₹50</span>
                    </div>
                    <div class="summary-row">
                      <span>Tax</span>
                      <span id="checkout-tax">₹0</span>
                    </div>
                    <div class="summary-divider"></div>
                    <div class="summary-row total-row">
                      <span>Total</span>
                      <span id="checkout-total">₹0</span>
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

      <?php include 'includes/footer.php'; ?>
    </div>

    <script src="<?php echo htmlspecialchars(asset_url('script.js')); ?>"></script>

  </body>
</html>


