<?php require_once 'includes/config.php'; ?>
<!DOCTYPE html>
<html lang="en" class="auth-premium-root">
<head>
  <meta charset="UTF-8" />
  <title>Forgot Password – UX Pacific Shop</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
  <?php include 'includes/auth-preload.php'; ?>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="icon" type="image/x-icon" href="img/faviconUXP444@4x-789.png" />
  <link rel="stylesheet" href="css/auth-premium.css" />
</head>
<body class="auth-premium-page">
  <div class="auth-premium-bg" aria-hidden="true"></div>

  <div class="auth-premium-wrap">
    <div class="auth-premium-card">
      <div class="auth-premium-brand">
        <?php include 'includes/auth-premium-logo.php'; ?>
      </div>

      <h1 class="auth-premium-title">Reset your password</h1>

      <form class="auth-premium-form" id="forgot-password-form" onsubmit="handleForgotPassword(event)" novalidate>
        <div id="auth-error" class="auth-premium-alert auth-premium-alert--error" style="display:none;"></div>
        <div id="auth-success" class="auth-premium-alert auth-premium-alert--success" style="display:none;"></div>

        <p class="auth-premium-intro">
          Enter your account email and we&rsquo;ll send you a secure reset link.
        </p>

        <div class="auth-premium-field">
          <input id="forgot-email" name="email" type="email" placeholder="designer@example.com" autocomplete="email" required />
          <label for="forgot-email">Email</label>
          <span class="field-error"></span>
        </div>

        <button type="submit" class="auth-premium-btn" id="reset-btn">
          <span id="reset-text">Send reset link</span>
          <span id="reset-loader" style="display:none;">Sending…</span>
        </button>
      </form>

      <p class="auth-premium-footer">
        Remembered it? <a href="signin.php">Log in</a>
      </p>
    </div>
  </div>

  <script src="script.js"></script>
</body>
</html>
