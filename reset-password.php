<?php
require_once 'includes/config.php';

// Read token and email from query string — they will be validated client-side before form is shown
$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$email = isset($_GET['email']) ? trim(strtolower($_GET['email'])) : '';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>Reset Password – UX Pacific Shop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" />
    <?php include 'includes/auth-preload.php'; ?>
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
      rel="stylesheet"
    />
      <link rel="icon" type="image/png" href="img/fav.png" />
    <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('style.css')); ?>" />
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
  </head>

  <body>
    <div class="page">
      <?php include 'includes/header.php'; ?>

      <!-- MAIN CONTENT -->
      <main class="main">
        <section class="auth-section">
          <div class="auth-container">
            <div class="auth-card">
              <h1 class="auth-title">Create New Password</h1>
              <p class="auth-subtitle">Enter and confirm your new password below.</p>

              <!-- Validation state (shown while checking token) -->
              <div id="token-checking" style="text-align:center;padding:20px 0">
                <p>Validating reset link…</p>
              </div>

              <!-- Invalid token state -->
              <div id="token-invalid" style="display:none">
                <div class="error-message" style="margin-bottom:24px">
                  This reset link is invalid or has expired.
                  <br>Please <a href="forgot-password.php" class="auth-link">request a new one</a>.
                </div>
              </div>

              <!-- New password form (shown after token validation) -->
              <form class="auth-form" id="reset-password-form" style="display:none" onsubmit="handleResetPassword(event)">
                <div id="reset-error" class="error-message" style="display:none"></div>

                <div class="form-field">
                  <label for="new-password">New Password *</label>
                  <input
                    id="new-password"
                    name="password"
                    type="password"
                    placeholder="At least 8 characters"
                    required
                    minlength="8"
                    autocomplete="new-password"
                  />
                  <span class="field-hint">Must be at least 8 characters</span>
                  <span class="field-error"></span>
                </div>

                <div class="form-field">
                  <label for="confirm-password">Confirm Password *</label>
                  <input
                    id="confirm-password"
                    name="confirm_password"
                    type="password"
                    placeholder="Repeat your new password"
                    required
                    minlength="8"
                    autocomplete="new-password"
                  />
                  <span class="field-error"></span>
                </div>

                <button type="submit" class="btn-primary auth-submit" id="save-btn">
                  <span id="save-text">Save New Password</span>
                  <span id="save-loader" style="display:none">Saving…</span>
                </button>
              </form>

              <!-- Success state -->
              <div id="reset-success" style="display:none">
                <div class="success-message" style="margin-bottom:24px">
                  <strong>Password updated successfully!</strong><br>
                  You can now sign in with your new password.
                </div>
                <a href="signin.php" class="btn-primary auth-submit">
                  Go to Sign In
                </a>
              </div>

              <p class="auth-footer" style="margin-top:24px">
                <a href="signin.php" class="auth-link">Back to Sign In</a>
              </p>
            </div>
          </div>
        </section>
      </main>

      <!-- FOOTER -->
      <footer class="site-footer">
        <div class="footer-bottom">
          <p>&copy; <?php echo date('Y'); ?> UX Pacific. All rights reserved.</p>
        </div>
      </footer>
    </div>

    <script>
      // Token and email from server-rendered URL params
      const RESET_TOKEN = <?php echo json_encode($token); ?>;
      const RESET_EMAIL = <?php echo json_encode($email); ?>;

      // On load: verify the token is still valid before showing the form
      (async function verifyToken() {
        if (!RESET_TOKEN || !RESET_EMAIL) {
          showInvalid();
          return;
        }
        try {
          const res = await fetch('api/auth/verify-reset-token.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token: RESET_TOKEN, email: RESET_EMAIL })
          });
          const data = await res.json();
          if (data.status === 'success') {
            document.getElementById('token-checking').style.display = 'none';
            document.getElementById('reset-password-form').style.display = 'block';
          } else {
            showInvalid(data.message);
          }
        } catch (e) {
          showInvalid('Network error. Please try again.');
        }
      })();

      function showInvalid(msg) {
        document.getElementById('token-checking').style.display = 'none';
        const el = document.getElementById('token-invalid');
        if (msg) el.querySelector('.error-message').textContent = msg + ' Please request a new reset link.';
        el.style.display = 'block';
      }

      async function handleResetPassword(event) {
        event.preventDefault();
        const form = event.target;
        const password = form.password.value;
        const confirmPassword = form.confirm_password.value;
        const btn = document.getElementById('save-btn');
        const btnText = document.getElementById('save-text');
        const btnLoader = document.getElementById('save-loader');
        const errorDiv = document.getElementById('reset-error');

        errorDiv.style.display = 'none';

        if (password.length < 8) {
          errorDiv.textContent = 'Password must be at least 8 characters.';
          errorDiv.style.display = 'block';
          return;
        }
        if (password !== confirmPassword) {
          errorDiv.textContent = 'Passwords do not match.';
          errorDiv.style.display = 'block';
          return;
        }

        btn.disabled = true;
        btnText.style.display = 'none';
        btnLoader.style.display = 'inline';

        try {
          const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
          const res = await fetch('api/auth/reset-password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              token: RESET_TOKEN,
              email: RESET_EMAIL,
              password,
              confirm_password: confirmPassword,
              csrf_token: csrfToken
            })
          });
          const data = await res.json();
          if (data.status === 'success') {
            form.style.display = 'none';
            document.getElementById('reset-success').style.display = 'block';
          } else {
            errorDiv.textContent = data.message || 'Reset failed. Please try again.';
            errorDiv.style.display = 'block';
          }
        } catch (e) {
          errorDiv.textContent = 'Network error. Please try again.';
          errorDiv.style.display = 'block';
        }

        btn.disabled = false;
        btnText.style.display = 'inline';
        btnLoader.style.display = 'none';
      }
    </script>
    <script src="<?php echo htmlspecialchars(asset_url('script.js')); ?>"></script>
  </body>
</html>
