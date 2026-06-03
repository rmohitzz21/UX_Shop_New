<?php
require_once 'includes/config.php';

$query    = trim((string) ($_GET['q'] ?? ''));
$typeFilter = trim((string) ($_GET['type'] ?? 'all')); // all | product | bundle
$sort     = trim((string) ($_GET['sort'] ?? 'relevance')); // relevance | price_asc | price_desc | newest

$items = [];

// ── Products ──────────────────────────────────────────────────────────────────
if ($typeFilter !== 'bundle') {
    if ($query !== '') {
        $like = '%' . $query . '%';
        $pStmt = $conn->prepare(
            'SELECT id, name, price, old_price, image, category, description, available_type, rating, created_at, "product" AS item_type
             FROM products
             WHERE (name LIKE ? OR category LIKE ? OR description LIKE ?) AND is_active = 1
             LIMIT 80'
        );
        $pStmt->bind_param('sss', $like, $like, $like);
    } else {
        $pStmt = $conn->prepare(
            'SELECT id, name, price, old_price, image, category, description, available_type, rating, created_at, "product" AS item_type
             FROM products WHERE is_active = 1 LIMIT 80'
        );
    }
    $pStmt->execute();
    $pRows = $pStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($pRows as $r) {
        $items[] = $r;
    }
}

// ── Bundles ───────────────────────────────────────────────────────────────────
if ($typeFilter !== 'product') {
    if ($query !== '') {
        $like = '%' . $query . '%';
        $bStmt = $conn->prepare(
            'SELECT id, name, price, old_price, image, category, description, "digital" AS available_type, rating, created_at, "bundle" AS item_type
             FROM bundles
             WHERE (name LIKE ? OR category LIKE ? OR description LIKE ?) AND is_active = 1
             LIMIT 80'
        );
        $bStmt->bind_param('sss', $like, $like, $like);
    } else {
        $bStmt = $conn->prepare(
            'SELECT id, name, price, old_price, image, category, description, "digital" AS available_type, rating, created_at, "bundle" AS item_type
             FROM bundles WHERE is_active = 1 LIMIT 80'
        );
    }
    $bStmt->execute();
    $bRows = $bStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($bRows as $r) {
        $items[] = $r;
    }
}

// ── Sort ──────────────────────────────────────────────────────────────────────
usort($items, function (array $a, array $b) use ($sort, $query): int {
    switch ($sort) {
        case 'price_asc':  return $a['price'] <=> $b['price'];
        case 'price_desc': return $b['price'] <=> $a['price'];
        case 'newest':     return strcmp((string) $b['created_at'], (string) $a['created_at']);
        default: // relevance: bundles last when no query, otherwise mixed
            if ($query === '') {
                return strcmp((string) $b['created_at'], (string) $a['created_at']);
            }
            $na = (int) str_contains(strtolower((string) $a['name']), strtolower($query));
            $nb = (int) str_contains(strtolower((string) $b['name']), strtolower($query));
            return $nb <=> $na;
    }
});

$items = array_slice($items, 0, 96);
$total = count($items);

$safeQuery = htmlspecialchars($query, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
    <title><?php echo $query !== '' ? 'Search: ' . $safeQuery . ' — UX Pacific Shop' : 'All Products — UX Pacific Shop'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;600&family=Gabarito:wght@400;500;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="style.css" />
</head>
<body class="bundles-page search-page">
    <?php include 'includes/header.php'; ?>

    <main class="search-results-page">

        <!-- Hero with inline search bar -->
        <section class="search-hero">
            <h1><?php echo $query !== '' ? 'Search results' : 'Browse everything'; ?></h1>
            <?php if ($query !== ''): ?>
                <p>Showing <?php echo $total; ?> result<?php echo $total !== 1 ? 's' : ''; ?> for &ldquo;<?php echo $safeQuery; ?>&rdquo;</p>
            <?php else: ?>
                <p>Every product and bundle currently in the shop.</p>
            <?php endif; ?>

            <form class="search-page-bar" action="search.php" method="get" role="search">
                <input type="hidden" name="type" value="<?php echo htmlspecialchars($typeFilter); ?>" />
                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>" />
                <div class="search-page-input-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input
                        type="search"
                        name="q"
                        value="<?php echo $safeQuery; ?>"
                        placeholder="Search products and bundles…"
                        autocomplete="off"
                        autofocus
                    />
                    <?php if ($query !== ''): ?>
                        <a href="search.php" class="search-page-clear" aria-label="Clear search">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </a>
                    <?php endif; ?>
                    <button type="submit" class="search-page-submit">Search</button>
                </div>
            </form>
        </section>

        <!-- Filters row -->
        <div class="search-controls">
            <div class="search-type-filters">
                <?php
                $types = ['all' => 'All', 'product' => 'Products', 'bundle' => 'Bundles'];
                foreach ($types as $val => $label):
                    $active = $typeFilter === $val ? ' active' : '';
                    $href = 'search.php?' . http_build_query(['q' => $query, 'type' => $val, 'sort' => $sort]);
                ?>
                <a href="<?php echo $href; ?>" class="search-type-chip<?php echo $active; ?>"><?php echo $label; ?></a>
                <?php endforeach; ?>
            </div>
            <form class="search-sort-form" action="search.php" method="get">
                <input type="hidden" name="q" value="<?php echo $safeQuery; ?>" />
                <input type="hidden" name="type" value="<?php echo htmlspecialchars($typeFilter); ?>" />
                <label for="search-sort-select" class="sr-only">Sort by</label>
                <select id="search-sort-select" name="sort" class="search-sort-select" onchange="this.form.submit()">
                    <option value="relevance" <?php echo $sort === 'relevance' ? 'selected' : ''; ?>>Relevance</option>
                    <option value="newest"    <?php echo $sort === 'newest'    ? 'selected' : ''; ?>>Newest</option>
                    <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="price_desc"<?php echo $sort === 'price_desc'? 'selected' : ''; ?>>Price: High to Low</option>
                </select>
            </form>
        </div>

        <!-- Results grid -->
        <section class="search-results-grid" id="search-results-grid">
            <?php if ($total === 0): ?>
                <div class="search-empty-state">
                    <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="1.5" style="width:64px;height:64px;margin:0 auto 16px;color:#5c5c7a;"><circle cx="28" cy="28" r="18"/><line x1="46" y1="46" x2="60" y2="60"/></svg>
                    <h2>No results found</h2>
                    <p>Try a different keyword, or browse everything.</p>
                    <a href="search.php">Browse all</a>
                </div>
            <?php endif; ?>

            <?php foreach ($items as $item):
                $id          = (int) $item['id'];
                $itemType    = (string) $item['item_type'];
                $name        = (string) $item['name'];
                $price       = (float)  $item['price'];
                $oldPrice    = $item['old_price'] !== null ? (float) $item['old_price'] : null;
                $image       = trim((string) ($item['image'] ?? '')) ?: 'img/sticker.webp';
                $category    = (string) ($item['category'] ?? 'Products');
                $description = (string) ($item['description'] ?? '');
                $availType   = (string) ($item['available_type'] ?? 'digital');
                $isBundle    = $itemType === 'bundle';
                $viewHref    = $isBundle ? 'bundles.php' : 'product.php?id=' . $id;
            ?>
            <article class="search-product-card">
                <a href="<?php echo $viewHref; ?>" class="search-card-img-link" tabindex="-1" aria-hidden="true">
                    <img
                        src="<?php echo htmlspecialchars($image); ?>"
                        alt="<?php echo htmlspecialchars($name); ?>"
                        loading="lazy"
                        onerror="this.src='img/sticker.webp'"
                    />
                    <?php if ($isBundle): ?>
                        <span class="search-card-badge">Bundle</span>
                    <?php endif; ?>
                </a>
                <div>
                    <span><?php echo htmlspecialchars($category); ?></span>
                    <h2><?php echo htmlspecialchars($name); ?></h2>
                    <p><?php echo htmlspecialchars(mb_substr($description, 0, 80)) . (mb_strlen($description) > 80 ? '…' : ''); ?></p>
                    <div class="search-card-price">
                        <strong>₹<?php echo number_format($price, 0); ?></strong>
                        <?php if ($oldPrice !== null && $oldPrice > $price): ?>
                            <del>₹<?php echo number_format($oldPrice, 0); ?></del>
                        <?php endif; ?>
                    </div>
                    <div class="search-product-actions">
                        <button
                            type="button"
                            data-add-to-cart="<?php echo $id; ?>"
                            data-item-type="<?php echo $itemType; ?>"
                            data-name="<?php echo htmlspecialchars($name); ?>"
                            data-price="<?php echo $price; ?>"
                            data-image="<?php echo htmlspecialchars($image); ?>"
                            data-category="<?php echo htmlspecialchars($category); ?>"
                            data-description="<?php echo htmlspecialchars($description); ?>"
                            data-type="<?php echo htmlspecialchars($availType); ?>"
                        >Add to Cart</button>
                        <a href="<?php echo $viewHref; ?>" class="search-view-btn">View</a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </section>

    </main>

    <script src="script.js"></script>
</body>
</html>
