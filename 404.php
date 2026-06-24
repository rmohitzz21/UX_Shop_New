<?php require_once 'includes/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>404 - Page Not Found – UX Pacific Shop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <?php include 'includes/auth-preload.php'; ?>
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
        <section class="error-section">
          <div class="error-container">
            <div class="error-content">
              <h1 class="error-code">404</h1>
              <h2 class="error-title">Page Not Found</h2>
              <p class="error-message">
                Oops! The page you're looking for doesn't exist. It might have been moved, deleted, or the URL might be incorrect.
              </p>
              <div class="error-actions">
                <a href="index.php" class="btn-primary">Go to Homepage</a>
                <a href="shopAll.php" class="btn-ghost">Browse Products</a>
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


