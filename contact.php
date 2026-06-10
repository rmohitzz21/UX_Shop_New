<?php require_once 'includes/config.php'; ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?php echo e($_SESSION['csrf_token'] ?? ''); ?>">
  <title>Contact Us — UX Pacific Shop</title>
  <link rel="icon" type="image/png" href="img/fav.png">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('style.css')); ?>">
  <link rel="stylesheet" href="assets/css/contact.css">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>
<body class="contact-page">
<div class="page">
  <?php include 'includes/header.php'; ?>

  <main class="contact-main">
    <section class="contact-section">
      <div class="contact-layout">

        <div class="contact-info">
          <h1 class="contact-heading">Connect <span>With Us</span></h1>
          <p class="contact-lead">
            Whether you're a startup, an established brand, or a creative team  we're here for all of it.
            Reach out for product questions, custom bundles, licensing, or partnership opportunities.
          </p>

          <div class="contact-details">
            <div class="contact-detail-item">
              <div class="contact-detail-icon" aria-hidden="true">
                <i class="ph ph-map-pin"></i>
              </div>
              <div>
                <span class="contact-detail-label">Location</span>
                <p class="contact-detail-value">
                  512, Majestic Building, Near Law Garden BRTS Stand, Ahmedabad
                </p>
              </div>
            </div>

            <div class="contact-detail-item">
              <div class="contact-detail-icon" aria-hidden="true">
                <i class="ph ph-phone"></i>
              </div>
              <div>
                <span class="contact-detail-label">Phone</span>
                <p class="contact-detail-value">
                  <a href="tel:+919274061063">+91 9274061063</a>
                </p>
              </div>
            </div>

            <div class="contact-detail-item">
              <div class="contact-detail-icon" aria-hidden="true">
                <i class="ph ph-envelope-simple"></i>
              </div>
              <div>
                <span class="contact-detail-label">Email</span>
                <p class="contact-detail-value">
                  <a href="mailto:<?php echo htmlspecialchars(getenv('SUPPORT_EMAIL') ?: 'support@uxpacific.com'); ?>">
                    <?php echo htmlspecialchars(getenv('SUPPORT_EMAIL') ?: 'support@uxpacific.com'); ?>
                  </a>
                </p>
              </div>
            </div>
          </div>

          <div class="contact-benefits">
            <ul>
              <li><i class="ph ph-check-circle" aria-hidden="true"></i> We respond within 24 hours</li>
              <li><i class="ph ph-check-circle" aria-hidden="true"></i> Free initial consultation call</li>
              <li><i class="ph ph-check-circle" aria-hidden="true"></i> No commitment required</li>
              <li><i class="ph ph-check-circle" aria-hidden="true"></i> Your data is always private</li>
            </ul>
          </div>
        </div>

        <div class="contact-form-card">
          <form id="contact-form" novalidate>
            <div class="contact-form-row">
              <div class="contact-field">
                <label for="contact-name">Name</label>
                <input id="contact-name" name="name" type="text" placeholder="Enter your name here" required autocomplete="name">
              </div>
              <div class="contact-field">
                <label for="contact-email">Email</label>
                <input id="contact-email" name="email" type="email" placeholder="Enter your email here" required autocomplete="email">
              </div>
            </div>

            <div class="contact-form-row">
              <div class="contact-field">
                <label for="contact-phone">Phone Number</label>
                <input id="contact-phone" name="phone" type="tel" placeholder="+91 xxxxx-xxxxx" autocomplete="tel">
              </div>
              <div class="contact-field">
                <label for="contact-industry">Industry</label>
                <select id="contact-industry" name="industry">
                  <option value="">Select your industry</option>
                  <option value="Design &amp; UX">Design &amp; UX</option>
                  <option value="E-commerce">E-commerce</option>
                  <option value="SaaS / Technology">SaaS / Technology</option>
                  <option value="Marketing &amp; Agency">Marketing &amp; Agency</option>
                  <option value="Education">Education</option>
                  <option value="Other">Other</option>
                </select>
              </div>
            </div>

            <div class="contact-field contact-field-full">
              <label for="contact-message">Message</label>
              <textarea id="contact-message" name="message" placeholder="Tell us about your project or question..." required></textarea>
            </div>

            <label class="contact-terms">
              <input type="checkbox" id="contact-terms" name="terms" required>
              <span>I agree to <a href="policies.php">Terms &amp; Conditions</a> of UX Pacific</span>
            </label>

            <button class="contact-submit" type="submit" id="contact-submit-btn">Send Message</button>
          </form>
        </div>

      </div>
    </section>
  </main>

  <?php include 'includes/footer.php'; ?>
</div>

<script src="<?php echo htmlspecialchars(asset_url('script.js')); ?>"></script>
<script>
(function () {
  const form = document.getElementById('contact-form');
  const submitBtn = document.getElementById('contact-submit-btn');

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const terms = document.getElementById('contact-terms');
    if (!terms.checked) {
      showToast('Please agree to the Terms & Conditions to continue.', 'error');
      return;
    }

    const payload = Object.fromEntries(new FormData(form).entries());
    delete payload.terms;

    const industry = (payload.industry || '').trim();
    payload.subject = industry ? 'Enquiry — ' + industry : 'General enquiry';
    delete payload.industry;

    submitBtn.disabled = true;
    submitBtn.classList.add('is-loading');

    try {
      const response = await fetch('api/contact/send.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': getCsrfToken()
        },
        body: JSON.stringify(payload)
      });
      const data = await response.json();
      showToast(
        data.message || (data.status === 'success' ? 'Message sent.' : 'Could not send message.'),
        data.status === 'success' ? 'success' : 'error'
      );
      if (data.status === 'success') {
        form.reset();
      }
    } catch (err) {
      showToast('Something went wrong. Please try again.', 'error');
    } finally {
      submitBtn.disabled = false;
      submitBtn.classList.remove('is-loading');
    }
  });
})();
</script>
</body>
</html>
