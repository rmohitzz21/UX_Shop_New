<?php require_once 'includes/config.php'; ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?php echo e($_SESSION['csrf_token'] ?? ''); ?>">
  <title>Order Confirmed — UX Pacific Shop</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Gabarito:wght@500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="assets/css/order-confirmation.css">
</head>
<body class="oc-page">
<div class="page">
  <?php include 'includes/header.php'; ?>

  <main class="oc-main">
    <div class="oc-wrap">

      <!-- ── Animated checkmark ── -->
      <div class="oc-icon-wrap">
        <svg class="oc-check-svg" viewBox="0 0 88 88" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <circle class="oc-check-circle" cx="44" cy="44" r="41" stroke="#7c5dfa" stroke-width="2.5"/>
          <polyline class="oc-check-mark" points="26,45 38,57 62,33" stroke="#fff" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <div class="oc-icon-glow"></div>
      </div>

      <!-- ── Heading ── -->
      <div class="oc-eyebrow">Purchase Successful</div>
      <h1 class="oc-heading">You're all set!</h1>
      <p class="oc-sub" id="oc-sub-text">Your digital files are on their way to your inbox.</p>
      <span class="oc-status-badge" id="oc-status-badge">Confirmed</span>

      <!-- ── Email confirmation banner ── -->
      <div class="oc-email-banner">
        <div class="oc-email-banner-icon">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
            <polyline points="22,6 12,13 2,6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <div class="oc-email-banner-body">
          <strong>Download link sent to your email</strong>
          <span id="oc-email-address">Check your inbox for order confirmation and download instructions.</span>
        </div>
        <div class="oc-email-banner-check">
          <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
            <path d="M4 10l4 4 8-8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
      </div>
      <p class="oc-spam-hint">Didn't get it? Check your spam folder or <a href="orders.php">access downloads from your orders page</a>.</p>

      <!-- ── Meta info ── -->
      <div class="oc-meta-row">
        <div class="oc-meta-card">
          <span class="oc-meta-label">Order</span>
          <span class="oc-meta-value" id="order-number">—</span>
        </div>
        <div class="oc-meta-card">
          <span class="oc-meta-label">Date</span>
          <span class="oc-meta-value" id="order-date">—</span>
        </div>
        <div class="oc-meta-card">
          <span class="oc-meta-label">Total</span>
          <span class="oc-meta-value" id="order-total">—</span>
        </div>
        <div class="oc-meta-card">
          <span class="oc-meta-label">Payment</span>
          <span class="oc-meta-value" id="payment-method">—</span>
        </div>
      </div>

      <!-- ── Digital delivery timeline (3 steps) ── -->
      <div class="oc-timeline" id="oc-timeline" aria-label="Delivery progress">

        <div class="oc-step active current" data-step="confirmed">
          <div class="oc-step-dot">
            <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
              <path d="M4 10l4 4 8-8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="oc-step-label">Confirmed</div>
        </div>

        <div class="oc-step-line" id="oc-line-1"></div>

        <div class="oc-step" data-step="email">
          <div class="oc-step-dot">
            <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
              <path d="M3 4h14c.8 0 1.5.7 1.5 1.5v9c0 .8-.7 1.5-1.5 1.5H3c-.8 0-1.5-.7-1.5-1.5v-9C1.5 4.7 2.2 4 3 4z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
              <polyline points="17.5,5 10,11.5 2.5,5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="oc-step-label">Email Sent</div>
        </div>

        <div class="oc-step-line" id="oc-line-2"></div>

        <div class="oc-step" data-step="ready">
          <div class="oc-step-dot">
            <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
              <path d="M10 2v10M6 8l4 4 4-4" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M3 14v2a1 1 0 001 1h12a1 1 0 001-1v-2" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
            </svg>
          </div>
          <div class="oc-step-label">Ready to Download</div>
        </div>

      </div>

      <!-- ── Download Files ── -->
      <div class="oc-card" id="oc-downloads-card" style="display:none;">
        <div class="oc-card-header">
          <div class="oc-card-header-icon">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M12 2v10M6 8l4 4 4-4" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M3 14v2a1 1 0 001 1h12a1 1 0 001-1v-2" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
            </svg>
          </div>
          <h2 class="oc-card-title">Download Your Files</h2>
        </div>
        <div id="oc-downloads-list" class="oc-downloads-list"></div>
      </div>

      <!-- ── Order summary ── -->
      <div class="oc-card">
        <div class="oc-card-header">
          <div class="oc-card-header-icon">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
              <line x1="3" y1="6" x2="21" y2="6" stroke="currentColor" stroke-width="1.75"/>
              <path d="M16 10a4 4 0 01-8 0" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
            </svg>
          </div>
          <h2 class="oc-card-title">Order Summary</h2>
        </div>
        <div id="confirmation-items-list"></div>
        <div class="oc-order-total-row">
          <span>Order Total</span>
          <strong id="oc-total-display">—</strong>
        </div>
      </div>

      <!-- ── How to access your files ── -->
      <div class="oc-card">
        <div class="oc-card-header">
          <div class="oc-card-header-icon">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z" stroke="currentColor" stroke-width="1.75"/>
              <path d="M12 8v4l3 3" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
            </svg>
          </div>
          <h2 class="oc-card-title">How to access your files</h2>
        </div>
        <div class="oc-next-steps">
          <div class="oc-next-step">
            <span class="oc-next-num">01</span>
            <div>
              <strong>Check your email</strong>
              <p>We've sent a confirmation email with your unique download link. It should arrive within a few minutes.</p>
            </div>
          </div>
          <div class="oc-next-step">
            <span class="oc-next-num">02</span>
            <div>
              <strong>Click the download link</strong>
              <p>Open the email and click the secure link to instantly download your digital files.</p>
            </div>
          </div>
          <div class="oc-next-step">
            <span class="oc-next-num">03</span>
            <div>
              <strong>Access anytime from My Orders</strong>
              <p>Your files are also saved in your account. Go to <a href="orders.php" class="oc-inline-link">My Orders</a> to re-download whenever you need.</p>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Action buttons ── -->
      <div class="oc-actions">
        <a class="oc-btn oc-btn-primary" href="orders.php">
          <svg viewBox="0 0 20 20" fill="none" width="16" height="16" aria-hidden="true">
            <path d="M10 2v10M6 8l4 4 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M3 14v2a1 1 0 001 1h12a1 1 0 001-1v-2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
          My Orders &amp; Downloads
        </a>
        <a class="oc-btn oc-btn-outline" href="orders.php?review=1" id="confirmation-review-link" style="display:none;">
          <svg viewBox="0 0 20 20" fill="none" width="16" height="16" aria-hidden="true">
            <path d="M10 2l2.4 4.8 5.4.8-3.9 3.8.9 5.4-4.8-2.6-4.8 2.6.9-5.4L2.2 7.6l5.4-.8L10 2z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
          </svg>
          Leave a Review
        </a>
        <a class="oc-btn oc-btn-ghost" href="shopAll.php">Continue Shopping</a>
      </div>

    </div>
  </main>

  <?php include 'includes/footer.php'; ?>
</div>

<script src="script.js"></script>
<script>
(function () {
  const order = JSON.parse(localStorage.getItem('lastOrder') || '{}');
  const statusRaw = String(order.status || 'pending').toLowerCase();

  // Sub-text
  if (order.orderNumber) {
    const sub = document.getElementById('oc-sub-text');
    if (sub) sub.textContent = 'Order #' + order.orderNumber + ' confirmed — your files are on their way.';
  }

  // Status badge
  const badge = document.getElementById('oc-status-badge');
  if (badge) {
    const labelMap = { pending: 'Confirmed', paid: 'Paid', processing: 'Processing', delivered: 'Delivered' };
    badge.textContent = labelMap[statusRaw] || 'Confirmed';
    badge.dataset.status = statusRaw;
  }

  // Email address in banner
  const emailEl = document.getElementById('oc-email-address');
  const emailAddr = (order.shipping && order.shipping.email) || '';
  if (emailEl && emailAddr) {
    emailEl.textContent = 'Sent to ' + emailAddr;
  }

  // Digital timeline: confirmed(0) → email(1) → ready(2)
  // email is sent for paid/processing/delivered; ready when paid/delivered
  const emailSent   = ['paid', 'processing', 'shipped', 'delivered'].includes(statusRaw);
  const filesReady  = ['paid', 'processing', 'shipped', 'delivered'].includes(statusRaw);

  const steps = document.querySelectorAll('#oc-timeline .oc-step');
  // Step 0: Confirmed — always active on this page
  // Step 1: Email Sent
  // Step 2: Ready to Download
  if (steps[0]) { steps[0].classList.add('active', 'current'); }
  if (steps[1] && emailSent)  { steps[1].classList.add('active'); }
  if (steps[2] && filesReady) { steps[2].classList.add('active'); }

  // Remove 'current' from step 0 if a later step is active
  if (filesReady && steps[2]) {
    steps[0].classList.remove('current');
    steps[1].classList.remove('current');
    steps[2].classList.add('current');
  } else if (emailSent && steps[1]) {
    steps[0].classList.remove('current');
    steps[1].classList.add('current');
  }

  // Connector lines
  const line1 = document.getElementById('oc-line-1');
  const line2 = document.getElementById('oc-line-2');
  if (line1 && emailSent)  line1.classList.add('active');
  if (line2 && filesReady) line2.classList.add('active');

  // Mirror order total in summary footer
  if (order.total) {
    const totalDisplay = document.getElementById('oc-total-display');
    if (totalDisplay) totalDisplay.textContent = '₹' + Number(order.total).toLocaleString('en-IN');
  }

  // Review link
  if (['paid', 'processing', 'shipped', 'delivered', 'pending'].includes(statusRaw)) {
    const reviewLink = document.getElementById('confirmation-review-link');
    if (reviewLink) reviewLink.style.display = '';
  }

  // Fetch download tokens for this order
  const orderId = order.orderId;
  if (orderId && ['paid', 'processing', 'shipped', 'delivered'].includes(statusRaw)) {
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrf = csrfMeta ? csrfMeta.getAttribute('content') : '';
    fetch('api/order/downloads.php?order_id=' + encodeURIComponent(orderId), {
      headers: { 'X-CSRF-Token': csrf }
    })
      .then(r => r.json())
      .then(res => {
        if (res.status !== 'success' || !Array.isArray(res.data) || res.data.length === 0) return;
        const card = document.getElementById('oc-downloads-card');
        const list = document.getElementById('oc-downloads-list');
        if (!card || !list) return;

        function escOc(s) {
          return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        list.innerHTML = res.data.map(dl => {
          const meta = dl.download_count + '/' + dl.download_limit + ' downloads used'
            + (dl.expires_at ? ' · Expires ' + new Date(dl.expires_at).toLocaleDateString('en-IN', {day:'numeric',month:'short',year:'numeric'}) : '');
          let action;
          if (dl.is_available && dl.has_file) {
            action = `<a class="oc-btn oc-btn-primary oc-dl-btn" href="api/download/file.php?token=${encodeURIComponent(dl.token)}" download>
              <svg viewBox="0 0 20 20" fill="none" width="14" height="14" aria-hidden="true">
                <path d="M10 2v10M6 8l4 4 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M3 14v2a1 1 0 001 1h12a1 1 0 001-1v-2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>Download
            </a>`;
          } else {
            const label = !dl.has_file ? 'File pending' : (dl.download_count >= dl.download_limit ? 'Limit reached' : 'Expired');
            action = `<span class="oc-dl-unavailable">${escOc(label)}</span>`;
          }
          return `<div class="oc-dl-row">
            <div class="oc-dl-info">
              <span class="oc-dl-name">${escOc(dl.item_name)}</span>
              <span class="oc-dl-meta">${escOc(meta)}</span>
            </div>
            ${action}
          </div>`;
        }).join('');

        card.style.display = '';
      })
      .catch(() => {});
  }
})();
</script>
</body>
</html>
