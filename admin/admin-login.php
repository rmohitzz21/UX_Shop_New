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
  <link rel="icon" type="image/x-icon" href="../img/faviconUXP444@4x-789.png" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      background: #020212;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
      position: relative;
      overflow: hidden;
    }

    /* Ambient background orbs */
    body::before, body::after {
      content: '';
      position: fixed;
      border-radius: 50%;
      filter: blur(120px);
      opacity: 0.18;
      pointer-events: none;
    }
    body::before {
      width: 600px; height: 600px;
      background: radial-gradient(circle, #6f4bff 0%, transparent 70%);
      top: -150px; left: -150px;
    }
    body::after {
      width: 500px; height: 500px;
      background: radial-gradient(circle, #a855f7 0%, transparent 70%);
      bottom: -100px; right: -100px;
    }

    .login-wrapper {
      width: 100%;
      max-width: 440px;
      position: relative;
      z-index: 1;
    }

    .login-card {
      background: rgba(15, 15, 35, 0.85);
      backdrop-filter: blur(24px);
      -webkit-backdrop-filter: blur(24px);
      border: 1px solid rgba(111, 75, 255, 0.25);
      border-radius: 20px;
      padding: 2.5rem 2.25rem;
      box-shadow:
        0 0 0 1px rgba(255,255,255,0.04),
        0 32px 80px rgba(0,0,0,0.55),
        0 0 60px rgba(111,75,255,0.08);
    }

    /* Logo area */
    .login-logo {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: .75rem;
      margin-bottom: 2rem;
      text-align: center;
    }
    .login-logo img {
      height: 40px;
      object-fit: contain;
      filter: brightness(1.1);
    }
    .login-badge {
      display: inline-flex;
      align-items: center;
      gap: .4rem;
      background: rgba(111,75,255,0.15);
      border: 1px solid rgba(111,75,255,0.35);
      color: #a78bfa;
      font-size: .72rem;
      font-weight: 600;
      letter-spacing: .08em;
      text-transform: uppercase;
      padding: .3rem .75rem;
      border-radius: 999px;
    }
    .login-badge svg { width: 12px; height: 12px; flex-shrink: 0; }

    .login-title {
      color: #f8fafc;
      font-size: 1.55rem;
      font-weight: 700;
      letter-spacing: -.02em;
      text-align: center;
      margin-bottom: .35rem;
    }
    .login-subtitle {
      color: rgba(148,163,184,.75);
      font-size: .85rem;
      text-align: center;
      margin-bottom: 1.75rem;
    }

    /* Form */
    .form-group {
      margin-bottom: 1.1rem;
    }
    .form-label {
      display: block;
      color: #cbd5e1;
      font-size: .8rem;
      font-weight: 500;
      margin-bottom: .45rem;
      letter-spacing: .01em;
    }
    .input-wrap {
      position: relative;
    }
    .input-wrap svg.field-icon {
      position: absolute;
      left: .85rem;
      top: 50%;
      transform: translateY(-50%);
      width: 17px; height: 17px;
      color: rgba(148,163,184,.6);
      pointer-events: none;
      flex-shrink: 0;
    }
    .form-input {
      width: 100%;
      background: rgba(15,23,42,0.7);
      border: 1px solid rgba(71,85,105,0.6);
      border-radius: 10px;
      color: #f1f5f9;
      font-size: .9rem;
      font-family: 'Inter', sans-serif;
      padding: .72rem .9rem .72rem 2.5rem;
      outline: none;
      transition: border-color .2s, box-shadow .2s, background .2s;
    }
    .form-input::placeholder { color: rgba(100,116,139,.6); }
    .form-input:focus {
      border-color: #6f4bff;
      background: rgba(15,23,42,0.9);
      box-shadow: 0 0 0 3px rgba(111,75,255,.18);
    }
    .toggle-pw {
      position: absolute;
      right: .85rem;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      color: rgba(148,163,184,.6);
      padding: .2rem;
      display: flex;
      align-items: center;
      transition: color .2s;
    }
    .toggle-pw:hover { color: #a78bfa; }
    .toggle-pw svg { width: 17px; height: 17px; }

    /* Alert messages */
    .alert {
      border-radius: 8px;
      padding: .75rem 1rem;
      font-size: .83rem;
      font-weight: 500;
      display: none;
      margin-bottom: 1.1rem;
      border: 1px solid;
    }
    .alert-error {
      background: rgba(239,68,68,.12);
      border-color: rgba(239,68,68,.3);
      color: #fca5a5;
    }
    .alert-success {
      background: rgba(16,185,129,.12);
      border-color: rgba(16,185,129,.3);
      color: #6ee7b7;
    }

    /* Submit button */
    .btn-login {
      width: 100%;
      padding: .82rem;
      background: linear-gradient(135deg, #6f4bff 0%, #8b5cf6 100%);
      border: none;
      border-radius: 10px;
      color: #fff;
      font-size: .95rem;
      font-weight: 700;
      font-family: 'Inter', sans-serif;
      cursor: pointer;
      transition: opacity .2s, transform .1s, box-shadow .2s;
      letter-spacing: .01em;
      box-shadow: 0 4px 20px rgba(111,75,255,.35);
      margin-top: .25rem;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: .5rem;
    }
    .btn-login:hover:not(:disabled) {
      opacity: .92;
      box-shadow: 0 6px 28px rgba(111,75,255,.45);
    }
    .btn-login:active:not(:disabled) { transform: scale(.98); }
    .btn-login:disabled {
      opacity: .6;
      cursor: not-allowed;
    }
    .btn-login .spinner {
      width: 18px; height: 18px;
      border: 2px solid rgba(255,255,255,.3);
      border-top-color: #fff;
      border-radius: 50%;
      animation: spin .7s linear infinite;
      display: none;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* Footer link */
    .login-footer {
      margin-top: 1.75rem;
      text-align: center;
    }
    .back-link {
      color: rgba(148,163,184,.65);
      font-size: .82rem;
      text-decoration: none;
      transition: color .2s;
      display: inline-flex;
      align-items: center;
      gap: .3rem;
    }
    .back-link:hover { color: #a78bfa; }
    .back-link svg { width: 14px; height: 14px; }

    @media (max-width: 480px) {
      .login-card { padding: 2rem 1.5rem; }
    }
  </style>
</head>
<body>
  <div class="login-wrapper">
    <div class="login-card">

      <div class="login-logo">
        <img src="../img/logo1.webp" alt="UX Pacific Shop" onerror="this.style.display='none'" />
        <span class="login-badge">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          Secure Admin Area
        </span>
      </div>

      <h1 class="login-title">Admin Login</h1>
      <p class="login-subtitle">Sign in to manage UX Pacific Shop</p>

      <div class="alert alert-error" id="login-error" role="alert"></div>
      <div class="alert alert-success" id="login-success" role="status"></div>

      <form id="admin-login-form" onsubmit="handleAdminLogin(event)" novalidate>
        <div class="form-group">
          <label class="form-label" for="admin-email">Admin Email</label>
          <div class="input-wrap">
            <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
              <polyline points="22,6 12,13 2,6"></polyline>
            </svg>
            <input id="admin-email" name="email" type="email" class="form-input"
              placeholder="admin@uxpacific.com" required autocomplete="email" />
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="admin-password">Password</label>
          <div class="input-wrap">
            <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
            <input id="admin-password" name="password" type="password" class="form-input"
              placeholder="Enter your password" required autocomplete="current-password" />
            <button type="button" class="toggle-pw" id="pw-toggle" aria-label="Toggle password visibility"
              onclick="togglePasswordVisibility()">
              <svg id="pw-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
              <svg id="pw-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                <line x1="1" y1="1" x2="23" y2="23"></line>
              </svg>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-login" id="login-btn">
          <div class="spinner" id="login-spinner"></div>
          <span id="login-btn-text">Sign In</span>
        </button>
      </form>

      <div class="login-footer">
        <a href="../index.php" class="back-link">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
          </svg>
          Back to Shop
        </a>
      </div>
    </div>
  </div>

  <script>
    function togglePasswordVisibility() {
      const input = document.getElementById('admin-password');
      const eye = document.getElementById('pw-eye');
      const eyeOff = document.getElementById('pw-eye-off');
      if (input.type === 'password') {
        input.type = 'text';
        eye.style.display = 'none';
        eyeOff.style.display = 'block';
      } else {
        input.type = 'password';
        eye.style.display = 'block';
        eyeOff.style.display = 'none';
      }
    }

    function showAlert(type, message) {
      const err = document.getElementById('login-error');
      const ok = document.getElementById('login-success');
      err.style.display = 'none';
      ok.style.display = 'none';
      if (type === 'error') { err.textContent = message; err.style.display = 'block'; }
      else { ok.textContent = message; ok.style.display = 'block'; }
    }

    function setLoading(loading) {
      const btn = document.getElementById('login-btn');
      const spinner = document.getElementById('login-spinner');
      const btnText = document.getElementById('login-btn-text');
      btn.disabled = loading;
      spinner.style.display = loading ? 'block' : 'none';
      btnText.textContent = loading ? 'Signing in...' : 'Sign In';
    }

    function handleAdminLogin(event) {
      event.preventDefault();
      const form = document.getElementById('admin-login-form');
      const email = form.email.value.trim();
      const password = form.password.value;

      if (!email || !password) {
        showAlert('error', 'Please fill in all fields.');
        return;
      }

      setLoading(true);
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

      fetch('../api/auth/admin-login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password, csrf_token: csrfToken })
      })
      .then(r => r.json())
      .then(data => {
        if (data.status === 'success') {
          showAlert('success', 'Login successful! Redirecting...');
          setTimeout(() => { window.location.href = 'admin-dashboard.php'; }, 900);
        } else {
          showAlert('error', data.message || 'Access denied.');
          setLoading(false);
        }
      })
      .catch(() => {
        showAlert('error', 'Server error. Please try again.');
        setLoading(false);
      });
    }
  </script>
</body>
</html>
