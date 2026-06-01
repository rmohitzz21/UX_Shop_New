<?php
require_once 'includes/config.php';
if (empty($_SESSION['user_id'])) {
    header('Location: signin.php?redirect=account.php');
    exit;
}
$uid = (int) $_SESSION['user_id'];
$stmt = $conn->prepare('SELECT email, first_name, last_name, phone, created_at FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$orderStats = [
    'total_orders' => 0,
    'recent_orders' => 0,
    'total_spent' => 0.0,
];
$statsStmt = $conn->prepare("
    SELECT
        COUNT(*) AS total_orders,
        SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS recent_orders,
        COALESCE(SUM(CASE WHEN status NOT IN ('cancelled', 'failed') THEN total ELSE 0 END), 0) AS total_spent
    FROM orders
    WHERE user_id = ?
");
if ($statsStmt) {
    $statsStmt->bind_param('i', $uid);
    $statsStmt->execute();
    $row = $statsStmt->get_result()->fetch_assoc();
    if ($row) {
        $orderStats['total_orders'] = (int) ($row['total_orders'] ?? 0);
        $orderStats['recent_orders'] = (int) ($row['recent_orders'] ?? 0);
        $orderStats['total_spent'] = (float) ($row['total_spent'] ?? 0);
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?php echo e($_SESSION['csrf_token'] ?? ''); ?>">
  <title>Account - UX Pacific Shop</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    .account-shell {
      max-width: 1180px;
      margin: 0 auto;
      padding: 28px 20px 56px;
      display: grid;
      gap: 20px;
    }
    .account-welcome {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      flex-wrap: wrap;
      padding: 20px 22px;
      border-radius: 18px;
      border: 1px solid rgba(255,255,255,0.1);
      background: linear-gradient(160deg, rgba(109,61,255,0.16), rgba(9,9,28,0.88));
    }
    .account-welcome h2 { margin: 0 0 6px; font-size: 1.45rem; color: #fff; }
    .account-welcome p { margin: 0; color: rgba(255,255,255,0.72); }
    .account-stats {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 12px;
      width: min(520px, 100%);
    }
    .account-stat {
      border: 1px solid rgba(255,255,255,0.11);
      border-radius: 14px;
      background: rgba(7,7,22,0.62);
      padding: 12px 14px;
      text-align: center;
    }
    .account-stat b {
      display: block;
      font-size: 1.05rem;
      color: #fff;
      margin-bottom: 2px;
    }
    .account-stat span {
      font-size: 0.74rem;
      color: rgba(255,255,255,0.66);
      text-transform: uppercase;
      letter-spacing: 0.06em;
    }
    .account-grid-modern {
      display: grid;
      grid-template-columns: minmax(0, 1.35fr) minmax(280px, 0.8fr);
      gap: 18px;
    }
    .account-card {
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 18px;
      background: rgba(7,7,20,0.82);
      padding: 20px;
    }
    .account-card h3 {
      margin: 0 0 14px;
      color: #fff;
      font-size: 1.05rem;
    }
    .account-form-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 12px;
    }
    .account-form-grid .full { grid-column: 1 / -1; }
    .account-form-grid label {
      display: grid;
      gap: 6px;
      color: rgba(255,255,255,0.8);
      font-size: 0.86rem;
    }
    .account-form-grid input {
      height: 44px;
      border-radius: 12px;
      border: 1px solid rgba(255,255,255,0.15);
      background: rgba(255,255,255,0.03);
      color: #fff;
      padding: 0 12px;
      font: inherit;
    }
    .account-form-grid input[readonly] { opacity: 0.72; cursor: not-allowed; }
    .account-form-actions {
      margin-top: 16px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      flex-wrap: wrap;
    }
    .account-meta {
      font-size: 0.82rem;
      color: rgba(255,255,255,0.56);
    }
    .account-links {
      display: grid;
      gap: 10px;
    }
    .account-link {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 12px 13px;
      border-radius: 12px;
      border: 1px solid rgba(255,255,255,0.1);
      color: #f2e8ff;
      text-decoration: none;
      background: rgba(255,255,255,0.02);
    }
    .account-link small { color: rgba(255,255,255,0.6); }
    .account-link:hover { border-color: rgba(109,61,255,0.5); background: rgba(109,61,255,0.1); }
    @media (max-width: 900px) {
      .account-grid-modern { grid-template-columns: 1fr; }
      .account-form-grid { grid-template-columns: 1fr; }
      .account-stats { grid-template-columns: 1fr 1fr 1fr; }
    }
    @media (max-width: 640px) {
      .account-stats { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
<div class="page">
  <?php include 'includes/header.php'; ?>
  <main>
    <section class="account-shell">
      <div class="account-welcome">
        <div>
          <h2>Hi <?php echo htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'there'); ?> 👋</h2>
          <p>Manage your profile, check order activity, and keep your account updated.</p>
        </div>
        <div class="account-stats">
          <div class="account-stat">
            <b><?php echo (int) $orderStats['total_orders']; ?></b>
            <span>Total Orders</span>
          </div>
          <div class="account-stat">
            <b><?php echo (int) $orderStats['recent_orders']; ?></b>
            <span>Last 30 Days</span>
          </div>
          <div class="account-stat">
            <b>₹<?php echo number_format((float) $orderStats['total_spent'], 0); ?></b>
            <span>Total Spent</span>
          </div>
        </div>
      </div>

      <div class="account-grid-modern">
        <form class="account-card" id="account-form">
          <h3>Edit Profile</h3>
          <div class="account-form-grid">
            <label>
              First name
              <input name="first_name" value="<?php echo e($user['first_name'] ?? ''); ?>" required minlength="2">
            </label>
            <label>
              Last name
              <input name="last_name" value="<?php echo e($user['last_name'] ?? ''); ?>" required minlength="2">
            </label>
            <label class="full">
              Email
              <input name="email" type="email" value="<?php echo e($user['email'] ?? ''); ?>" readonly>
            </label>
            <label class="full">
              Phone
              <input name="phone" value="<?php echo e($user['phone'] ?? ''); ?>" placeholder="+91 98765 43210">
            </label>
          </div>
          <div class="account-form-actions">
            <span class="account-meta">Member since: <?php echo !empty($user['created_at']) ? date('d M Y', strtotime((string) $user['created_at'])) : '—'; ?></span>
            <button class="btn btn-primary" type="submit">Save Profile</button>
          </div>
        </form>

        <aside class="account-card">
          <h3>Quick Actions</h3>
          <div class="account-links">
            <a class="account-link" href="orders.php">
              <span>My Orders<br><small>Track recent and past orders</small></span>
              <span>→</span>
            </a>
<a class="account-link" href="cart.php">
              <span>Cart<br><small>Review pending checkout items</small></span>
              <span>→</span>
            </a>
          </div>
        </aside>
      </div>

      <form class="account-card" id="password-form" style="max-width:560px;">
        <h3>Change Password</h3>
        <div class="account-form-grid">
          <label class="full">
            Current password
            <input name="current_password" type="password" autocomplete="current-password" required>
          </label>
          <label>
            New password
            <input name="new_password" type="password" autocomplete="new-password" minlength="8" required>
          </label>
          <label>
            Confirm new password
            <input name="confirm_password" type="password" autocomplete="new-password" minlength="8" required>
          </label>
        </div>
        <div class="account-form-actions" style="justify-content:flex-end;">
          <button class="btn btn-primary" type="submit">Update Password</button>
        </div>
      </form>
    </section>
  </main>
  <?php include 'includes/footer.php'; ?>
</div>
<script src="script.js"></script>
<script>
document.getElementById('account-form').addEventListener('submit', async (event) => {
  event.preventDefault();
  const payload = Object.fromEntries(new FormData(event.target).entries());
  const response = await fetch('api/user/update_profile.php', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken() }, body: JSON.stringify(payload) });
  const data = await response.json();
  showToast(data.message || (data.status === 'success' ? 'Profile saved.' : 'Could not save profile.'), data.status === 'success' ? 'success' : 'error');
});

document.getElementById('password-form').addEventListener('submit', async (event) => {
  event.preventDefault();
  const btn = event.target.querySelector('button[type="submit"]');
  const orig = btn.textContent;
  btn.disabled = true; btn.textContent = 'Saving…';
  const payload = Object.fromEntries(new FormData(event.target).entries());
  try {
    const res = await fetch('api/user/update_password.php', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken() }, body: JSON.stringify(payload) });
    const data = await res.json();
    showToast(data.message || (data.status === 'success' ? 'Password updated.' : 'Could not update password.'), data.status === 'success' ? 'success' : 'error');
    if (data.status === 'success') event.target.reset();
  } finally {
    btn.disabled = false; btn.textContent = orig;
  }
});
</script>
</body>
</html>
