<?php require_once 'includes/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>Forgot Password - UX Pacific Shop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
    <?php include 'includes/auth-preload.php'; ?>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="style.css" />
    <link rel="icon" type="image/x-icon" href="img/faviconUXP444@4x-789.png" />
  </head>
  <body>
    <div class="page">
      <?php include 'includes/header.php'; ?>
      <main class="auth-shell">
        <section class="auth-container">
          <div class="auth-card">
            <h1 class="auth-title">Reset your password</h1>
            <p class="auth-subtitle">Enter your account email and we will prepare a secure reset link.</p>
            <div id="auth-success" class="success-message" style="display:none"></div>
            <form class="auth-form" id="forgot-password-form" onsubmit="handleForgotPassword(event)">
              <div id="auth-error" class="error-message" style="display:none"></div>
              <div class="form-field">
                <label for="forgot-email">Email *</label>
                <input id="forgot-email" name="email" type="email" autocomplete="email" required placeholder="you@example.com" />
                <span class="field-error"></span>
              </div>
              <button type="submit" class="btn-primary auth-submit" id="reset-btn">
                <span id="reset-text">Send Reset Link</span>
                <span id="reset-loader" style="display:none">Sending...</span>
              </button>
              <p class="auth-switch">Remembered it? <a href="signin.php">Sign in</a></p>
            </form>
          </div>
        </section>
      </main>
      <?php include 'includes/footer.php'; ?>
    </div>
    <script src="script.js"></script>
  </body>
</html>
