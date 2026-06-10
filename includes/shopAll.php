<?php require_once 'includes/config.php'; ?>
<?php require_once __DIR__ . '/marketplace.php'; ?>
<?php
// ── Category filter resolution ──────────────────────────────────────────────
$rawFilter    = isset($_GET['category']) ? trim($_GET['category']) : '';
$categoryName = ''; // resolved product-table category name

if ($rawFilter !== '') {
    // 1. Try categories.slug → category name
    $cStmt = $conn->prepare(
        "SELECT name FROM categories WHERE slug = ? AND is_active = 1 LIMIT 1"
    );
    $cStmt->bind_param("s", $rawFilter);
    $cStmt->execute();
    $cRow = $cStmt->get_result()->fetch_assoc();
    $cStmt->close();

    if ($cRow) {
        $categoryName = $cRow['name'];
    } else {
        // 2. Fallback: match product category name directly (case-insensitive)
        $cStmt2 = $conn->prepare(
            "SELECT DISTINCT category FROM products WHERE is_active = 1 AND LOWER(category) = LOWER(?) LIMIT 1"
        );
        $cStmt2->bind_param("s", $rawFilter);
        $cStmt2->execute();
        $cRow2 = $cStmt2->get_result()->fetch_assoc();
        $cStmt2->close();
        if ($cRow2) {
            $categoryName = $cRow2['category'];
        }
    }
}

// ── Category list for tabs ───────────────────────────────────────────────────
$catResult  = $conn->query("SELECT DISTINCT category FROM products WHERE is_active = 1 AND available_type != 'physical' ORDER BY category");
$categories = [];
while ($cat = $catResult->fetch_assoc()) {
    $categories[] = $cat['category'];
}

// ── Price range for sidebar slider ──────────────────────────────────────────
$priceRange = $conn->query("SELECT MIN(price) as min_price, MAX(price) as max_price FROM products WHERE is_active = 1 AND available_type != 'physical'")->fetch_assoc();
$minPrice   = floor($priceRange['min_price'] ?? 0);
$maxPrice   = ceil($priceRange['max_price'] ?? 5000);

// ── Pagination ───────────────────────────────────────────────────────────────
$limit  = 9;
$page   = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Build base for pagination links (preserves category param)
$pageBase = $rawFilter !== '' ? '?category=' . urlencode($rawFilter) . '&page=' : '?page=';

// Digital-only catalog (no physical products)
$digitalWhere = "is_active = 1 AND available_type != 'physical'";

// ── Count (category-aware) ───────────────────────────────────────────────────
if ($categoryName !== '') {
    $cntStmt = $conn->prepare(
        "SELECT COUNT(*) AS total FROM products
         WHERE {$digitalWhere} AND category = ?"
    );
    $cntStmt->bind_param("s", $categoryName);
    $cntStmt->execute();
    $totalProducts = $cntStmt->get_result()->fetch_assoc()['total'];
    $cntStmt->close();
} else {
    $totalProducts = $conn->query(
        "SELECT COUNT(*) AS total FROM products WHERE {$digitalWhere}"
    )->fetch_assoc()['total'];
}
$totalPages = max(1, (int) ceil($totalProducts / $limit));
if ($page > $totalPages) $page = $totalPages;

// ── Products query (category-aware, digital only) ─────────────────────────
if ($categoryName !== '') {
    $stmt = $conn->prepare(
        "SELECT * FROM products
         WHERE {$digitalWhere} AND category = ?
         ORDER BY is_featured DESC, sales_count DESC, rating DESC, created_at DESC
         LIMIT ? OFFSET ?"
    );
    $stmt->bind_param("sii", $categoryName, $limit, $offset);
} else {
    $stmt = $conn->prepare(
        "SELECT * FROM products
         WHERE {$digitalWhere}
         ORDER BY is_featured DESC, sales_count DESC, rating DESC, created_at DESC
         LIMIT ? OFFSET ?"
    );
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$result = $stmt->get_result();

// ── Helper: slugify category name for URL ───────────────────────────────────
function catSlug(string $name): string {
    return strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($name)));
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title><?= $categoryName ? htmlspecialchars($categoryName) . ' Products – UX Pacific' : 'Shop All Products – UX Pacific' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('style.css')); ?>" />
    <link rel="stylesheet" href="assets/css/shop-all.css" />
    <link rel="icon" type="image/png" href="img/fav.png" />
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
  </head>

  <body class="shopAll">
    <div class="page">
      <!-- NAVBAR -->
      <?php include 'includes/header.php'; ?>

      <!-- MAIN CONTENT -->
      <main class="main shop-all-main">

        <!-- Page Header -->
        <section class="shop-all-header">
          <?php if ($categoryName): ?>
            <div class="shop-all-breadcrumb">
              <a href="shopAll.php">All Products</a>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M9 18l6-6-6-6"/></svg>
              <span><?= htmlspecialchars($categoryName) ?></span>
            </div>
            <h1 class="shop-all-title"><?= htmlspecialchars($categoryName) ?> <span>Products</span></h1>
            <p class="shop-all-subtitle">
              Showing <?= $totalProducts ?> product<?= $totalProducts !== 1 ? 's' : '' ?> in <strong><?= htmlspecialchars($categoryName) ?></strong>
            </p>
          <?php else: ?>
            <h1 class="shop-all-title">Design <span>Resources &amp;<br class="mobile-title-break"> Products</span></h1>
            <p class="shop-all-subtitle">
              Explore premium UX/UI design resources — Figma kits, templates, mockups, and digital assets ready to download.
            </p>
          <?php endif; ?>
        </section>

        <!-- Category Tabs + Sort -->
        <div class="shop-controls">
          <div class="category-tabs">
            <a class="category-tab <?= $categoryName === '' ? 'active' : '' ?>"
               href="shopAll.php">All</a>
            <?php foreach ($categories as $cat):
              $slug = catSlug($cat);
              $isActive = $categoryName !== '' && strtolower($cat) === strtolower($categoryName);
            ?>
              <a class="category-tab <?= $isActive ? 'active' : '' ?>"
                 href="shopAll.php?category=<?= urlencode($slug) ?>">
                <?= htmlspecialchars($cat) ?>
              </a>
            <?php endforeach; ?>
          </div>
          <div class="sort-control">
            <label for="sort-select">Sort By:</label>
            <select id="sort-select">
              <option value="default">Default</option>
              <option value="price-low">Price: Low to High</option>
              <option value="price-high">Price: High to Low</option>
              <option value="rating">Top Rated</option>
            </select>
          </div>
        </div>

        <!-- Main Layout: Sidebar + Grid -->
        <section class="shop-layout">

          <!-- Left Sidebar -->
          <aside class="shop-sidebar">

            <!-- Quick filters -->
            <div class="filter-section">
              <h3 class="filter-title">Quick Filters</h3>
              <p class="filter-hint">All items are digital downloads — instant access after checkout.</p>
              <div class="filter-options">
                <label class="filter-checkbox">
                  <input type="checkbox" id="filter-free" name="free" value="1" />
                  <span class="checkmark"></span>
                  Free resources only
                </label>
                <label class="filter-checkbox">
                  <input type="checkbox" id="filter-featured" name="featured" value="1" />
                  <span class="checkmark"></span>
                  Featured only
                </label>
              </div>
            </div>

            <!-- Price Range Filter -->
            <div class="filter-section">
              <h3 class="filter-title">Price Range</h3>
              <div class="price-range-wrapper">
                <div class="price-inputs">
                  <span class="price-label">₹<span id="price-min-val"><?= $minPrice ?></span></span>
                  <span class="price-separator">–</span>
                  <span class="price-label">₹<span id="price-max-val"><?= $maxPrice ?></span>+</span>
                </div>
                <div class="range-slider">
                  <input type="range" id="price-range" min="<?= $minPrice ?>" max="<?= $maxPrice ?>" value="<?= $maxPrice ?>" />
                </div>
              </div>
            </div>

          </aside>

          <!-- Product Grid -->
          <div class="shop-products">
            <div class="shop-results-meta" id="shop-results-meta">
              <span>Showing <strong id="shop-visible-count"><?= (int) $totalProducts ?></strong> digital resources</span>
            </div>
            <div class="product-grid shop-grid" id="product-grid">
              <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                <?= uxpShopAllProductCard($row) ?>
                <?php endwhile; ?>
              <?php else: ?>
                <div class="shop-empty-state">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="48" height="48">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                  </svg>
                  <p>No products found<?= $categoryName ? ' in <strong>' . htmlspecialchars($categoryName) . '</strong>' : '' ?>.</p>
                  <?php if ($categoryName): ?>
                    <a href="shopAll.php" class="btn btn-primary" style="margin-top:12px;">Browse All Products</a>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav class="pagination">
              <a href="<?= $pageBase ?><?= max(1, $page - 1) ?>"
                 class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>" aria-label="Previous page">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M15 18l-6-6 6-6"/>
                </svg>
              </a>
              <?php
              $startPage = max(1, $page - 2);
              $endPage   = min($totalPages, $page + 2);
              if ($startPage > 1) {
                  echo '<a href="' . $pageBase . '1" class="page-num">1</a>';
                  if ($startPage > 2) echo '<span class="page-ellipsis">…</span>';
              }
              for ($i = $startPage; $i <= $endPage; $i++) {
                  $cls = $i === $page ? 'active' : '';
                  echo "<a href='{$pageBase}{$i}' class='page-num {$cls}'>{$i}</a>";
              }
              if ($endPage < $totalPages) {
                  if ($endPage < $totalPages - 1) echo '<span class="page-ellipsis">…</span>';
                  echo "<a href='{$pageBase}{$totalPages}' class='page-num'>{$totalPages}</a>";
              }
              ?>
              <a href="<?= $pageBase ?><?= min($totalPages, $page + 1) ?>"
                 class="page-btn <?= $page >= $totalPages ? 'disabled' : '' ?>" aria-label="Next page">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M9 18l6-6-6-6"/>
                </svg>
              </a>
            </nav>
            <?php endif; ?>

          </div>
        </section>
      </main>

      <!-- FOOTER -->
      <?php include 'includes/footer.php'; ?>
    </div>

    <script src="<?php echo htmlspecialchars(asset_url('script.js')); ?>"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
      const productCards   = document.querySelectorAll('.shop-product-card');
      const filterFree     = document.getElementById('filter-free');
      const filterFeatured = document.getElementById('filter-featured');
      const priceRangeEl   = document.getElementById('price-range');
      const sortSelect     = document.getElementById('sort-select');
      const priceMaxVal    = document.getElementById('price-max-val');
      const visibleCountEl = document.getElementById('shop-visible-count');

      // ── Client-side filter (free / featured / price) ──────────────────────
      function filterProducts() {
        const freeOnly = filterFree?.checked === true;
        const featuredOnly = filterFeatured?.checked === true;
        const maxPrice = parseFloat(priceRangeEl?.value || 999999);
        let visible = 0;

        productCards.forEach(card => {
          const cardPrice = parseFloat(card.dataset.price || 0);
          const isFree = card.dataset.isFree === '1';
          const isFeatured = card.dataset.featured === '1';
          const freeMatch = !freeOnly || isFree;
          const featuredMatch = !featuredOnly || isFeatured;
          const priceMatch = cardPrice <= maxPrice;
          const show = freeMatch && featuredMatch && priceMatch;
          card.style.display = show ? '' : 'none';
          if (show) visible++;
        });

        if (visibleCountEl) visibleCountEl.textContent = String(visible);
      }

      // ── Client-side sort ──────────────────────────────────────────────────
      function sortProducts() {
        const sortBy = sortSelect?.value || 'default';
        const grid   = document.getElementById('product-grid');
        if (!grid) return;
        const cards  = Array.from(productCards);

        cards.sort((a, b) => {
          switch (sortBy) {
            case 'price-low':  return parseFloat(a.dataset.price)  - parseFloat(b.dataset.price);
            case 'price-high': return parseFloat(b.dataset.price)  - parseFloat(a.dataset.price);
            case 'rating':     return parseFloat(b.dataset.rating) - parseFloat(a.dataset.rating);
            default:           return 0;
          }
        });

        cards.forEach(card => grid.appendChild(card));
      }

      // ── Event listeners ───────────────────────────────────────────────────
      filterFree?.addEventListener('change', filterProducts);
      filterFeatured?.addEventListener('change', filterProducts);

      if (priceRangeEl) {
        priceRangeEl.addEventListener('input', function () {
          if (priceMaxVal) priceMaxVal.textContent = this.value;
          filterProducts();
        });
      }

      if (sortSelect) {
        sortSelect.addEventListener('change', sortProducts);
      }

      // Initial run (applies type filter defaults)
      filterProducts();
    });
    </script>
  </body>
</html>
