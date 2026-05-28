<?php require_once 'includes/config.php'; ?>
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
$catResult  = $conn->query("SELECT DISTINCT category FROM products WHERE is_active = 1 ORDER BY category");
$categories = [];
while ($cat = $catResult->fetch_assoc()) {
    $categories[] = $cat['category'];
}

// ── Price range for sidebar slider ──────────────────────────────────────────
$priceRange = $conn->query("SELECT MIN(price) as min_price, MAX(price) as max_price FROM products WHERE is_active = 1")->fetch_assoc();
$minPrice   = floor($priceRange['min_price'] ?? 0);
$maxPrice   = ceil($priceRange['max_price'] ?? 5000);

// ── Pagination ───────────────────────────────────────────────────────────────
$limit  = 9;
$page   = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Build base for pagination links (preserves category param)
$pageBase = $rawFilter !== '' ? '?category=' . urlencode($rawFilter) . '&page=' : '?page=';

// ── Count (category-aware) ───────────────────────────────────────────────────
if ($categoryName !== '') {
    $cntStmt = $conn->prepare(
        "SELECT COUNT(*) AS total FROM products
         WHERE is_active = 1 AND (stock > 0 OR available_type != 'physical') AND category = ?"
    );
    $cntStmt->bind_param("s", $categoryName);
    $cntStmt->execute();
    $totalProducts = $cntStmt->get_result()->fetch_assoc()['total'];
    $cntStmt->close();
} else {
    $totalProducts = $conn->query(
        "SELECT COUNT(*) AS total FROM products WHERE is_active = 1 AND (stock > 0 OR available_type != 'physical')"
    )->fetch_assoc()['total'];
}
$totalPages = max(1, (int) ceil($totalProducts / $limit));
if ($page > $totalPages) $page = $totalPages;

// ── Products query (category-aware) ─────────────────────────────────────────
if ($categoryName !== '') {
    $stmt = $conn->prepare(
        "SELECT * FROM products
         WHERE is_active = 1 AND (stock > 0 OR available_type != 'physical') AND category = ?
         ORDER BY is_featured DESC, rating DESC, created_at DESC
         LIMIT ? OFFSET ?"
    );
    $stmt->bind_param("sii", $categoryName, $limit, $offset);
} else {
    $stmt = $conn->prepare(
        "SELECT * FROM products
         WHERE is_active = 1 AND (stock > 0 OR available_type != 'physical')
         ORDER BY is_featured DESC, created_at DESC
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
    <link rel="stylesheet" href="style.css" />
    <link rel="icon" type="image/x-icon" href="img/faviconUXP444@4x-789.png" />
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
              Explore premium UX/UI design resources including digital assets and physical products.
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

            <!-- Product Type Filter -->
            <div class="filter-section">
              <h3 class="filter-title">Product Type</h3>
              <div class="filter-options">
                <label class="filter-checkbox">
                  <input type="checkbox" name="type" value="physical" checked />
                  <span class="checkmark"></span>
                  Physical
                </label>
                <label class="filter-checkbox">
                  <input type="checkbox" name="type" value="digital" checked />
                  <span class="checkmark"></span>
                  Digital
                </label>
                <label class="filter-checkbox">
                  <input type="checkbox" name="type" value="both" checked />
                  <span class="checkmark"></span>
                  Both
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

            <!-- Promo Card -->
            <div class="sidebar-promo">
              <div class="promo-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                  <path d="M2 17l10 5 10-5"></path>
                  <path d="M2 12l10 5 10-5"></path>
                </svg>
              </div>
              <h4>Premium Resources</h4>
              <p>Get 20% off on all digital products this month!</p>
            </div>

          </aside>

          <!-- Product Grid -->
          <div class="shop-products">
            <div class="product-grid shop-grid" id="product-grid">
              <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()):
                  $id            = $row['id'];
                  $name          = htmlspecialchars($row['name']);
                  $jsName        = htmlspecialchars(addslashes($row['name']), ENT_QUOTES, 'UTF-8');
                  $jsImage       = htmlspecialchars(addslashes($row['image'] ?? ''), ENT_QUOTES, 'UTF-8');
                  $jsCategory    = htmlspecialchars(addslashes($row['category']), ENT_QUOTES, 'UTF-8');
                  $jsAvailType   = htmlspecialchars(addslashes($row['available_type'] ?? 'physical'), ENT_QUOTES, 'UTF-8');
                  $price         = number_format($row['price'], 2);
                  $oldPrice      = !empty($row['old_price']) ? number_format($row['old_price'], 2) : '';
                  $imgSrc        = !empty($row['image']) ? htmlspecialchars($row['image']) : 'img/sticker.webp';
                  $category      = htmlspecialchars($row['category']);
                  $rating        = number_format($row['rating'] ?: 4.5, 1);
                  $availableType = $row['available_type'] ?? 'physical';
                  $description   = htmlspecialchars(mb_strimwidth($row['description'] ?? '', 0, 100, '…'));
                  $outOfStock    = $row['stock'] <= 0 && $availableType === 'physical';
                ?>
                <article class="uxp-product-card shop-product-card"
                  data-product-id="<?= $id ?>"
                  data-name="<?= $name ?>"
                  data-image="<?= $imgSrc ?>"
                  data-category="<?= $category ?>"
                  data-type="<?= htmlspecialchars($availableType) ?>"
                  data-price="<?= $row['price'] ?>"
                  data-old-price="<?= $row['old_price'] ?>"
                  data-rating="<?= $rating ?>">

                  <a href="product.php?id=<?= $id ?>"
                     class="uxp-product-media js-product-popup"
                     aria-label="View <?= $name ?>"
                     data-product-id="<?= $id ?>">

                    <img src="<?= $imgSrc ?>" alt="<?= $name ?>"
                         loading="lazy" width="480" height="360"
                         onerror="this.src='img/sticker.webp'" />

                    <span class="uxp-product-badge-icon" aria-hidden="true">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M12 3v18M3 12h18"></path>
                        <circle cx="12" cy="12" r="8"></circle>
                      </svg>
                    </span>
                  </a>

                  <div class="uxp-product-body">
                    <div class="uxp-product-title-row">
                      <h3><?= $name ?></h3>
                      <div class="uxp-rating" aria-label="Rating <?= $rating ?> out of 5">
                        <span aria-hidden="true">&#9733;</span>
                        <b><?= $rating ?></b>
                      </div>
                    </div>

                    <p><?= $description ?></p>

                    <p class="uxp-product-spec">Category: <?= $category ?></p>

                    <div class="uxp-product-meta">
                      <div class="uxp-product-price">
                        ₹<?= $price ?>
                        <?= $oldPrice ? "<span class='uxp-old-price'>₹{$oldPrice}</span>" : '' ?>
                      </div>
                    </div>

                    <div class="uxp-product-actions">
                      <a href="product.php?id=<?= $id ?>"
                         class="uxp-card-btn uxp-card-btn-primary js-product-popup"
                         data-product-id="<?= $id ?>">View Details</a>

                      <button
                        onclick="addToCart('<?= $id ?>',null,1,{name:'<?= $jsName ?>',price:<?= $row['price'] ?>,image:'<?= $jsImage ?>',category:'<?= $jsCategory ?>'},'<?= $jsAvailType ?>')"
                        class="uxp-card-btn uxp-card-btn-secondary"
                        type="button"
                        aria-label="Add to cart"
                        <?= $outOfStock ? 'disabled' : '' ?>>
                        Add to Cart
                      </button>
                    </div>
                  </div>
                </article>
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

    <script src="script.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
      const productCards  = document.querySelectorAll('.shop-product-card');
      const typeCheckboxes = document.querySelectorAll('input[name="type"]');
      const priceRangeEl  = document.getElementById('price-range');
      const sortSelect    = document.getElementById('sort-select');
      const priceMaxVal   = document.getElementById('price-max-val');

      // ── Client-side filter (type + price) ─────────────────────────────────
      function filterProducts() {
        const checkedTypes = Array.from(typeCheckboxes)
          .filter(cb => cb.checked).map(cb => cb.value);
        const maxPrice = parseFloat(priceRangeEl?.value || 999999);

        productCards.forEach(card => {
          const cardType  = card.dataset.type || 'physical';
          const cardPrice = parseFloat(card.dataset.price || 0);
          const typeMatch  = checkedTypes.includes(cardType);
          const priceMatch = cardPrice <= maxPrice;
          card.style.display = (typeMatch && priceMatch) ? '' : 'none';
        });
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
      typeCheckboxes.forEach(cb => cb.addEventListener('change', filterProducts));

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
