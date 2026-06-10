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
            'SELECT *, "product" AS item_type
             FROM products
             WHERE (name LIKE ? OR category LIKE ? OR description LIKE ?) AND is_active = 1
             LIMIT 80'
        );
        $pStmt->bind_param('sss', $like, $like, $like);
    } else {
        $pStmt = $conn->prepare(
            'SELECT *, "product" AS item_type FROM products WHERE is_active = 1 LIMIT 80'
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
            'SELECT *, "bundle" AS item_type, "digital" AS available_type
             FROM bundles
             WHERE (name LIKE ? OR category LIKE ? OR description LIKE ?) AND is_active = 1
             LIMIT 80'
        );
        $bStmt->bind_param('sss', $like, $like, $like);
    } else {
        $bStmt = $conn->prepare(
            'SELECT *, "bundle" AS item_type, "digital" AS available_type FROM bundles WHERE is_active = 1 LIMIT 80'
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
    <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('style.css')); ?>" />
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
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
                        type="text"
                        name="q"
                        inputmode="search"
                        enterkeyhint="search"
                        role="searchbox"
                        value="<?php echo $safeQuery; ?>"
                        placeholder="Search products and bundles…"
                        autocomplete="off"
                        spellcheck="false"
                        autofocus
                    />
                    <button type="submit" class="search-page-submit">
                        <span class="search-page-submit-label">Search</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </button>
                </div>
            </form>
        </section>

        <!-- Filters row -->
        <div class="search-controls">
            <p class="search-results-meta"><?php echo $total; ?> result<?php echo $total !== 1 ? 's' : ''; ?></p>
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

        <!-- Results grid (same cards as homepage) -->
        <section class="uxp-product-grid search-results-grid" id="search-results-grid">
            <?php if ($total === 0): ?>
                <div class="search-empty-state">
                    <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="1.5" style="width:64px;height:64px;margin:0 auto 16px;color:#5c5c7a;"><circle cx="28" cy="28" r="18"/><line x1="46" y1="46" x2="60" y2="60"/></svg>
                    <h2>No results found</h2>
                    <p>Try a different keyword, or browse everything.</p>
                    <a href="search.php">Browse all</a>
                </div>
            <?php endif; ?>

            <?php foreach ($items as $item):
                echo ($item['item_type'] ?? '') === 'bundle'
                    ? uxpIndexBundleCard($item)
                    : uxpIndexProductCard($item);
            endforeach; ?>
        </section>

    </main>

    <script src="<?php echo htmlspecialchars(asset_url('script.js')); ?>"></script>
</body>
</html>
