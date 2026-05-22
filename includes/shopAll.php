<?php require_once 'includes/config.php'; ?>
<?php
// Fetch distinct categories for filter tabs
$catResult = $conn->query("SELECT DISTINCT category FROM products WHERE is_active = 1 ORDER BY category");
$categories = [];
while ($cat = $catResult->fetch_assoc()) {
    $categories[] = $cat['category'];
}

// Get price range for slider
$priceRange = $conn->query("SELECT MIN(price) as min_price, MAX(price) as max_price FROM products WHERE is_active = 1")->fetch_assoc();
$minPrice = floor($priceRange['min_price'] ?? 0);
$maxPrice = ceil($priceRange['max_price'] ?? 500);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>UX Pacific - Shop All Products</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="style.css" />
      <link rel="icon" type="image/x-icon" href="img/faviconUXP444@4x-789.png" />
  </head>

  <body class="shopAll">
    <div class="page">
      <!-- NAVBAR -->
      <?php include 'includes/header.php'; $isShop = true;?>

      <!-- MAIN CONTENT -->
      <main class="main shop-all-main">
        <!-- Page Header -->
        <section class="shop-all-header">
          <h1 class="shop-all-title">Design <span>Resources &amp;<br class="mobile-title-break"> Products</span></h1>
          <p class="shop-all-subtitle">
            Explore premium UX/UI design resources including digital assets and physical products.
          </p>
        </section>

        <!-- Category Tabs + Sort -->
        <div class="shop-controls">
          <div class="category-tabs">
            <button class="category-tab active" data-filter="all">All</button>
            <?php foreach ($categories as $cat): ?>
              <button class="category-tab" data-filter="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></button>
            <?php endforeach; ?>
          </div>
          <div class="sort-control">
            <label for="sort-select">Sort By:</label>
            <select id="sort-select">
              <option value="newest">Newest</option>
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
                  <span class="price-label">$<span id="price-min-val"><?= $minPrice ?></span></span>
                  <span class="price-separator">-</span>
                  <span class="price-label">$<span id="price-max-val"><?= $maxPrice ?></span>+</span>
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
              <!-- <a href="#" class="promo-btn">Learn More</a> -->
            </div>
          </aside>

          <!-- Product Grid -->
          <div class="shop-products">
            <div class="product-grid shop-grid" id="product-grid">
              <?php
              // Pagination Settings
              $limit = 9;
              $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
              if ($page < 1) $page = 1;
              $offset = ($page - 1) * $limit;

              // Count total products
              $countSql = "SELECT COUNT(*) as total FROM products WHERE is_active = 1 AND (stock > 0 OR available_type != 'physical')";
              $countResult = $conn->query($countSql);
              $totalProducts = $countResult->fetch_assoc()['total'];
              $totalPages = ceil($totalProducts / $limit);

              // Fetch products
              $sql = "SELECT * FROM products WHERE is_active = 1 AND (stock > 0 OR available_type != 'physical') ORDER BY created_at DESC LIMIT ? OFFSET ?";
              $stmt = $conn->prepare($sql);
              $stmt->bind_param("ii", $limit, $offset);
              $stmt->execute();
              $result = $stmt->get_result();

              if ($result && $result->num_rows > 0) {
                  while($row = $result->fetch_assoc()) {
                      $id = $row['id'];
                      $name = htmlspecialchars($row['name']);
                      $jsName = htmlspecialchars(addslashes($row['name']), ENT_QUOTES, 'UTF-8');
                      $jsImage = htmlspecialchars(addslashes($row['image']), ENT_QUOTES, 'UTF-8');
                      $jsCategory = htmlspecialchars(addslashes($row['category']), ENT_QUOTES, 'UTF-8');
                      $jsAvailableType = htmlspecialchars(addslashes($row['available_type'] ?? 'physical'), ENT_QUOTES, 'UTF-8');

                      $price = number_format($row['price'], 2);
                      $old_price = !empty($row['old_price']) ? number_format($row['old_price'], 2) : '';
                      $imgSrc = !empty($row['image']) ? htmlspecialchars($row['image']) : 'img/sticker.webp';
                      $category = htmlspecialchars($row['category']);
                      $rating = number_format($row['rating'] ?: 4.5, 1);
                      $availableType = $row['available_type'] ?? 'physical';

                      // Truncate description
                      $description = htmlspecialchars($row['description']);
                      if (strlen($description) > 100) {
                          $description = substr($description, 0, 100) . '...';
                      }

                      echo "
                      <article class='uxp-product-card shop-product-card' 
    data-product-id='$id' 
    data-category='$category' 
    data-type='$availableType' 
    data-price='{$row['price']}' 
    data-rating='$rating'>

    <a href='product.php?id=$id' 
       class='uxp-product-media js-product-popup' 
       aria-label='View $name'
       data-product-id='$id'>

        <img 
            src='$imgSrc' 
            alt='$name' 
            loading='lazy'
            width='480'
            height='360'
            onerror=\"this.src='img/sticker.webp'\"
        />

        <span class='uxp-product-badge-icon' aria-hidden='true'>
            <svg viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='1.8'>
                <path d='M12 3v18M3 12h18'></path>
                <circle cx='12' cy='12' r='8'></circle>
            </svg>
        </span>
    </a>

    <div class='uxp-product-body'>

        <div class='uxp-product-title-row'>
            <h3>$name</h3>

            <div class='uxp-rating' aria-label='Rating $rating out of 5'>
                <span aria-hidden='true'>&#9733;</span>
                <b>$rating</b>
            </div>
        </div>

        <p>$description</p>

        <p class='uxp-product-spec'>
            Category: $category
        </p>

        <div class='uxp-product-meta'>
            <div class='uxp-product-price'>
                $$price
                " . ($old_price ? "<span class='uxp-old-price'>$$old_price</span>" : "") . "
            </div>
        </div>

        <div class='uxp-product-actions'>

            <a href='product.php?id=$id' 
               class='uxp-card-btn uxp-card-btn-primary js-product-popup'
               data-product-id='$id'>
               Buy Now
            </a>

            <button 
                onclick=\"addToCart(
                    '$id',
                    null,
                    1,
                    {
                        name: '$jsName',
                        price: {$row['price']},
                        image: '$jsImage',
                        category: '$jsCategory'
                    },
                    '$jsAvailableType'
                )\"
                class='uxp-card-btn uxp-card-btn-secondary'
                type='button'
                aria-label='Add to cart'
                " . ($row['stock'] <= 0 && $availableType === 'physical' ? 'disabled' : '') . ">
                Add to Cart
            </button>

        </div>

    </div>
</article>
                      ";
                  }
              } else {
                  echo "<p class='no-products'>No products found.</p>";
              }
              ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav class="pagination">
              <a href="?page=<?= max(1, $page - 1) ?>" class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
              </a>
              <?php
              $startPage = max(1, $page - 2);
              $endPage = min($totalPages, $page + 2);

              if ($startPage > 1) {
                  echo '<a href="?page=1" class="page-num">1</a>';
                  if ($startPage > 2) echo '<span class="page-ellipsis">...</span>';
              }

              for ($i = $startPage; $i <= $endPage; $i++) {
                  $activeClass = $i === $page ? 'active' : '';
                  echo "<a href='?page=$i' class='page-num $activeClass'>$i</a>";
              }

              if ($endPage < $totalPages) {
                  if ($endPage < $totalPages - 1) echo '<span class="page-ellipsis">...</span>';
                  echo "<a href='?page=$totalPages' class='page-num'>$totalPages</a>";
              }
              ?>
              <a href="?page=<?= min($totalPages, $page + 1) ?>" class="page-btn <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
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
    // Shop page specific filtering and sorting
    document.addEventListener('DOMContentLoaded', function() {
      const categoryTabs = document.querySelectorAll('.category-tab');
      const productCards = document.querySelectorAll('.product-card');
      const typeCheckboxes = document.querySelectorAll('input[name="type"]');
      const priceRange = document.getElementById('price-range');
      const sortSelect = document.getElementById('sort-select');

      function filterProducts() {
        const activeCategory = document.querySelector('.category-tab.active')?.dataset.filter || 'all';
        const checkedTypes = Array.from(typeCheckboxes).filter(cb => cb.checked).map(cb => cb.value);
        const maxPrice = parseFloat(priceRange?.value || 9999);

        productCards.forEach(card => {
          const cardCategory = card.dataset.category;
          const cardType = card.dataset.type || 'physical';
          const cardPrice = parseFloat(card.dataset.price || 0);

          const categoryMatch = activeCategory === 'all' || cardCategory === activeCategory;
          const typeMatch = checkedTypes.includes(cardType);
          const priceMatch = cardPrice <= maxPrice;

          card.style.display = (categoryMatch && typeMatch && priceMatch) ? '' : 'none';
        });
      }

      function sortProducts() {
        const sortBy = sortSelect?.value || 'newest';
        const grid = document.getElementById('product-grid');
        const cards = Array.from(productCards);

        cards.sort((a, b) => {
          switch(sortBy) {
            case 'price-low':
              return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
            case 'price-high':
              return parseFloat(b.dataset.price) - parseFloat(a.dataset.price);
            case 'rating':
              return parseFloat(b.dataset.rating) - parseFloat(a.dataset.rating);
            default:
              return 0;
          }
        });

        cards.forEach(card => grid.appendChild(card));
      }

      // Category tabs
      categoryTabs.forEach(tab => {
        tab.addEventListener('click', function() {
          categoryTabs.forEach(t => t.classList.remove('active'));
          this.classList.add('active');
          filterProducts();
        });
      });

      // Type checkboxes
      typeCheckboxes.forEach(cb => {
        cb.addEventListener('change', filterProducts);
      });

      // Price range
      if (priceRange) {
        priceRange.addEventListener('input', function() {
          document.getElementById('price-max-val').textContent = this.value;
          filterProducts();
        });
      }

      // Sort
      if (sortSelect) {
        sortSelect.addEventListener('change', sortProducts);
      }

      document.querySelectorAll('.shop-product-card').forEach(card => {
        card.addEventListener('click', function(event) {
          if (event.target.closest('button')) return;
          const productId = this.dataset.productId;
          if (!productId) return;
          event.preventDefault();
          if (typeof openMarketplaceModal === 'function') {
            openMarketplaceModal('product', productId);
          } else {
            window.location.href = `product.php?id=${encodeURIComponent(productId)}`;
          }
        });
      });
    });
    </script>
  </body>
</html>
