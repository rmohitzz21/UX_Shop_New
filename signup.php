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
  <title>Create Account | UX Pacific Shop</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="icon" type="image/png" href="img/fav.png" />
  <link rel="stylesheet" href="css/auth-premium.css" />
</head>
<body class="auth-premium-page">
  <div class="auth-premium-bg" aria-hidden="true"></div>

  <div class="auth-premium-wrap">
    <div class="auth-premium-card">
      <div class="auth-premium-brand">
        <?php include 'includes/auth-premium-logo.php'; ?>
      </div>

      <h1 class="auth-premium-title">Create New Account</h1>

      <form class="auth-premium-form" id="signup-form" onsubmit="handleSignUp(event)" novalidate>
        <div id="auth-error" class="auth-premium-alert auth-premium-alert--error" style="display:none;"></div>
        <div id="auth-success" class="auth-premium-alert auth-premium-alert--success" style="display:none;"></div>

        <div class="auth-premium-field">
          <input
            id="full-name"
            name="fullName"
            type="text"
            placeholder="Jamie Davis"
            required
            minlength="2"
            autocomplete="name"
          />
          <label for="full-name">Full name</label>
          <span class="field-error-modern"></span>
        </div>

        <div class="auth-premium-field">
          <input
            id="email"
            name="email"
            type="email"
            placeholder="designer@example.com"
            required
            autocomplete="email"
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
            required
            minlength="8"
            autocomplete="new-password"
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

        <div class="auth-premium-field auth-premium-field--password">
          <input
            id="confirm-password"
            name="confirmPassword"
            type="password"
            placeholder="••••••••"
            required
            minlength="8"
            autocomplete="new-password"
          />
          <label for="confirm-password">Confirm password</label>
          <button type="button" class="auth-premium-toggle" onclick="togglePassword('confirm-password')" aria-label="Show password">
            <svg id="confirm-password-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
              <circle cx="12" cy="12" r="3"></circle>
            </svg>
          </button>
          <span class="field-error-modern"></span>
        </div>

        <label class="auth-premium-terms">
          <input type="checkbox" name="terms" required />
          <span>I agree to the <a href="policies.php#terms">Terms &amp; Conditions</a> of UX Pacific</span>
        </label>

        <button type="submit" class="auth-premium-btn" id="signup-btn">
          <span id="signup-text">Sign up</span>
          <span id="signup-loader" style="display:none;">Creating account…</span>
        </button>
      </form>

      <p class="auth-premium-footer">
        Already have an account? <a href="signin.php">Log in</a>
      </p>
    </div>
  </div>

  <script src="<?php echo htmlspecialchars(asset_url('script.js')); ?>"></script>
  <?php include 'includes/auth-premium-scripts.php'; ?>
</body>
</html>
