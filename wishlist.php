<?php require_once 'includes/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
    <meta name="description" content="Your wishlist of favorite UX/UI design resources and products at UX Pacific Shop" />
    <meta name="keywords" content="wishlist, favorites, saved products, UX design products" />
    <title>My Wishlist – UX Pacific Shop</title>
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="style.css" />
      <link rel="icon" type="image/x-icon" href="img/faviconUXP444@4x-789.png" />
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
          <a href="cart.php" class="nav-mobile-link">Cart</a>
          <a href="signin.php" class="nav-mobile-link nav-mobile-cta">
            Sign in
          </a>
        </div>
      </header>

      <!-- MAIN CONTENT -->
      <main class="main">
        <section class="wishlist-section">
          <div class="wishlist-container">
            <div class="wishlist-header">
              <h1 class="wishlist-title">My Wishlist</h1>
              <p class="wishlist-subtitle">Save your favorite products for later</p>
            </div>

            <div id="wishlist-items" class="wishlist-grid">
              <!-- Wishlist items will be loaded here -->
            </div>

            <div id="wishlist-empty" class="wishlist-empty">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 80px; height: 80px; margin-bottom: 24px; opacity: 0.5;">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
              </svg>
              <h2>Your wishlist is empty</h2>
              <p>Start adding products you love to your wishlist</p>
              <a href="shopAll.php" class="btn-primary">Browse Products</a>
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
                Design resources and merchandise trusted by creators worldwide —
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
            <a href="policies.php">Our Policies</a>
          </div>
        </div>
      </footer>
    </div>

    <script src="script.js"></script>
    <script>
      // Wishlist functionality
      function loadWishlist() {
        const wishlist = JSON.parse(localStorage.getItem('wishlist')) || [];
        const wishlistGrid = document.getElementById('wishlist-items');
        const wishlistEmpty = document.getElementById('wishlist-empty');

        if (wishlist.length === 0) {
          wishlistGrid.style.display = 'none';
          wishlistEmpty.style.display = 'block';
          return;
        }

        wishlistEmpty.style.display = 'none';
        wishlistGrid.style.display = 'grid';
        wishlistGrid.innerHTML = wishlist.map(item => `
          <article class="product-card wishlist-card">
            <button class="wishlist-remove" onclick="removeFromWishlist('${item.id}')" title="Remove from wishlist">
              <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
              </svg>
            </button>
            <div class="product-img">
              <img src="${item.image}" alt="${item.name}" />
              <span class="product-tag">${item.category}</span>
            </div>
            <div class="product-body">
              <h3>${item.name}</h3>
              <p>${item.description || ''}</p>
              <div class="product-meta">
                <div class="product-price">$${item.price} ${item.oldPrice ? '<span>$' + item.oldPrice + '</span>' : ''}</div>
                <div class="product-rating">★ ${item.rating || '4.5'}</div>
              </div>
              <div class="product-actions">
                <button onclick="addToCart('${item.id}', null, 1, {name: '${item.name}', price: ${item.price}, image: '${item.image}'}, '${item.available_type || 'physical'}')" class="btn-primary small">Add to Cart</button>
                <a href="product.php?id=${item.id}" class="btn-ghost small">View Details</a>
              </div>
            </div>
          </article>
        `).join('');
      }

      function removeFromWishlist(productId) {
        let wishlist = JSON.parse(localStorage.getItem('wishlist')) || [];
        wishlist = wishlist.filter(item => item.id !== productId);
        localStorage.setItem('wishlist', JSON.stringify(wishlist));
        loadWishlist();
        updateWishlistCount();
        showToast('Removed from wishlist', 'success');
      }

      document.addEventListener('DOMContentLoaded', function() {
        loadWishlist();
      });

      window.removeFromWishlist = removeFromWishlist;
    </script>
  </body>
</html>


