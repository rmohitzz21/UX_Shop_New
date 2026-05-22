<?php
require_once 'includes/config.php';

$selected = trim((string) ($_GET['cat'] ?? ''));
$fallbackImages = ['img/poster.webp', 'img/poster1.webp', 'img/poster2.webp', 'img/poster3.webp', 'img/poster4.webp', 'img/shopnew.png'];
$categories = [];
$stmt = $conn->query("SELECT c.*, COUNT(p.id) AS product_count, MIN(p.image) AS product_image
    FROM categories c
    LEFT JOIN products p ON p.category = c.name AND p.is_active = 1
    WHERE c.is_active = 1
    GROUP BY c.id
    ORDER BY c.sort_order ASC, c.name ASC");
while ($stmt && $row = $stmt->fetch_assoc()) {
    $categories[] = $row;
}

$products = [];
if ($selected !== '') {
    $productStmt = $conn->prepare("SELECT p.* FROM products p
        INNER JOIN categories c ON c.name = p.category
        WHERE p.is_active = 1 AND (c.slug = ? OR c.name = ?)
        ORDER BY p.is_featured DESC, p.created_at DESC
        LIMIT 12");
    $productStmt->bind_param('ss', $selected, $selected);
    $productStmt->execute();
    $productResult = $productStmt->get_result();
    while ($row = $productResult->fetch_assoc()) $products[] = $row;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e($_SESSION['csrf_token'] ?? ''); ?>">
    <title>Categories - UX Pacific Shop</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,200;0,9..40,300;0,9..40,600;1,9..40,200&family=Gabarito:wght@400;500;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="category-page">
<div class="page">
    <?php include 'includes/header.php'; ?>
    <main class="category-main">
        <section class="category-hero">
            <p class="category-kicker">Explore by category</p>
            <h1>Browse by Category</h1>
            <p>Explore our carefully curated categories to find exactly what you need for your creative projects.</p>
        </section>

        <div class="uxp-container">
    <div class="uxp-category-grid">

        <?php
        if (!isset($conn)) {
            @require_once 'includes/config.php';
        }

        $fallbackImages = [
            'img/poster.webp',
            'img/poster1.webp',
            'img/poster2.webp',
            'img/poster3.webp'
        ];

        $colorClasses = [
            'uxp-category-teal',
            'uxp-category-pink',
            'uxp-category-purple',
            'uxp-category-orange'
        ];

        if (isset($conn)) {

            $sql = "
                SELECT 
                    c.*,
                    COUNT(p.id) as product_count,
                    MIN(p.image) as product_image
                FROM categories c
                LEFT JOIN products p 
                    ON p.category = c.name
                GROUP BY c.id
                ORDER BY c.id ASC
                LIMIT 4
            ";

            $result = $conn->query($sql);

            if ($result && $result->num_rows > 0) {

                $index = 0;

                while($cat = $result->fetch_assoc()) {

                    $name = htmlspecialchars($cat['name']);
                    $slug = htmlspecialchars($cat['slug']);
                    $description = htmlspecialchars(
                        $cat['description'] 
                        ?: 'Explore premium curated design resources.'
                    );

                    $productCount = (int)$cat['product_count'];

                    $image = !empty($cat['product_image'])
                        ? htmlspecialchars($cat['product_image'])
                        : $fallbackImages[$index % count($fallbackImages)];

                    $colorClass = $colorClasses[$index % count($colorClasses)];

                    echo "

                    <a href='shopAll.php?category=$slug'
                       class='uxp-category-card $colorClass'>

                        <div class='uxp-category-art' aria-hidden='true'>

                            <span class='uxp-category-bubble'></span>

                            <span class='uxp-category-icon'>

                                <img 
                                    src='$image'
                                    alt='$name'
                                    loading='lazy'
                                    onerror=\"this.src='img/poster.webp'\"
                                />

                            </span>

                        </div>

                        <div class='uxp-category-body'>

                            <h3>$name</h3>

                            <span>$productCount+ items</span>

                            <p>$description</p>

                            <strong>

                                Explore

                                <svg viewBox='0 0 24 24'
                                     fill='none'
                                     stroke='currentColor'
                                     stroke-width='2'>

                                    <path d='M5 12h14'></path>
                                    <path d='m13 6 6 6-6 6'></path>

                                </svg>

                            </strong>

                        </div>

                    </a>
                    ";

                    $index++;
                }

            } else {

                echo "<p>No categories found.</p>";

            }
        }
        ?>

    </div>

</div>
        <?php if ($selected !== ''): ?>
        <section class="category-products">
            <div class="category-products-heading">
                <h2><?php echo e(ucwords(str_replace('-', ' ', $selected))); ?></h2>
                <a href="shopAll.php?cat=<?php echo e($selected); ?>">View all</a>
            </div>
            <div class="products-grid">
                <?php if ($products): ?>
                    <?php foreach ($products as $product) echo marketplaceProductCard($product, 'product'); ?>
                <?php else: ?>
                    <div class="marketplace-empty"><h2>No products yet</h2><p>Add products for this category from the admin panel.</p></div>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>
    </main>
    <?php include 'includes/footer.php'; ?>
</div>
<script src="script.js"></script>
</body>
</html>
