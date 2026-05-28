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
            minlength="6"
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
          <label class="auth-premium-check">
            <input type="checkbox" name="remember" />
            <span>Remember me</span>
          </label>
          <a href="forgot-password.php" class="auth-premium-link">Forgot password?</a>
        </div>

        <button type="submit" class="auth-premium-btn" id="signin-btn">
          <span id="signin-text">Sign in</span>
          <span id="signin-loader" style="display:none;">Signing in…</span>
        </button>
      </form>

      <button type="button" class="auth-premium-google" onclick="signInWithGoogle()">
        <svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true">
          <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
          <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
          <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
          <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
        </svg>
        Sign in with Google
      </button>

      <p class="auth-premium-footer">
        Don&rsquo;t have an account? <a href="signup.php">Sign up</a>
      </p>
    </div>
  </div>

  <div id="otp-modal" class="auth-premium-modal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="otp-title">
    <div class="auth-premium-modal-panel">
      <button type="button" class="auth-premium-modal-close" onclick="closeOtpModal()" aria-label="Close">&times;</button>
      <h2 id="otp-title">Verify OTP</h2>
      <p>Enter the 6-digit code sent to your phone</p>
      <div class="auth-premium-field">
        <input type="text" maxlength="6" id="otp-input" class="auth-premium-otp" placeholder="000000" inputmode="numeric" style="padding-right:14px;text-align:center;letter-spacing:0.35em;" />
      </div>
      <button type="button" class="auth-premium-btn" onclick="verifyOtp()">Verify &amp; sign in</button>
    </div>
  </div>

  <script src="script.js"></script>
  <?php include 'includes/auth-premium-scripts.php'; ?>
</body>
</html>
