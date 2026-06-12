<?php require_once 'includes/config.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Ready-made UI/UX career bundles from UX Pacific." />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />

    <title>Bundles - UX Pacific Shop</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,200;0,9..40,300;0,9..40,600;1,9..40,200&family=Gabarito:wght@400;500;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />

    <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('style.css')); ?>" />
    <link rel="stylesheet" href="assets/css/bundles.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script defer src="https://unpkg.com/@phosphor-icons/web"></script>
</head>

<body class="bundles-page figma-bundles">

<?php
$headerUserId = $_SESSION['user_id'] ?? null;

$headerFirstName = trim((string) ($_SESSION['first_name'] ?? ''));
$headerLastName = trim((string) ($_SESSION['last_name'] ?? ''));

$headerUserName = trim($headerFirstName . ' ' . $headerLastName);

if ($headerUserName === '') {
    $headerUserName = 'Profile';
}
?>

<?php include 'includes/header.php'; ?>

<main class="bundles-figma-main">

    <!-- HERO -->
    <section class="figma-hero">

     

   

    </section>

    <!-- BEST SELLER SLIDER -->
    <?php
    /* Best Seller horizontal slider: only bundles marked is_featured = 1 in admin */
    $featuredSql = "
        SELECT *
        FROM bundles
        WHERE is_active = 1 AND is_featured = 1
        ORDER BY rating DESC, updated_at DESC, id DESC
        LIMIT 10
    ";

    $featuredResult = $conn->query($featuredSql);
    $featuredBundles = [];
    if ($featuredResult && $featuredResult->num_rows > 0) {
        while ($row = $featuredResult->fetch_assoc()) {
            $featuredBundles[] = $row;
        }
    }
    $featuredCount = count($featuredBundles);
    $featuredIds = $featuredCount > 0
        ? array_map(static fn ($b) => (int) $b['id'], $featuredBundles)
        : [];

    $bundleFilterPills = [
        'all' => 'All',
        'portfolio' => 'Portfolio',
        'case-study' => 'Case Study',
        'ui-kits' => 'UI Kits',
        'freelancing' => 'Freelancing',
        'design-system' => 'Design System',
        'career-pack' => 'Career Pack',
        'interview-prep' => 'Interview Prep',
    ];

    $bundleIncludedItems = function (array $bundle): array {
        $items = [];
        if (!empty($bundle['whats_included'])) {
            $lines = preg_split('/\r\n|\r|\n/', trim((string) $bundle['whats_included']));
            foreach ($lines as $line) {
                $line = trim(preg_replace('/^[\-\*•]\s*/', '', trim($line)));
                if ($line !== '') {
                    $items[] = $line;
                }
            }
        }
        if ($items === []) {
            $decoded = json_decode($bundle['included_items'] ?? '[]', true);
            if (is_array($decoded)) {
                $items = $decoded;
            }
        }
        return $items;
    };

    $bundleCategoryKey = function (array $bundle): string {
        $raw = strtolower(trim((string) ($bundle['category'] ?? '')));
        $raw = preg_replace('/[^a-z0-9]+/', '-', $raw);
        return trim($raw, '-') ?: 'career-pack';
    };

    $bundleReviewCount = function (int $bundleId) use ($conn): int {
        $stmt = $conn->prepare('SELECT COUNT(*) AS c FROM reviews WHERE bundle_id = ? AND is_approved = 1');
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('i', $bundleId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return (int) ($row['c'] ?? 0);
    };
    ?>

    <?php if ($featuredCount > 0): ?>
        <section class="bundles-bestseller" aria-label="Best seller bundles">

            <div class="bs-section-label">
                <p class="bs-eyebrow">★ Featured Bundles</p>
                <h2 class="bs-heading">Best Sellers</h2>
            </div>

            <nav class="bundles-filter-bar" aria-label="Bundle categories">
                <?php foreach ($bundleFilterPills as $slug => $label): ?>
                    <button
                        type="button"
                        class="bundles-filter-pill<?php echo $slug === 'all' ? ' is-active' : ''; ?>"
                        data-filter="<?php echo htmlspecialchars($slug); ?>">
                        <?php echo htmlspecialchars($label); ?>
                    </button>
                <?php endforeach; ?>
            </nav>

            <div class="bundles-bestseller-stage">
                <?php if ($featuredCount > 1): ?>
                    <button type="button" class="bs-nav bs-nav--prev bundles-featured-prev" aria-label="Previous bundle">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                <?php endif; ?>

                <div class="bundles-featured-swiper swiper" data-slide-count="<?php echo (int) $featuredCount; ?>">
                    <div class="swiper-wrapper">
                        <?php foreach ($featuredBundles as $featured):
                            $featuredItems = $bundleIncludedItems($featured);
                            $categoryKey = $bundleCategoryKey($featured);
                            $tagsKey = strtolower(preg_replace('/[^a-z0-9]+/', '-', (string) ($featured['tags'] ?? '')));
                            $reviewCount = $bundleReviewCount((int) $featured['id']);
                            $rating = number_format((float) ($featured['rating'] ?: 4.9), 1);
                            $fOld = (float) ($featured['old_price'] ?? 0);
                            $fNew = (float) $featured['price'];
                            $discountPct = ($fOld > $fNew && $fNew > 0) ? (int) round(($fOld - $fNew) / $fOld * 100) : 0;
                        ?>
                            <div
                                class="swiper-slide"
                                data-category="<?php echo htmlspecialchars($categoryKey); ?>"
                                data-tags="<?php echo htmlspecialchars(trim($tagsKey, '-')); ?>">
                                <article class="bs-card" data-type="bundle" data-id="<?php echo (int) $featured['id']; ?>" data-product-id="<?php echo (int) $featured['id']; ?>" data-name="<?php echo htmlspecialchars($featured['name']); ?>" data-image="<?php echo htmlspecialchars($featured['image'] ?: 'img/poster.webp'); ?>" data-price="<?php echo (float) $featured['price']; ?>" data-old-price="<?php echo (float) ($featured['old_price'] ?? 0); ?>" data-rating="<?php echo htmlspecialchars($rating); ?>">
                                    <div class="bs-card-media">
                                        <img
                                            src="<?php echo htmlspecialchars($featured['image'] ?: 'img/poster.webp'); ?>"
                                            alt="<?php echo htmlspecialchars($featured['name']); ?>"
                                            onerror="this.src='img/poster.webp'"
                                        />
                                        <span class="bs-badge"><?php echo htmlspecialchars($featured['badge_text'] ?? 'Best Seller'); ?></span>
                                        <?php if ($discountPct > 0): ?>
                                        <span class="bs-discount-badge">Save <?php echo $discountPct; ?>%</span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="bs-card-body">
                                        <h2 class="bs-card-title"><?php echo htmlspecialchars($featured['name']); ?></h2>

                                        <div class="bs-rating">
                                            <span class="bs-stars" aria-hidden="true">★★★★★</span>
                                            <span class="bs-rating-text">
                                                <?php echo htmlspecialchars($rating); ?>
                                                <?php if ($reviewCount > 0): ?>
                                                    (<?php echo number_format($reviewCount); ?> reviews)
                                                <?php else: ?>
                                                    rating
                                                <?php endif; ?>
                                            </span>
                                        </div>

                                        <p class="bs-desc">
                                            <?php echo htmlspecialchars($featured['description'] ?: 'Everything you need to build a professional UI/UX portfolio and land your dream job.'); ?>
                                        </p>

                                        <p class="bs-includes-title">What's Included</p>
                                        <div class="bs-chips">
                                            <?php
                                            $chipItems = !empty($featuredItems)
                                                ? array_slice($featuredItems, 0, 5)
                                                : ['15+ UI Screens', 'UX Workbook', 'Interview Guide', 'Portfolio Templates', 'Design System'];
                                            foreach ($chipItems as $chip):
                                            ?>
                                                <span class="bs-chip"><?php echo htmlspecialchars($chip); ?></span>
                                            <?php endforeach; ?>
                                        </div>

                                        <div class="bs-pricing">
                                            <strong class="bs-price">₹<?php echo number_format((float) $featured['price']); ?></strong>
                                            <?php if (!empty($featured['old_price'])): ?>
                                                <del class="bs-price-old">₹<?php echo number_format((float) $featured['old_price']); ?></del>
                                            <?php endif; ?>
                                        </div>

                                        <div class="bs-actions">
                                            <button type="button" class="bs-btn bs-btn--primary js-product-popup" data-product-id="<?php echo (int) $featured['id']; ?>" data-item-type="bundle">View Details</button>
                                            <button
                                                type="button"
                                                class="bs-btn bs-btn--outline js-buy-now"
                                                data-product-id="<?php echo (int) $featured['id']; ?>"
                                                data-item-type="bundle">
                                                Buy Now
                                            </button>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if ($featuredCount > 1): ?>
                    <button type="button" class="bs-nav bs-nav--next bundles-featured-next" aria-label="Next bundle">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                <?php endif; ?>
            </div>

            <?php if ($featuredCount > 1): ?>
                <div class="swiper-pagination bundles-featured-pagination" aria-label="Featured pagination"></div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <!-- BUNDLE GRID (excludes bundles already in Best Seller slider) -->
    <div class="uxp-top-products-heading">
        <h2>All Bundles</h2>
        <p>Browse our complete collection of curated design bundles — packed with templates, guides, and career-ready assets.</p>
    </div>

    <section class="figma-card-grid" aria-label="Career bundles">

        <?php
        $excludeFeatured = $featuredIds !== []
            ? ' AND b.id NOT IN (' . implode(',', $featuredIds) . ')'
            : '';

        $bundleSql = "
            SELECT 
                b.*,
                COUNT(bi.id) AS product_count
            FROM bundles b
            LEFT JOIN bundle_items bi
                ON bi.bundle_id = b.id
            WHERE b.is_active = 1
            {$excludeFeatured}
            GROUP BY b.id
            ORDER BY b.updated_at DESC, b.id DESC
        ";

        $bundleResult = $conn->query($bundleSql);

        if ($bundleResult && $bundleResult->num_rows > 0):

            while($bundle = $bundleResult->fetch_assoc()):

                $includedItems = $bundleIncludedItems($bundle);

        ?>

        <?php
        $gridBundleId = (int) $bundle['id'];
        $gridBundleName = htmlspecialchars($bundle['name']);
        $gridBundleImage = htmlspecialchars($bundle['image'] ?: 'img/poster1.webp');
        $gridBundlePrice = (float) $bundle['price'];
        $gridBundleOldPrice = (float) ($bundle['old_price'] ?? 0);
        $gridBundleRating = htmlspecialchars($bundle['rating'] ?: '4.5');
        ?>
        <article class="uxp-bundle-card" data-type="bundle" data-id="<?php echo $gridBundleId; ?>" data-product-id="<?php echo $gridBundleId; ?>" data-name="<?php echo $gridBundleName; ?>" data-image="<?php echo $gridBundleImage; ?>" data-price="<?php echo $gridBundlePrice; ?>" data-old-price="<?php echo $gridBundleOldPrice; ?>" data-rating="<?php echo $gridBundleRating; ?>">

            <div class="uxp-bundle-image">

                <img
                    src="<?php echo $gridBundleImage; ?>"
                    alt="<?php echo $gridBundleName; ?>"
                    loading="lazy"
                    onerror="this.src='img/poster1.webp'"
                />

                <span>

                    <?php
                    echo htmlspecialchars(
                        $bundle['badge_text'] ?? 'Most Popular'
                    );
                    ?>

                </span>

            </div>

            <div class="uxp-bundle-content">

                <div class="uxp-bundle-title-row">

                    <h3>
                        <?php echo htmlspecialchars($bundle['name']); ?>
                    </h3>

                    <span class="uxp-bundle-rating">

                        &#9733;

                        <b>
                            <?php
                            echo htmlspecialchars(
                                $bundle['rating']
                                ?: '4.5'
                            );
                            ?>
                        </b>

                    </span>

                </div>

                <p>

                    <?php
                    echo htmlspecialchars(
                        $bundle['description']
                        ?: 'Premium curated UI/UX bundle.'
                    );
                    ?>

                </p>

                <ul>

                    <?php if (!empty($includedItems)): ?>

                        <?php foreach($includedItems as $item): ?>

                            <li>
                                <?php echo htmlspecialchars($item); ?>
                            </li>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <li>Premium templates</li>
                        <li>Career-ready assets</li>
                        <li>Professional resources</li>
                        <li>Design systems included</li>

                    <?php endif; ?>

                </ul>

                <div class="uxp-bundle-footer">

                    <strong>

                        ₹<?php echo number_format((float)$bundle['price']); ?>

                        <?php if (!empty($bundle['old_price'])): ?>

                            <span>
                                ₹<?php echo number_format((float)$bundle['old_price']); ?>
                            </span>

                        <?php endif; ?>

                    </strong>

                    <div>

                        <button
                            type="button"
                            class="uxp-card-btn uxp-card-btn-primary js-buy-now"
                            data-product-id="<?php echo $gridBundleId; ?>"
                            data-item-type="bundle">

                            Buy Now

                        </button>

                        <button
                            class="uxp-card-btn uxp-card-btn-secondary"
                            type="button"

                            onclick='addToCart(
                                <?php echo (int) $gridBundleId; ?>,
                                null,
                                1,
                                {
                                    name: <?php echo json_encode($bundle["name"]); ?>,
                                    price: <?php echo $gridBundlePrice; ?>,
                                    image: <?php echo json_encode($bundle["image"]); ?>,
                                    category: "Bundle",
                                    description: <?php echo json_encode($bundle["description"]); ?>,
                                    item_type: "bundle"
                                },
                                "digital"
                            )'>

                            Add to Cart

                        </button>

                    </div>

                </div>

            </div>

        </article>

        <?php endwhile; ?>

        <?php else: ?>

            <p>No bundles available.</p>

        <?php endif; ?>

    </section>

    <!-- GROW SECTION -->
    <section class="figma-grow-section">

        <h2>How These Bundles Help You Grow</h2>

        <div class="figma-grow-grid">

            <article>
                <strong>01</strong>
                <h3>Learn Skills</h3>
                <p>Master UI/UX fundamentals</p>
            </article>

            <article>
                <strong>02</strong>
                <h3>Build Portfolio</h3>
                <p>Create stunning case studies</p>
            </article>

            <article>
                <strong>03</strong>
                <h3>Practice UX</h3>
                <p>Apply real-world methods</p>
            </article>

            <article>
                <strong>04</strong>
                <h3>Apply for Jobs</h3>
                <p>Land your dream role</p>
            </article>

        </div>

    </section>

</main>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script src="<?php echo htmlspecialchars(asset_url('script.js')); ?>"></script>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const section = document.querySelector('.bundles-bestseller');
    const el = document.querySelector('.bundles-featured-swiper');
    if (!section || !el || typeof Swiper === 'undefined') return;

    const slideCount = parseInt(el.dataset.slideCount || '0', 10);
    const hasMultiple = slideCount > 1;

    function triggerSlideAnim(slide) {
      if (!slide) return;
      slide.classList.remove('bs-anim');
      void slide.offsetWidth; // force reflow to restart CSS animation
      slide.classList.add('bs-anim');
    }

    const featuredSwiper = new Swiper(el, {
      slidesPerView: 1,
      slidesPerGroup: 1,
      spaceBetween: 0,
      speed: 760,
      effect: 'slide',
      autoHeight: true,
      grabCursor: hasMultiple,
      watchOverflow: true,
      allowTouchMove: hasMultiple,
      loop: hasMultiple,
      loopAdditionalSlides: hasMultiple ? 2 : 0,
      keyboard: { enabled: hasMultiple, onlyInViewport: true },
      autoplay: hasMultiple ? {
        delay: 4800,
        disableOnInteraction: false,
        pauseOnMouseEnter: true,
      } : false,
      pagination: hasMultiple ? {
        el: section.querySelector('.bundles-featured-pagination'),
        clickable: true,
      } : false,
      navigation: hasMultiple ? {
        prevEl: section.querySelector('.bundles-featured-prev'),
        nextEl: section.querySelector('.bundles-featured-next'),
      } : false,
      on: {
        init: function () { triggerSlideAnim(this.slides[this.activeIndex]); },
        slideChangeTransitionStart: function () { triggerSlideAnim(this.slides[this.activeIndex]); },
      },
    });

    function slideMatchesFilter(slide, filter) {
      if (filter === 'all') return true;
      const cat = (slide.dataset.category || '').toLowerCase();
      const tags = (slide.dataset.tags || '').toLowerCase();
      return cat.includes(filter) || tags.includes(filter);
    }

    function applyBundleFilter(filter) {
      const slides = Array.from(el.querySelectorAll('.swiper-slide'));
      let firstVisible = -1;

      slides.forEach(function (slide, index) {
        const match = slideMatchesFilter(slide, filter);
        slide.classList.toggle('swiper-slide-hidden', !match);
        if (match && firstVisible === -1) {
          firstVisible = index;
        }
      });

      if (firstVisible === -1) {
        slides.forEach(function (slide) {
          slide.classList.remove('swiper-slide-hidden');
        });
        firstVisible = 0;
      }

      const visibleCount = slides.filter(function (s) {
        return !s.classList.contains('swiper-slide-hidden');
      }).length;

      featuredSwiper.params.loop = visibleCount > 1;
      featuredSwiper.update();
      featuredSwiper.slideTo(firstVisible, 400);
    }

    section.querySelectorAll('.bundles-filter-pill').forEach(function (pill) {
      pill.addEventListener('click', function () {
        section.querySelectorAll('.bundles-filter-pill').forEach(function (p) {
          p.classList.remove('is-active');
        });
        pill.classList.add('is-active');
        applyBundleFilter((pill.dataset.filter || 'all').toLowerCase());
      });
    });
  });
</script>

</body>
</html>