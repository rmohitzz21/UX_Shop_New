<?php require_once 'includes/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="View and manage your shopping cart at UX Pacific Shop. Review items, quantities, and proceed to secure checkout." />
    <meta name="keywords" content="shopping cart, checkout, UX Pacific, design resources, merchandise" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
    <?php include 'includes/auth-preload.php'; ?>
    <title>Shopping Cart – UX Pacific Shop</title>
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
      rel="stylesheet"
    />
      <link rel="icon" type="image/png" href="img/fav.png" />
    <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('style.css')); ?>" />
    <script defer src="https://unpkg.com/@phosphor-icons/web"></script>
  </head>

  <body>
    <div class="page">
      <?php include 'includes/header.php'; ?>

      <!-- MAIN CONTENT -->
      <main class="main">
        <section class="cart-section">
          <div class="cart-container">
            <h1 class="cart-title">Shopping Cart</h1>

            <div class="cart-layout">
              <!-- Cart Items -->
              <div class="cart-items-wrapper">
                <!-- Digital Products Section -->
                <div id="cart-section-digital" class="cart-type-section" style="display: none;">
                  <div class="cart-section-header">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                      <polyline points="7 10 12 15 17 10"></polyline>
                      <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    <h3>Digital Products</h3>
                  </div>
                  <div id="cart-items-digital" class="cart-items">
                    <!-- Digital cart items rendered here -->
                  </div>
                </div>

                <!-- Physical Products Section -->
                <div id="cart-section-physical" class="cart-type-section" style="display: none;">
                  <div class="cart-section-header">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <rect x="1" y="3" width="15" height="13"></rect>
                      <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                      <circle cx="5.5" cy="18.5" r="2.5"></circle>
                      <circle cx="18.5" cy="18.5" r="2.5"></circle>
                    </svg>
                    <h3>Physical Products</h3>
                  </div>
                  <div id="cart-items-physical" class="cart-items">
                    <!-- Physical cart items rendered here -->
                  </div>
                </div>

                <div class="cart-empty" id="cart-empty" style="display: none;">
                    <img src="img/cart-icon.webp" alt="Empty Cart" />
                    <h2>Your cart is empty</h2>
                    <p>Looks like you haven't added anything to your cart yet.</p>
                    <a href="shopAll.php" class="btn-primary">Continue Shopping</a>
                </div>
              </div>

              <!-- Cart Summary -->
              <div class="cart-summary-wrapper">
                <div class="cart-summary">
                  <h2 class="summary-title">Order Summary</h2>

                  <div class="summary-details">
                    <div class="summary-row">
                      <span>Subtotal</span>
                      <span id="cart-subtotal">₹0</span>
                    </div>
                    <div class="summary-row">
                      <span>Shipping</span>
                      <span id="cart-shipping">Calculated at checkout</span>
                    </div>
                    <div class="summary-row">
                      <span>Tax</span>
                      <span id="cart-tax">₹0</span>
                    </div>
                    <div class="summary-divider"></div>
                    <div class="summary-row total-row">
                      <span>Total</span>
                      <span id="cart-total">₹0</span>
                    </div>
                  </div>

                  <a href="checkout.php" class="btn-primary checkout-btn" id="checkout-btn" style="display: none;" onclick="return checkAuthBeforeCheckout(event);">
                    Proceed to Checkout
                  </a>
                  <a href="shopAll.php" class="btn-ghost continue-shopping">
                    Continue Shopping
                  </a>

                  <div class="cart-security">
                    <div class="security-item">
                      <img src="img/m4.webp" alt="Secure" />
                      <span>Secure Checkout</span>
                    </div>
                    <div class="security-item">
                      <img src="img/m2.webp" alt="Fast" />
                      <span>Fast Delivery</span>
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
                  href="mailto:<?php echo htmlspecialchars(getenv('SUPPORT_EMAIL') ?: 'support@uxpacific.com'); ?>"
                  style="text-decoration: none; color: inherit"
                  target="_blank"
                  ><?php echo htmlspecialchars(getenv('SUPPORT_EMAIL') ?: 'support@uxpacific.com'); ?></a
                >
                &nbsp;&nbsp;&nbsp;&nbsp;
              </p>
            </div>
          </div>
        </div>

        <div class="footer-bottom">
          <p>&copy; <?php echo date('Y'); ?> UX Pacific. All rights reserved.</p>
          <div class="footer-links">
            <a href="policies.php" target="">Our Policies </a>
            <span>•</span>
            <a href="contact.php" style="text-decoration: none;">Contact Us</a>
          </div>
        </div>
      </footer>
    </div>

    <script src="<?php echo htmlspecialchars(asset_url('script.js')); ?>"></script>
  </body>
</html>


