<?php require_once 'includes/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>Sign Up – UX Pacific Shop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="style.css" />
      <link rel="icon" type="image/x-icon" href="img/faviconUXP444@4x-789.png" />
    <style>
      /* Modern Auth Page Styles */
      .auth-page-wrapper {
        min-height: 100vh;
        display: flex;
        background: #000;
        position: relative;
        overflow: hidden;
      }

      .auth-left-panel {
        flex: 1;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        position: relative;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 3rem;
        overflow: hidden;
      }

      .auth-left-panel::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="100" height="100" patternUnits="userSpaceOnUse"><path d="M 100 0 L 0 0 0 100" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100%" height="100%" fill="url(%23grid)"/></svg>');
        opacity: 0.3;
      }

      .auth-left-content {
        position: relative;
        z-index: 1;
        max-width: 500px;
        color: white;
      }

      .auth-left-content h1 {
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 1rem;
        line-height: 1.2;
      }

      .auth-left-content p {
        font-size: 1.125rem;
        line-height: 1.6;
        opacity: 0.95;
      }

      .auth-right-panel {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        background: #1a1a1a;
        min-height: 100vh;
      }

      .auth-card-modern {
        background: #2a2a2a;
        border-radius: 24px;
        padding: 3rem;
        width: 100%;
        max-width: 480px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        border: 1px solid rgba(255, 255, 255, 0.1);
      }

      .auth-card-header {
        margin-bottom: 2rem;
      }

      .auth-card-title {
        font-size: 2rem;
        font-weight: 700;
        color: #ffffff;
        margin-bottom: 0.5rem;
      }

      .auth-card-subtitle {
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.95rem;
      }

      .auth-link-modern {
        color: #667eea;
        text-decoration: none;
        font-weight: 500;
        transition: color 0.2s;
      }

      .auth-link-modern:hover {
        color: #764ba2;
      }

      .auth-form-modern {
        margin-top: 2rem;
      }

      .form-field-modern {
        margin-bottom: 1.5rem;
        position: relative;
      }

      .form-field-modern label {
        display: block;
        margin-bottom: 0.5rem;
        color: rgba(255, 255, 255, 0.9);
        font-size: 0.9rem;
        font-weight: 500;
      }

      .input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
      }

      .input-icon {
        position: absolute;
        left: 1rem;
        width: 20px;
        height: 20px;
        color: rgba(255, 255, 255, 0.5);
        z-index: 1;
        pointer-events: none;
      }

      .input-icon svg {
        width: 100%;
        height: 100%;
      }

      .input-modern {
        width: 100%;
        padding: 0.875rem 1rem 0.875rem 3rem;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        color: #ffffff;
        font-size: 1rem;
        transition: all 0.2s;
        outline: none;
      }

      .input-modern:focus {
        border-color: #667eea;
        background: rgba(255, 255, 255, 0.08);
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
      }

      .input-modern::placeholder {
        color: rgba(255, 255, 255, 0.4);
      }

      .password-toggle {
        position: absolute;
        right: 1rem;
        background: none;
        border: none;
        color: rgba(255, 255, 255, 0.5);
        cursor: pointer;
        padding: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: color 0.2s;
        z-index: 1;
      }

      .password-toggle:hover {
        color: rgba(255, 255, 255, 0.8);
      }

      .password-toggle svg {
        width: 20px;
        height: 20px;
      }

      .form-options-modern {
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
        margin-bottom: 1.5rem;
        font-size: 0.9rem;
      }

      .checkbox-modern {
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
        color: rgba(255, 255, 255, 0.8);
        cursor: pointer;
        line-height: 1.5;
      }

      .checkbox-modern input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: #667eea;
        margin-top: 0.125rem;
        flex-shrink: 0;
      }

      .btn-primary-modern {
        width: 100%;
        padding: 0.875rem;
        background-color: #6f4bff;
        color: white;
        border: none;
        border-radius: 999px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.2s, transform 0.15s;
        margin-bottom: 1.5rem;
      }

      .btn-primary-modern:hover {
        background-color: #7c59ff;
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(111, 75, 255, 0.35);
      }

      .btn-primary-modern:active {
        transform: translateY(0);
      }

      .btn-primary-modern:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
      }

      .auth-divider-modern {
        display: flex;
        align-items: center;
        margin: 1.5rem 0;
        color: rgba(255, 255, 255, 0.5);
      }

      .auth-divider-modern::before,
      .auth-divider-modern::after {
        content: '';
        flex: 1;
        height: 1px;
        background: rgba(255, 255, 255, 0.1);
      }

      .auth-divider-modern span {
        padding: 0 1rem;
        font-size: 0.9rem;
      }

      .social-btn-modern {
        width: 100%;
        padding: 0.875rem;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        color: #ffffff;
        font-size: 1rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
      }

      .social-btn-modern:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.2);
      }

      .error-message-modern {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.3);
        color: #fca5a5;
        padding: 0.875rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        font-size: 0.9rem;
        display: none;
      }

      .success-message-modern {
        background: rgba(34, 197, 94, 0.1);
        border: 1px solid rgba(34, 197, 94, 0.3);
        color: #86efac;
        padding: 0.875rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        font-size: 0.9rem;
        display: none;
      }

      .field-error-modern {
        color: #fca5a5;
        font-size: 0.85rem;
        margin-top: 0.5rem;
        display: none;
      }

      @media (min-width: 1024px) {
        .auth-left-panel {
          display: flex;
        }
      }

      @media (max-width: 768px) {
        .auth-right-panel {
          padding: 1rem;
        }

        .auth-card-modern {
          padding: 2rem 1.5rem;
        }

        .auth-left-content h1 {
          font-size: 2rem;
        }
      }
    </style>
  </head>

  <body>
    <div class="auth-page-wrapper">
      <!-- Left Panel - Welcome Message -->
      <div class="auth-left-panel">
        <div class="auth-left-content">
          <h1>Create Your Account</h1>
          <p>
            Create an account to discover premium design products. Save favorites and stay updated with new launches.
          </p>
        </div>
      </div>

      <!-- Right Panel - Sign Up Form -->
      <div class="auth-right-panel">
        <div class="auth-card-modern">
          <div class="auth-card-header">
            <h1 class="auth-card-title">Sign-Up</h1>
            <p class="auth-card-subtitle">
              Already have an Account? <a href="signin.php" class="auth-link-modern">Login</a>
            </p>
          </div>

          <form class="auth-form-modern" id="signup-form" onsubmit="handleSignUp(event)">
            <div id="auth-error" class="error-message-modern" style="display: none;"></div>
            <div id="auth-success" class="success-message-modern" style="display: none;"></div>

            <div class="form-field-modern">
              <label for="full-name">Full Name</label>
              <div class="input-wrapper">
                <span class="input-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                  </svg>
                </span>
                <input
                  id="full-name"
                  name="fullName"
                  type="text"
                  class="input-modern"
                  placeholder="Enter your full name"
                  required
                  minlength="2"
                  autocomplete="name"
                />
              </div>
              <span class="field-error-modern"></span>
            </div>

            <div class="form-field-modern">
              <label for="email">Email Address</label>
              <div class="input-wrapper">
                <span class="input-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                    <polyline points="22,6 12,13 2,6"></polyline>
                  </svg>
                </span>
                <input
                  id="email"
                  name="email"
                  type="email"
                  class="input-modern"
                  placeholder="Enter your email"
                  required
                  autocomplete="email"
                />
              </div>
              <span class="field-error-modern"></span>
            </div>

            <div class="form-field-modern">
              <label for="phone">Phone Number</label>
              <div class="input-wrapper">
                <span class="input-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                  </svg>
                </span>
                <input
                  id="phone"
                  name="phone"
                  type="tel"
                  class="input-modern"
                  placeholder="Enter your phone number"
                  required
                  autocomplete="tel"
                />
              </div>
              <span class="field-error-modern"></span>
            </div>

            <div class="form-field-modern">
              <label for="password">Password</label>
              <div class="input-wrapper">
                <span class="input-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                  </svg>
                </span>
                <input
                  id="password"
                  name="password"
                  type="password"
                  class="input-modern"
                  placeholder="Create a strong password"
                  required
                  minlength="8"
                  autocomplete="new-password"
                />
                <button type="button" class="password-toggle" onclick="togglePassword('password')">
                  <svg id="password-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                  </svg>
                </button>
              </div>
              <span class="field-error-modern"></span>
            </div>

            <div class="form-field-modern">
              <label for="confirm-password">Confirm Password</label>
              <div class="input-wrapper">
                <span class="input-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                  </svg>
                </span>
                <input
                  id="confirm-password"
                  name="confirmPassword"
                  type="password"
                  class="input-modern"
                  placeholder="Confirm your password"
                  required
                  minlength="8"
                  autocomplete="new-password"
                />
                <button type="button" class="password-toggle" onclick="togglePassword('confirm-password')">
                  <svg id="confirm-password-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                  </svg>
                </button>
              </div>
              <span class="field-error-modern"></span>
            </div>

            <div class="form-options-modern">
              <label class="checkbox-modern">
                <input type="checkbox" name="terms" required />
                <span>I Agree to <a href="policies.php#terms" class="auth-link-modern">Terms & Condition of UXPacific</a></span>
              </label>
            </div>

            <button type="submit" class="btn-primary-modern" id="signup-btn">
              <span id="signup-text">Sign-Up</span>
              <span id="signup-loader" style="display:none;">Creating account...</span>
            </button>
          </form>

          <div class="auth-divider-modern">
            <span>Or</span>
          </div>

          <button class="social-btn-modern" onclick="signUpWithGoogle()">
            <svg width="20" height="20" viewBox="0 0 24 24">
              <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
              <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
              <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
              <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            Sign-up with Google
          </button>
        </div>
      </div>
    </div>

    <script src="script.js"></script>
    <script>
      function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const eye = document.getElementById(inputId + '-eye');
        if (!input || !eye) return;
        
        if (input.type === 'password') {
          input.type = 'text';
          eye.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
        } else {
          input.type = 'password';
          eye.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
        }
      }
      window.togglePassword = togglePassword;

      // Update signup handler with phone validation
      const originalHandleSignUp = window.handleSignUp;
      if (originalHandleSignUp) {
        window.handleSignUp = function(event) {
          event.preventDefault();
          const form = event.target;
          
          // --- Phone Validation ---
          const phoneInput = form.phone;
          if (phoneInput) {
            const phone = phoneInput.value.trim();
            // Validate: 10 digits, numeric only (simple validation)
            const phoneRegex = /^\d{10}$/;
            
            // Clear previous error first
            const errorSpan = phoneInput.parentElement.parentElement.querySelector('.field-error-modern');
            if (errorSpan) {
                errorSpan.style.display = 'none';
                errorSpan.textContent = '';
            }
            phoneInput.style.borderColor = '';

            if (phone && !phoneRegex.test(phone)) {
               // Show error
               if (errorSpan) {
                   errorSpan.textContent = 'Please enter a valid 10-digit phone number';
                   errorSpan.style.display = 'block';
               }
               phoneInput.style.borderColor = '#ef4444';
               return; // Stop submission
            }
          }
          // ------------------------

          const fullName = form.fullName?.value.trim() || '';
          const names = fullName.split(' ');
          const firstName = names[0] || '';
          const lastName = names.slice(1).join(' ') || '';
          
          // Temporarily set firstName and lastName for compatibility
          if (!form.firstName) {
            const firstNameInput = document.createElement('input');
            firstNameInput.type = 'hidden';
            firstNameInput.name = 'firstName';
            firstNameInput.value = firstName;
            form.appendChild(firstNameInput);
          } else {
            form.firstName.value = firstName;
          }
          
          if (!form.lastName) {
            const lastNameInput = document.createElement('input');
            lastNameInput.type = 'hidden';
            lastNameInput.name = 'lastName';
            lastNameInput.value = lastName;
            form.appendChild(lastNameInput);
          } else {
            form.lastName.value = lastName;
          }
          
          return originalHandleSignUp.call(this, event);
        };
      }
    </script>
  </body>
</html>

