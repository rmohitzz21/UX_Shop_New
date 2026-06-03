<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/DigitalDownloadService.php';

if (empty($_SESSION['user_id'])) {
    $redirect = 'order-confirmation.php';
    if (!empty($_GET['order_id'])) {
        $redirect .= '?order_id=' . (int) $_GET['order_id'];
    }
    header('Location: signin.php?redirect=' . rawurlencode($redirect));
    exit;
}

$ocOrderId = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;
$ocUserId  = (int) $_SESSION['user_id'];
$ocOrder   = null;
$ocDownloads = [];

if ($ocOrderId > 0) {
    $ocStmt = $conn->prepare(
        'SELECT id, order_number, status, payment_status, payment_method, total, subtotal, tax, shipping, shipping_address, created_at
         FROM orders WHERE id = ? AND user_id = ? LIMIT 1'
    );
    $ocStmt->bind_param('ii', $ocOrderId, $ocUserId);
    $ocStmt->execute();
    $ocOrder = $ocStmt->get_result()->fetch_assoc();
}

if (!$ocOrder) {
    header('Location: orders.php');
    exit;
}

$ocShipping = json_decode((string) ($ocOrder['shipping_address'] ?? '{}'), true);
if (!is_array($ocShipping)) {
    $ocShipping = [];
}

$ocItemsStmt = $conn->prepare(
    'SELECT oi.quantity, oi.price, oi.size, COALESCE(oi.product_name, p.name, b.name, "Item") AS name,
            COALESCE(oi.product_image, p.image, b.image, "img/poster.webp") AS image
     FROM order_items oi
     LEFT JOIN products p ON p.id = oi.product_id AND oi.item_type = "product"
     LEFT JOIN bundles b ON b.id = oi.bundle_id AND oi.item_type = "bundle"
     WHERE oi.order_id = ?'
);
$ocItemsStmt->bind_param('i', $ocOrderId);
$ocItemsStmt->execute();
$ocItems = $ocItemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$paymentStatus = strtolower((string) ($ocOrder['payment_status'] ?? 'pending'));
$orderStatus   = strtolower((string) ($ocOrder['status'] ?? 'pending'));
if ($paymentStatus === 'paid') {
    $ocDownloads = DigitalDownloadService::getDownloadsForOrder($ocOrderId, $ocUserId, $conn);
}

$ocPayload = [
    'orderId'        => (int) $ocOrder['id'],
    'orderNumber'    => $ocOrder['order_number'],
    'status'         => $orderStatus,
    'payment_status' => $paymentStatus,
    'paymentMethod'  => $ocOrder['payment_method'],
    'total'          => (float) $ocOrder['total'],
    'subtotal'       => (float) $ocOrder['subtotal'],
    'tax'            => (float) $ocOrder['tax'],
    'shipping_cost'  => (float) $ocOrder['shipping'],
    'date'           => $ocOrder['created_at'],
    'shipping'       => $ocShipping,
    'items'          => array_map(static function (array $row): array {
        return [
            'name'     => $row['name'],
            'image'    => $row['image'],
            'quantity' => (int) $row['quantity'],
            'price'    => (float) $row['price'],
            'size'     => $row['size'] ?? '',
        ];
    }, $ocItems),
    'downloads'      => $ocDownloads,
];
?>
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
  function escOc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function money(n) {
    return '₹' + Number(n || 0).toLocaleString('en-IN');
  }

  function applyOrderData(order) {
    const statusRaw = String(order.status || order.orderStatus || 'pending').toLowerCase();
    const payStatus = String(order.payment_status || '').toLowerCase();
    const isPaid = payStatus === 'paid' || ['paid','processing','shipped','delivered'].includes(statusRaw);

    const sub = document.getElementById('oc-sub-text');
    const num = order.orderNumber || order.order_number || '';
    if (sub) {
      if (statusRaw === 'awaiting_payment') {
        sub.textContent = 'Payment not completed for order #' + num + '.';
      } else if (statusRaw === 'failed' || payStatus === 'failed') {
        sub.textContent = 'Payment failed for order #' + num + '.';
      } else if ((order.paymentMethod || order.payment_method) === 'cod' && !isPaid) {
        sub.textContent = 'Order #' + num + ' placed. Payment due on delivery.';
      } else if (Number(order.total || 0) === 0 && isPaid) {
        sub.textContent = 'Order confirmed. Your free downloads are ready.';
      } else if (isPaid) {
        sub.textContent = 'Payment confirmed. Your downloads are ready.';
      } else if (num) {
        sub.textContent = 'Order #' + num + ' confirmed.';
      }
    }

    const badge = document.getElementById('oc-status-badge');
    if (badge) {
      const labelMap = { pending:'Confirmed', awaiting_payment:'Awaiting Payment', paid:'Paid', processing:'Processing', shipped:'Shipped', delivered:'Delivered' };
      badge.textContent = labelMap[statusRaw] || 'Confirmed';
      badge.dataset.status = statusRaw;
    }

    document.getElementById('order-number')?.textContent && (document.getElementById('order-number').textContent = num || '—');
    if (num) document.getElementById('order-number').textContent = num;

    const dateRaw = order.date || order.created_at || '';
    if (dateRaw) {
      const d = new Date(dateRaw);
      const formatted = isNaN(d) ? dateRaw : d.toLocaleDateString('en-IN', {day:'numeric',month:'short',year:'numeric'});
      const el = document.getElementById('order-date');
      if (el) el.textContent = formatted;
    }

    const totalEl = document.getElementById('order-total');
    if (totalEl && order.total) totalEl.textContent = money(order.total);

    const pmEl = document.getElementById('payment-method');
    if (pmEl) {
      const pmMap = { razorpay:'Online Payment', cod:'Cash on Delivery', test:'Test Payment', card:'Card', upi:'UPI' };
      const pm = order.paymentMethod || order.payment_method || '';
      pmEl.textContent = pmMap[pm] || pm || '—';
    }

    const emailEl = document.getElementById('oc-email-address');
    const emailAddr = (order.shipping && order.shipping.email) || '';
    if (emailEl && emailAddr) emailEl.textContent = 'Sent to ' + emailAddr;

    const emailSent  = isPaid;
    const filesReady = isPaid && Array.isArray(order.downloads) && order.downloads.length > 0;
    const steps = document.querySelectorAll('#oc-timeline .oc-step');
    if (steps[0]) steps[0].classList.add('active', 'current');
    if (steps[1] && emailSent)  steps[1].classList.add('active');
    if (steps[2] && filesReady) steps[2].classList.add('active');
    if (filesReady && steps[2]) {
      steps[0].classList.remove('current');
      steps[1]?.classList.remove('current');
      steps[2].classList.add('current');
    } else if (emailSent && steps[1]) {
      steps[0].classList.remove('current');
      steps[1].classList.add('current');
    }
    const line1 = document.getElementById('oc-line-1');
    const line2 = document.getElementById('oc-line-2');
    if (line1 && emailSent)  line1.classList.add('active');
    if (line2 && filesReady) line2.classList.add('active');

    // Order total in summary card
    const ocTotal = document.getElementById('oc-total-display');
    if (ocTotal && order.total) ocTotal.textContent = money(order.total);

    if (isPaid) {
      const reviewLink = document.getElementById('confirmation-review-link');
      if (reviewLink) reviewLink.style.display = '';
    }

    // Order items
    const itemsList = document.getElementById('confirmation-items-list');
    if (itemsList && Array.isArray(order.items) && order.items.length > 0) {
      itemsList.innerHTML = order.items.map(item => `
        <div class="oc-order-item">
          <img src="${escOc(item.image || 'img/sticker.webp')}" alt="${escOc(item.name || '')}" onerror="this.src='img/sticker.webp'" width="48" height="48" style="border-radius:6px;object-fit:cover;">
          <div class="oc-order-item-details">
            <span class="oc-order-item-name">${escOc(item.name || 'Item')}</span>
            ${item.size ? `<span class="oc-order-item-meta">Size: ${escOc(item.size)}</span>` : ''}
            <span class="oc-order-item-meta">Qty: ${escOc(String(item.quantity || 1))}</span>
          </div>
          <span class="oc-order-item-price">${money((item.price || 0) * (item.quantity || 1))}</span>
        </div>`).join('');
    }

    // Downloads
    if (filesReady && Array.isArray(order.downloads) && order.downloads.length > 0) {
      renderDownloads(order.downloads);
    } else if (filesReady) {
      // Fetch downloads separately (backward compat when API didn't embed them)
      const orderId = order.orderId || order.id || '';
      if (orderId) fetchAndRenderDownloads(orderId);
    }
  }

  function renderDownloads(downloads) {
    const card = document.getElementById('oc-downloads-card');
    const list = document.getElementById('oc-downloads-list');
    if (!card || !list || downloads.length === 0) return;

    list.innerHTML = downloads.map(dl => {
      const meta = (dl.download_count || 0) + '/' + (dl.download_limit || 5) + ' downloads used'
        + (dl.expires_at ? ' · Expires ' + new Date(dl.expires_at).toLocaleDateString('en-IN', {day:'numeric',month:'short',year:'numeric'}) : '');
      let action;
      if (dl.is_available && dl.has_file) {
        action = `<a class="oc-btn oc-btn-primary oc-dl-btn" href="api/download/file.php?token=${encodeURIComponent(dl.token)}" download>
          <svg viewBox="0 0 20 20" fill="none" width="14" height="14" aria-hidden="true">
            <path d="M10 2v10M6 8l4 4 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M3 14v2a1 1 0 001 1h12a1 1 0 001-1v-2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>Download</a>`;
      } else {
        const label = !dl.has_file ? 'File pending' : (dl.download_count >= dl.download_limit ? 'Limit reached' : 'Expired');
        action = `<span class="oc-dl-unavailable">${escOc(label)}</span>`;
      }
      return `<div class="oc-dl-row">
        <div class="oc-dl-info">
          <span class="oc-dl-name">${escOc(dl.item_name || '')}</span>
          <span class="oc-dl-meta">${escOc(meta)}</span>
        </div>${action}</div>`;
    }).join('');

    card.style.display = '';
  }

  function fetchAndRenderDownloads(orderId) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    fetch('api/order/downloads.php?order_id=' + encodeURIComponent(orderId), {
      headers: { 'X-CSRF-Token': csrf }
    }).then(r => r.json()).then(res => {
      if (res.status === 'success' && Array.isArray(res.data)) renderDownloads(res.data);
    }).catch(() => {});
  }

  const serverOrder = <?php echo json_encode($ocPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
  if (serverOrder) {
    applyOrderData(serverOrder);
  }
})();
</script>
</body>
</html>
