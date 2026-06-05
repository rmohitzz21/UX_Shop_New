<?php require_once 'includes/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <?php include 'includes/auth-preload.php'; ?>
    <meta name="description" content="Server error - UX Pacific Shop" />
    <title>Server Error (500) – UX Pacific Shop</title>
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
      rel="stylesheet"
    />
      <link rel="icon" type="image/x-icon" href="img/faviconUXP444@4x-789.png" />
    <link rel="stylesheet" href="style.css" />
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
  </head>

  <body>
    <div class="page">
      <?php include 'includes/header.php'; ?>

      <!-- MAIN CONTENT -->
      <main class="main">
        <section class="error-section">
          <div class="error-container">
            <div class="error-content">
              <div class="error-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="10"></circle>
                  <line x1="12" y1="8" x2="12" y2="12"></line>
                  <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
              </div>
              <h1 class="error-title">500</h1>
              <h2 class="error-subtitle">Server Error</h2>
              <p class="error-message">
                We're experiencing some technical difficulties. Our team has been notified and is working to fix the issue.
              </p>
              <div class="error-actions">
                <a href="index.php" class="btn-primary">Go to Homepage</a>
                <button onclick="window.location.reload()" class="btn-ghost">Try Again</button>
              </div>
              <div class="error-help">
                <p>If the problem persists, please contact our support team:</p>
                <p>
                  <a href="mailto:<?php echo htmlspecialchars(getenv('SUPPORT_EMAIL') ?: 'support@uxpacific.com'); ?>"><?php echo htmlspecialchars(getenv('SUPPORT_EMAIL') ?: 'support@uxpacific.com'); ?></a> | 
                  <a href="tel:+919274061063">+91 9274061063</a>
                </p>
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


