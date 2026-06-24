<?php require_once 'includes/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="UX Pacific Shop - Premium UX/UI design resources" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
    <?php $appUrl = rtrim((string) (getenv('APP_URL') ?: ''), '/'); $canonical = $appUrl !== '' ? $appUrl . '/index.php' : ''; ?>
    <?php if ($canonical !== ''): ?><link rel="canonical" href="<?php echo htmlspecialchars($canonical); ?>" /><?php endif; ?>
    <meta property="og:title" content="UX Pacific — Design Resources" />
    <meta property="og:description" content="Premium UI templates, mockups, and design kits for creators." />
    <meta property="og:type" content="website" />
    <?php if ($canonical !== ''): ?><meta property="og:url" content="<?php echo htmlspecialchars($canonical); ?>" /><?php endif; ?>
    <?php if ($appUrl !== ''): ?><meta property="og:image" content="<?php echo htmlspecialchars($appUrl . '/img/poster.webp'); ?>" /><?php endif; ?>
    <meta name="twitter:card" content="summary_large_image" />
    <title>UX Pacific – Shop</title>
    <?php include 'includes/favicon.php'; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="preconnect" href="https://checkout.razorpay.com" />
    <link rel="dns-prefetch" href="https://unpkg.com" />
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('style.css')); ?>" />
    <?php include 'includes/auth-preload.php'; ?>
    <script defer src="https://unpkg.com/@phosphor-icons/web"></script>
</head>
<body>
    <!-- <header class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <a href="index.php"><img src="img/1.png" alt="UX PACIFIC" class="logo" style="height:28px;" onerror="this.outerHTML='<span style=\'font-weight:700; font-size: 1.25rem; letter-spacing: -0.5px;\'>UX PACIFIC</span>'" /></a>
            </div>
            <nav class="nav-links">
                <a href="index.php" class="active">Home</a>
                <a href="#products">Products</a>
                <a href="#category">Category</a>
                <a href="bundles.php">Bundles</a>
            </nav>
            <div class="nav-actions">
                <form class="nav-search" action="search.php" method="get" role="search">
                    <input id="header-search-input" class="nav-search-input" type="search" name="q" placeholder="Search products" autocomplete="off" />
                    <button class="icon-btn nav-search-trigger" type="submit" aria-label="Search">
                        <img src="img/ss/nav/Vector.png" alt="" />
                    </button>
                </form>
                <a href="cart.php" class="icon-btn cart-btn" aria-label="Cart">
                    <img src="img/ss/hugeicons_shopping-basket-secure-01.png" alt="Cart" />
                </a>
                <div class="user-menu">
                    <a href="account.php" style="display:flex; align-items:center; gap: 8px; text-decoration: none; color: white;">
                        <img src="img/ss/nav/iconoir_user.png" alt="User" />
                        <span style="font-size: 0.875rem;">Mohit</span>
                        <i class="ph ph-caret-down"></i>
                    </a>
                </div>
            </div>
        </div>
    </header> -->
    <?php include 'includes/header.php'; ?>
    <main class="main-content">
        <section class="hero-section">
            <div class="hero-container">
                <h1 class="hero-title">
                    Explore <span class="highlight-italic">High-Quality</span><br />
                    Products <span class="highlight-italic">Made for</span> Creators
                </h1>
                <p class="hero-subtitle">
                    View products on UXPacific Shop and purchase safely on partner platforms like<br> Freepik, Behance, and Gumroad.
                </p>
                <div class="hero-buttons">
                    <a href="#bundles" class="btn btn-primary" style="width: 200px; text-align: center;">Explore Bundles</a>
                    <a href="#products" class="btn btn-secondary" style="width: 200px; text-align: center;">Shop All Products</a>
                </div>
            </div>
        </section>

        <!-- <section class="how-works">
            <h2 class="section-heading">How UX Pacific Shop Works</h2>
            <div class="works-grid">
                <div class="work-card">
                    <div class="work-icon"><img src="img/ss/Vector.png" alt="" /></div>
                    <div class="work-text">
                        <h3>Browse Products</h3>
                        <p>Discover UXPacific merch and design assets in one place. View clean previews and product details before you decide.</p>
                    </div>
                </div>
                <div class="work-card">
                    <div class="work-icon"><img src="img/ss/hugeicons_shopping-basket-secure-01.png" alt="" /></div>
                    <div class="work-text">
                        <h3>Click 'Buy Now'</h3>
                        <p>Choose your product and tap the Buy button. You will be redirected to the official platform where the item is listed.</p>
                    </div>
                </div>
                <div class="work-card">
                    <div class="work-icon"><img src="img/ss/Vector-1.png" alt="" /></div>
                    <div class="work-text">
                        <h3>Purchase Securely</h3>
                        <p>Complete your purchase on trusted sites like Freepik, Behance, or Gumroad. Safe checkout and instant access.</p>
                    </div>
                </div>
            </div>
        </section> -->

        <section id="products" class="uxp-top-products-section" aria-labelledby="top-products-title">
            <div class="uxp-container">
                <div class="uxp-top-products-heading">
                    <h2 id="top-products-title">Top UX Pacific Products</h2>
                    <p>Hand-picked design resources, templates, and tools trusted by creators worldwide.</p>
                </div>

                <?php
                // Top products: active only, featured first (managed in Admin → Products → Featured)
                $topProducts = [];
                $topCategories = [];
                if (isset($conn)) {
                    $sql = 'SELECT * FROM products WHERE is_active = 1 ORDER BY is_featured DESC, rating DESC, COALESCE(view_count, 0) DESC, id DESC LIMIT 8';
                    $result = $conn->query($sql);
                    if ($result) {
                        while ($row = $result->fetch_assoc()) {
                            $topProducts[] = $row;
                            $catLabel = trim((string) ($row['category'] ?? ''));
                            if ($catLabel !== '' && !in_array($catLabel, $topCategories, true)) {
                                $topCategories[] = $catLabel;
                            }
                        }
                    }
                    sort($topCategories, SORT_NATURAL | SORT_FLAG_CASE);
                }
                ?>

                <?php if (count($topCategories) > 1): ?>
                <div class="uxp-product-filters" role="tablist" aria-label="Filter products by category">
                    <button type="button" class="filter-btn active" data-filter="all">All</button>
                    <?php foreach ($topCategories as $catName): ?>
                        <button type="button" class="filter-btn" data-filter="<?php echo htmlspecialchars(strtolower($catName)); ?>">
                            <?php echo htmlspecialchars($catName); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="uxp-product-grid" id="top-products-grid">
                    <?php
                    if ($topProducts) {
                        foreach ($topProducts as $row) {
                            echo uxpIndexProductCard($row);
                        }
                    } elseif (isset($conn)) {
                        echo '<p class="uxp-top-products-empty">No products available yet. Add active products in the admin panel.</p>';
                    }
                    ?>
                </div>

                <div class="uxp-top-products-cta">
                    <a href="shopAll.php" class="btn btn-primary">Shop All Products</a>
                </div>
            </div>
        </section>

        <!-- EXPLORE BY CATEGORY -->
        <section class="uxp-category-section" aria-labelledby="category-title">
  <div class="uxp-container">

    <div class="uxp-category-heading">
        <h2 id="category-title">Browse by Category</h2>
        <p>
            Browse our curated collection of premium resources organized by your needs.
        </p>
    </div>

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

    $featuredCategories = [];
    $pillCategories = [];

    if (isset($conn)) {
        $catSql = "
            SELECT
                c.*,
                COUNT(p.id) AS product_count,
                MIN(p.image) AS product_image
            FROM categories c
            LEFT JOIN products p ON p.category = c.name AND p.is_active = 1
            WHERE c.is_active = 1
            GROUP BY c.id
            ORDER BY c.sort_order ASC, c.id ASC
        ";
        $catResult = $conn->query($catSql);
        if ($catResult && $catResult->num_rows > 0) {
            while ($row = $catResult->fetch_assoc()) {
                $featuredCategories[] = $row;
            }
        }
    }

    $gridCategories = array_slice($featuredCategories, 0, 4);
    $pillCategories = array_slice($featuredCategories, 4);
    ?>

    <div class="uxp-category-grid" role="list">
        <?php if (!empty($gridCategories)): ?>
            <?php foreach ($gridCategories as $index => $cat):
                $name = htmlspecialchars($cat['name']);
                $slug = htmlspecialchars($cat['slug']);
                $description = htmlspecialchars($cat['description'] ?: 'Explore premium curated design resources.');
                $productCount = (int) $cat['product_count'];
                $image = !empty($cat['product_image'])
                    ? htmlspecialchars($cat['product_image'])
                    : $fallbackImages[$index % count($fallbackImages)];
                $colorClass = $colorClasses[$index % count($colorClasses)];
            ?>
                <a href="shopAll.php?category=<?php echo $slug; ?>"
                   class="uxp-category-card <?php echo $colorClass; ?>"
                   role="listitem"
                   aria-label="Browse <?php echo $name; ?>">

                    <div class="uxp-category-art" aria-hidden="true">
                        <span class="uxp-category-bubble"></span>
                        <span class="uxp-category-icon">
                            <img
                                src="<?php echo $image; ?>"
                                alt="<?php echo $name; ?>"
                                loading="lazy"
                                onerror="this.src='img/poster.webp'"
                            />
                        </span>
                    </div>

                    <div class="uxp-category-body">
                        <h3><?php echo $name; ?></h3>
                        <span><?php echo $productCount; ?>+ items</span>
                        <p><?php echo $description; ?></p>
                        <strong>
                            Browse
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M5 12h14"></path>
                                <path d="m13 6 6 6-6 6"></path>
                            </svg>
                        </strong>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="uxp-category-empty">No categories found.</p>
        <?php endif; ?>
    </div>

    <?php if (!empty($pillCategories)): ?>
        <div class="uxp-category-pills" aria-label="More categories">
            <a class="uxp-category-pill" href="shopAll.php">All</a>
            <?php foreach ($pillCategories as $pill):
                $pillName = htmlspecialchars($pill['name']);
                $pillSlug = htmlspecialchars($pill['slug']);
            ?>
                <a class="uxp-category-pill" href="shopAll.php?category=<?php echo $pillSlug; ?>">
                    <?php echo $pillName; ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

  </div>
</section>

        <section id="bundles" class="uxp-bundles-section" aria-labelledby="bundles-title">
  <div class="uxp-container">
    <div class="uxp-bundle-heading">
      <h2 id="bundles-title">Ready-Made Career Bundles</h2>
    </div>

    <div class="uxp-bundle-grid">
      <?php
      if (isset($conn)) {
          $bundleSql = "SELECT * FROM bundles WHERE is_active = 1 ORDER BY is_featured DESC, sales_count DESC, rating DESC LIMIT 2";
          $bundleResult = $conn->query($bundleSql);
          if ($bundleResult && $bundleResult->num_rows > 0) {
              while ($bundle = $bundleResult->fetch_assoc()) {
                  $bId = (int) $bundle['id'];
                  $bName = htmlspecialchars($bundle['name'], ENT_QUOTES, 'UTF-8');
                  $bDesc = htmlspecialchars($bundle['description'] ?? '', ENT_QUOTES, 'UTF-8');
                  $bImage = htmlspecialchars(marketplaceImage($bundle['image'] ?? ''), ENT_QUOTES, 'UTF-8');
                  $bPrice = number_format((float) $bundle['price'], 0);
                  $bOldPrice = !empty($bundle['old_price']) ? number_format((float) $bundle['old_price'], 0) : '';
                  $bRating = number_format((float) ($bundle['rating'] ?? 4.5), 1);
                  $bFeatured = !empty($bundle['is_featured']);
                  $badgeText = $bFeatured ? 'Most Popular' : 'Bundle';

                  $includedItems = [];
                  if (!empty($bundle['whats_included'])) {
                      $lines = explode("\n", $bundle['whats_included']);
                      foreach ($lines as $line) {
                          $line = trim(str_replace(['- ', '* '], '', $line));
                          if ($line !== '') $includedItems[] = htmlspecialchars($line, ENT_QUOTES, 'UTF-8');
                      }
                  }
                  if (empty($includedItems)) {
                      $includedItems = ['Premium design resources', 'Editable source files', 'Bonus templates', 'Personal & commercial license'];
                  }
                  $includedItems = array_slice($includedItems, 0, 4);

                  $jsName = htmlspecialchars(json_encode($bundle['name']), ENT_QUOTES, 'UTF-8');
                  $jsImage = htmlspecialchars(json_encode($bundle['image'] ?? ''), ENT_QUOTES, 'UTF-8');

                  echo <<<HTML
      <article class="uxp-bundle-card" data-type="bundle" data-id="{$bId}" data-product-id="{$bId}" data-name="{$bName}" data-image="{$bImage}" data-price="{$bundle['price']}" data-old-price="{$bundle['old_price']}" data-rating="{$bRating}">
        <div class="uxp-bundle-image">
          <img src="{$bImage}" alt="{$bName} preview" loading="lazy" onerror="this.src='img/poster.webp'" />
          <span>{$badgeText}</span>
        </div>

        <div class="uxp-bundle-content">
          <div class="uxp-bundle-title-row">
            <h3>{$bName}</h3>
            <span class="uxp-bundle-rating" aria-label="Rated {$bRating} out of 5">
              &#9733; <b>{$bRating}</b>
            </span>
          </div>

          <p>{$bDesc}</p>

          <ul>
HTML;
                  foreach ($includedItems as $item) {
                      echo "<li>{$item}</li>";
                  }

                  $oldHtml = $bOldPrice ? "<span>₹{$bOldPrice}</span>" : '';
                  echo <<<HTML
          </ul>

          <div class="uxp-bundle-footer">
            <strong>₹{$bPrice} {$oldHtml}</strong>

            <div>
              <button type="button" class="uxp-card-btn uxp-card-btn-primary js-buy-now" data-product-id="{$bId}" data-item-type="bundle" data-available-type="digital">Buy Now</button>
              <button type="button" class="uxp-card-btn uxp-card-btn-secondary" onclick="addToCart({$bId}, null, 1, {name: {$jsName}, price: {$bundle['price']}, image: {$jsImage}, item_type: 'bundle'}, 'digital')">Add to Cart</button>
            </div>
          </div>
        </div>
      </article>
HTML;
              }
          } else {
              echo '<p class="uxp-bundle-empty">No bundles available yet. Check back soon.</p>';
          }
      }
      ?>

      <a href="bundles.php" class="uxp-bundle-see-all" aria-label="See all bundles">
        <span aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
            <path d="M7 17 17 7"></path>
            <path d="M8 7h9v9"></path>
          </svg>
        </span>
        <b>See all</b>
      </a>
    </div>
  </div>
</section>
    </main>


    <section class="explore-more">
        <div class="explore-banner">
            <h2>Ready to Explore More Products?</h2>
            <p>Explore the complete UXPacific Shop collection and discover high-quality merch, mockups, UI templates, workbooks, badge packs, and creative digital assets designed especially for modern creators.</p>
            <div class="explore-actions">
                <a href="shopAll.php" class="btn btn-primary" style="width: 200px; text-align:center;">Shop All Products</a>
                <a href="https://www.instagram.com/official_uxpacific/" target="_blank" rel="noopener" class="btn btn-secondary" style="width: 200px; text-align:center;">Follow Us</a>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script src="<?php echo htmlspecialchars(asset_url('script.js')); ?>"></script>
</body>
</html>
