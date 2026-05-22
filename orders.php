<?php require_once 'includes/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>My Orders – UX Pacific Shop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
    <?php include 'includes/auth-preload.php'; ?>
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
        <section class="orders-section">
          <div class="orders-container">
            <h1 class="orders-title">My Orders</h1>
            <p class="orders-subtitle">View and track all your orders</p>

            <!-- Orders List -->
            <div id="orders-list" class="orders-list">
              <!-- Orders will be loaded here -->
              <div class="orders-empty" id="orders-empty">
                <img src="img/cart-icon.webp" alt="No orders found" />
                <h2>No orders yet</h2>
                <p>You haven't placed any orders yet. Start shopping to see your orders here.</p>
                <a href="shopAll.php" class="btn-primary">Start Shopping</a>
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
    <script>
      // Load orders
      document.addEventListener('DOMContentLoaded', function() {
        loadOrders();
      });

      function loadOrders() {
        const userSession = JSON.parse(localStorage.getItem('userSession'));
        if (!userSession || !userSession.id) {
           // Not logged in
           window.location.href = 'signin.php?redirect=orders.php';
           return;
        }

        fetch('api/order/get.php')
          .then(response => response.json())
          .then(data => {
            const ordersList = document.getElementById('orders-list');
            const ordersEmpty = document.getElementById('orders-empty');
            
            if (data.status === 'success' && data.data.length > 0) {
              const orders = data.data;
              ordersEmpty.style.display = 'none';
              
              ordersList.innerHTML = orders.map(order => `
                <div class="order-card">
                  <div class="order-header">
                    <div class="order-info">
                      <h3>Order #${order.orderNumber}</h3>
                      <p class="order-date">Placed on ${new Date(order.date).toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric' 
                      })}</p>
                    </div>
                    <div class="order-status">
                      <span class="status-badge status-${order.status.toLowerCase()}">${order.status}</span>
                    </div>
                  </div>
      
                  <div class="order-items">
                    ${order.items.map(item => `
                      <div class="order-item">
                        <img src="${item.image}" alt="${item.name}" class="order-item-image" />
                        <div class="order-item-details">
                          <h4>${item.name}</h4>
                          <p>${item.size ? `Size: ${item.size} • ` : ''}Quantity: ${item.quantity}</p>
                        </div>
                        <div class="order-item-price">$${item.price * item.quantity}</div>
                      </div>
                    `).join('')}
                  </div>
      
                  <div class="order-footer">
                    <div class="order-total">
                      <span>Total: <strong>$${order.total}</strong></span>
                    </div>
                    <div class="order-actions">
                      <a href="order-confirmation.php?order=${order.orderNumber}" class="btn-ghost small">View Details</a>
                      <a href="order-tracking.php?order=${order.orderNumber}" class="btn-primary small">Track Order</a>
                      ${String(order.status).toLowerCase() === 'delivered' ? '<button class="btn-primary small" onclick="reorder(\'' + order.orderNumber + '\')">Reorder</button>' : ''}
                    </div>
                  </div>
                </div>
              `).join('');
            } else {
              ordersEmpty.style.display = 'block';
              // ordersList.innerHTML = ''; // Keep empty div inside
            }
          })
          .catch(err => {
            console.error('Failed to load orders', err);
            document.getElementById('orders-empty').style.display = 'block';
          });
      }

      function reorder(orderNumber) {
        const orders = JSON.parse(localStorage.getItem('orders')) || [];
        const order = orders.find(o => o.orderNumber === orderNumber);
        if (order) {
          // Add items to cart
          const cart = order.items.map(item => ({
            id: item.id,
            name: item.name,
            price: item.price,
            image: item.image,
            size: item.size,
            quantity: item.quantity
          }));
          localStorage.setItem('cart', JSON.stringify(cart));
          updateCartCount();
          showToast('Items added to cart!', 'success');
          setTimeout(() => {
            window.location.href = 'cart.php';
          }, 1000);
        }
      }

      window.reorder = reorder;
    </script>
  </body>
</html>


