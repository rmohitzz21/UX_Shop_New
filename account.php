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
</head>
<body>
<div class="page">
  <?php include 'includes/header.php'; ?>
  <main>
    <section class="shop-hero compact"><div><span class="marketplace-kicker">Profile</span><h1>Account Details</h1><p>Manage your customer information and order history.</p></div></section>
    <section class="account-grid">
      <form class="profile-card" id="account-form">
        <label>First name<input name="first_name" value="<?php echo e($user['first_name'] ?? ''); ?>" required></label>
        <label>Last name<input name="last_name" value="<?php echo e($user['last_name'] ?? ''); ?>" required></label>
        <label>Email<input name="email" type="email" value="<?php echo e($user['email'] ?? ''); ?>" readonly></label>
        <label>Phone<input name="phone" value="<?php echo e($user['phone'] ?? ''); ?>"></label>
        <button class="btn btn-primary" type="submit">Save Profile</button>
      </form>
      <aside class="profile-card">
        <h2>Quick links</h2>
        <a href="orders.php">Order history</a>
        <a href="wishlist.php">Wishlist</a>
        <a href="cart.php">Cart</a>
      </aside>
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
</script>
</body>
</html>
