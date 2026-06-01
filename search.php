<?php
require_once 'includes/config.php';

$query = trim((string) ($_GET['q'] ?? ''));
$products = [];

if ($query !== '') {
    $like = '%' . $query . '%';
    $stmt = $conn->prepare('SELECT * FROM products WHERE (name LIKE ? OR category LIKE ? OR description LIKE ?) AND is_active = 1 ORDER BY name ASC LIMIT 48');
    $stmt->bind_param('sss', $like, $like, $like);
} else {
    $stmt = $conn->prepare('SELECT * FROM products WHERE is_active = 1 ORDER BY created_at DESC, id DESC LIMIT 48');
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

function searchAsset($value) {
    $value = trim((string) $value);
    return $value !== '' ? $value : 'img/sticker.webp';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
    <title>Search - UX Pacific Shop</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;600&family=Gabarito:wght@400;500;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="style.css" />
</head>
<body class="bundles-page search-page">
    <?php include 'includes/header.php'; ?>

    <main class="search-results-page">
        <section class="search-hero">
            <h1><?php echo $query !== '' ? 'Search results' : 'All products'; ?></h1>
            <p><?php echo $query !== '' ? 'Showing matches for "' . htmlspecialchars($query) . '"' : 'Browse every product currently available in the shop.'; ?></p>
        </section>

        <section class="search-results-grid">
            <?php if (!$products): ?>
                <div class="search-empty-state">
                    <h2>No products found</h2>
                    <p>Try another keyword or browse the bundles page.</p>
                    <a href="bundles.php">Explore Bundles</a>
                </div>
            <?php endif; ?>

            <?php foreach ($products as $product): ?>
                <?php
                    $id = (int) $product['id'];
                    $name = (string) $product['name'];
                    $price = (float) $product['price'];
                    $image = searchAsset($product['image'] ?? '');
                    $category = (string) ($product['category'] ?? 'Products');
                    $description = (string) ($product['description'] ?? '');
                    $type = (string) ($product['available_type'] ?? 'physical');
                    $jsName = htmlspecialchars(json_encode($name), ENT_QUOTES, 'UTF-8');
                    $jsImage = htmlspecialchars(json_encode($image), ENT_QUOTES, 'UTF-8');
                    $jsCategory = htmlspecialchars(json_encode($category), ENT_QUOTES, 'UTF-8');
                    $jsDescription = htmlspecialchars(json_encode($description), ENT_QUOTES, 'UTF-8');
                ?>
                <article class="search-product-card">
                    <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($name); ?>" onerror="this.src='img/sticker.webp'" />
                    <div>
                        <span><?php echo htmlspecialchars($category); ?></span>
                        <h2><?php echo htmlspecialchars($name); ?></h2>
                        <p><?php echo htmlspecialchars($description); ?></p>
                        <strong>₹<?php echo number_format($price, 0); ?></strong>
                        <div class="search-product-actions">
                            <button type="button" data-add-to-cart="<?php echo $id; ?>" data-name="<?php echo htmlspecialchars($name); ?>" data-price="<?php echo htmlspecialchars((string) $price); ?>" data-image="<?php echo htmlspecialchars($image); ?>" data-category="<?php echo htmlspecialchars($category); ?>" data-description="<?php echo htmlspecialchars($description); ?>" data-type="<?php echo htmlspecialchars($type); ?>">Add to Cart</button>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    </main>

    <script src="script.js"></script>
</body>
</html>
