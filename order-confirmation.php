<?php require_once 'includes/config.php'; ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?php echo e($_SESSION['csrf_token'] ?? ''); ?>">
  <title>Order Confirmation - UX Pacific Shop</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="page">
  <?php include 'includes/header.php'; ?>
  <main>
    <section class="confirmation-panel">
      <span class="marketplace-kicker">Order received</span>
      <h1>Thank you for your order</h1>
      <p id="confirmation-copy">Your order has been created and is now pending confirmation.</p>
      <div class="delivery-timeline" aria-label="Delivery status">
        <span class="active">Pending</span><span>Processing</span><span>Shipped</span><span>Delivered</span>
      </div>
      <div class="confirmation-actions">
        <a class="btn btn-primary" href="orders.php">Track Orders</a>
        <a class="btn btn-outline" href="shopAll.php">Continue Shopping</a>
      </div>
    </section>
  </main>
  <?php include 'includes/footer.php'; ?>
</div>
<script src="script.js"></script>
<script>
const order = JSON.parse(localStorage.getItem('lastOrder') || '{}');
if (order.orderNumber) {
  document.getElementById('confirmation-copy').textContent = `Order ${order.orderNumber} has been placed. Current status: ${order.status || 'Pending'}.`;
}
</script>
</body>
</html>
