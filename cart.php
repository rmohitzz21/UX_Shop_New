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
      <link rel="icon" type="image/x-icon" href="img/faviconUXP444@4x-789.png" />
    <link rel="stylesheet" href="style.css" />
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
            <li><a href="shopAll.php" class="nav-link active">Buy Now</a></li>
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
          <a href="cart.php" class="nav-mobile-link">Cart</a>
          <a href="signin.php" class="nav-mobile-link nav-mobile-cta">
            Sign in
          </a>
        </div>
      </header>

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
                      <span id="cart-subtotal">$0</span>
                    </div>
                    <div class="summary-row">
                      <span>Shipping</span>
                      <span id="cart-shipping">Calculated at checkout</span>
                    </div>
                    <div class="summary-row">
                      <span>Tax</span>
                      <span id="cart-tax">$0</span>
                    </div>
                    <div class="summary-divider"></div>
                    <div class="summary-row total-row">
                      <span>Total</span>
                      <span id="cart-total">$0</span>
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


