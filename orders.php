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
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
  </head>

  <body>
    <div class="page">
      <?php include 'includes/header.php'; ?>

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


