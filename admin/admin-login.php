<?php
require_once '../includes/config.php';
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
if (!empty($_SESSION['admin_id'])) {
    header('Location: admin-dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>Admin Login – UX Pacific Shop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
      rel="stylesheet"
    />
          <link rel="icon" type="image/x-icon" href="../img/faviconUXP444@4x-789.png" />

    <link rel="stylesheet" href="../style.css" />
    <style>
      .admin-login-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 2rem;
      }
      .admin-login-card {
        background: #01010d;
        border-radius: 16px;
        padding: 3rem;
        max-width: 450px;
        width: 100%;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      }
      .admin-login-title {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: #fff;
      }
      .admin-login-subtitle {
        color: #666;
        margin-bottom: 2rem;
      }
      .admin-warning {
        background: #fff3cd;
        border: 1px solid #ffc107;
        color: #856404;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        font-size: 0.9rem;
      }
    </style>
  </head>

  <body>
    <div class="admin-login-container">
      <div class="admin-login-card">
        <h1 class="admin-login-title">Admin Login</h1>
        <p class="admin-login-subtitle">Access the admin dashboard</p>
        
        <div class="admin-warning">
          ⚠️ This is a restricted area. Only authorized administrators can access.
        </div>

        <form class="auth-form" id="admin-login-form" onsubmit="handleAdminLogin(event)">
          <div id="admin-error" class="error-message" style="display: none;"></div>
          <div id="admin-success" class="success-message" style="display: none;"></div>

          <div class="form-field">
            <label for="admin-email">Admin Email *</label>
            <input
              id="admin-email"
              name="email"
              type="email"
              placeholder="admin@uxpacific.com"
              required
              autocomplete="email"
            />
            <span class="field-error"></span>
          </div>

          <div class="form-field">
            <label for="admin-password">Password *</label>
            <input
              id="admin-password"
              name="password"
              type="password"
              placeholder="Enter admin password"
              required
              minlength="6"
              autocomplete="current-password"
            />
            <span class="field-error"></span>
          </div>

          <button type="submit" class="btn-primary auth-submit" id="admin-login-btn" style="width: 100%;">
            <span id="admin-login-text">Sign In as Admin</span>
            <span id="admin-login-loader" style="display:none;">Signing in...</span>
          </button>
        </form>

        <div style="margin-top: 2rem; text-align: center;">
          <a href="../index.php" class="auth-link">← Back to Shop</a>
        </div>
      </div>
    </div>

    <!-- Define Handler BEFORE it is needed -->
    <script>
      function handleAdminLogin(event) {
        event.preventDefault(); // STOP RELOAD
        
        const form = document.getElementById('admin-login-form');
        const email = form.email.value.trim();
        const password = form.password.value;
        const btn = document.getElementById('admin-login-btn');
        const btnText = document.getElementById('admin-login-text');
        const btnLoader = document.getElementById('admin-login-loader');
        const errorDiv = document.getElementById('admin-error');
        const successDiv = document.getElementById('admin-success');
        
        // Clear previous messages
        errorDiv.style.display = 'none';
        successDiv.style.display = 'none';
        
        if (!email || !password) {
          errorDiv.textContent = 'Please fill in all fields';
          errorDiv.style.display = 'block';
          return;
        }
        
        // Show loading
        btn.disabled = true;
        btnText.style.display = 'none';
        btnLoader.style.display = 'inline';
        
        // Call API
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        fetch('../api/auth/admin-login.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email, password, csrf_token: csrfToken })
        })
        .then(response => response.json())
        .then(data => {
          if (data.status === 'success') {
            successDiv.textContent = 'Login successful! Redirecting...';
            successDiv.style.display = 'block';
            setTimeout(() => {
              window.location.href = 'admin-dashboard.php';
            }, 1000);
          } else {
            errorDiv.textContent = data.message || 'Access denied.';
            errorDiv.style.display = 'block';
            btn.disabled = false;
            btnText.style.display = 'inline';
            btnLoader.style.display = 'none';
          }
        })
        .catch(err => {
          console.error('Login error:', err);
          errorDiv.textContent = 'Server error. Please try again.';
          errorDiv.style.display = 'block';
          btn.disabled = false;
          btnText.style.display = 'inline';
          btnLoader.style.display = 'none';
        });
      }

      // Check if already logged in handled by PHP session, no JS needed
      document.addEventListener('DOMContentLoaded', function() {
         // Optional: You could check an API endpoint here to see if session is valid 
         // and redirect if so, but PHP handling on dashboard.php is sufficient.
      });
    </script>
    
    <!-- Move External Script to Bottom to avoid blocking -->
    <script src="../script.js"></script>
  </body>
</html>


