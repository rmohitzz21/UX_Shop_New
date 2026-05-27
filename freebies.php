<?php
require_once 'includes/config.php';

$freebiesResult = $conn->query(
    'SELECT * FROM products WHERE is_active = 1 AND price <= 0 ORDER BY is_featured DESC, id DESC'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
    <title>Freebies – UX Pacific Shop</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="icon" type="image/x-icon" href="img/faviconUXP444@4x-789.png" />
    <link rel="stylesheet" href="style.css" />
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>
<body class="freebies-page">
<div class="page">
    <?php include 'includes/header.php'; ?>
    <main class="main shop-all-main">
        <section class="shop-all-header">
            <h1 class="shop-all-title">Free <span>Design Resources</span></h1>
            <p class="shop-all-subtitle">
                Download curated UX/UI freebies from UX Pacific — templates, assets, and tools at no cost.
            </p>
        </section>
        <section class="top-products shop-listing">
            <div class="products-grid">
                <?php if ($freebiesResult && $freebiesResult->num_rows > 0): ?>
                    <?php while ($item = $freebiesResult->fetch_assoc()): ?>
                        <?php echo marketplaceProductCard($item, 'product'); ?>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state marketplace-empty">
                        <h2>No freebies yet</h2>
                        <p>Check back soon for free templates, UI kits, and design assets.</p>
                        <a class="btn btn-primary" href="shopAll.php">Browse Products</a>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
    <?php include 'includes/footer.php'; ?>
</div>
<script src="script.js"></script>
</body>
</html>
