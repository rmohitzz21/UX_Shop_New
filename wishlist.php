<?php
require_once 'includes/config.php';
if (empty($_SESSION['user_id'])) {
    header('Location: signin.php?redirect=wishlist.php');
    exit;
}
$uid = (int) $_SESSION['user_id'];
$stmt = $conn->prepare('
    SELECT w.item_type, COALESCE(p.id, b.id) id, COALESCE(p.name, b.name) name,
           COALESCE(p.description, b.description) description, COALESCE(p.category, b.category) category,
           COALESCE(p.price, b.price) price, COALESCE(p.old_price, b.old_price) old_price,
           COALESCE(p.image, b.image) image, COALESCE(p.stock, b.stock) stock, COALESCE(p.rating, b.rating) rating
    FROM wishlist w
    LEFT JOIN products p ON p.id = w.product_id AND w.item_type = "product"
    LEFT JOIN bundles b ON b.id = w.bundle_id AND w.item_type = "bundle"
    WHERE w.user_id = ?
    ORDER BY w.created_at DESC
');
$stmt->bind_param('i', $uid);
$stmt->execute();
$items = $stmt->get_result();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?php echo e($_SESSION['csrf_token'] ?? ''); ?>">
  <title>Wishlist - UX Pacific Shop</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="img/fav.png">
  <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('style.css')); ?>">
</head>
<body>
<div class="page">
  <?php include 'includes/header.php'; ?>
  <main>
    <section class="shop-hero compact">
      <div>
        <span class="marketplace-kicker">Saved for later</span>
        <h1>Your Wishlist</h1>
        <p>Keep track of products and bundles before you add them to cart.</p>
      </div>
    </section>
    <section class="top-products shop-listing">
      <div class="products-grid">
        <?php if ($items->num_rows): ?>
          <?php while ($item = $items->fetch_assoc()) echo marketplaceProductCard($item, $item['item_type']); ?>
        <?php else: ?>
          <div class="empty-state marketplace-empty"><h2>Your wishlist is empty</h2><p>Use the heart button on products and bundles to save items here.</p><a class="btn btn-primary" href="shopAll.php">Browse Products</a></div>
        <?php endif; ?>
      </div>
    </section>
  </main>
  <?php include 'includes/footer.php'; ?>
</div>
<script src="<?php echo htmlspecialchars(asset_url('script.js')); ?>"></script>
</body>
</html>
