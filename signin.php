<?php
require_once 'includes/config.php';
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en" class="auth-premium-root">
<head>
  <meta charset="UTF-8" />
  <title>Sign In – UX Pacific Shop</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" />
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

      <h1 class="auth-premium-title">Sign in</h1>

      <form class="auth-premium-form" id="signin-form" onsubmit="handleSignIn(event)" novalidate>
        <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_GET['redirect'] ?? ''); ?>">

        <div id="auth-error" class="auth-premium-alert auth-premium-alert--error" style="display:none;"></div>
        <div id="auth-success" class="auth-premium-alert auth-premium-alert--success" style="display:none;"></div>

        <?php if (!empty($_SESSION['error'])): ?>
          <div class="auth-premium-alert auth-premium-alert--error">
            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
          </div>
        <?php endif; ?>
        <?php if (!empty($_GET['message'])): ?>
          <div class="auth-premium-alert auth-premium-alert--success">
            <?php echo htmlspecialchars($_GET['message']); ?>
          </div>
        <?php endif; ?>

        <div class="auth-premium-field">
          <input
            id="email"
            name="email"
            type="email"
            placeholder="designer@example.com"
            autocomplete="email"
            required
          />
          <label for="email">Email</label>
          <span class="field-error-modern"></span>
        </div>

        <div class="auth-premium-field auth-premium-field--password">
          <input
            id="password"
            name="password"
            type="password"
            placeholder="••••••••"
            autocomplete="current-password"
            minlength="8"
            required
          />
          <label for="password">Password</label>
          <button type="button" class="auth-premium-toggle" onclick="togglePassword('password')" aria-label="Show password">
            <svg id="password-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
              <circle cx="12" cy="12" r="3"></circle>
            </svg>
          </button>
          <span class="field-error-modern"></span>
        </div>

        <div class="auth-premium-row">
          <a href="forgot-password.php" class="auth-premium-link">Forgot password?</a>
        </div>

        <button type="submit" class="auth-premium-btn" id="signin-btn">
          <span id="signin-text">Sign in</span>
          <span id="signin-loader" style="display:none;">Signing in…</span>
        </button>
      </form>

      <p class="auth-premium-footer">
        Don&rsquo;t have an account? <a href="signup.php">Sign up</a>
      </p>
    </div>
  </div>

  <script src="script.js"></script>
  <?php include 'includes/auth-premium-scripts.php'; ?>
</body>
</html>
