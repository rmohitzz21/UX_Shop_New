<?php
require_once 'includes/config.php';
require_once __DIR__ . '/api/admin/resources/_helpers.php';

drEnsureFreebieResourcesColumn($conn);

/**
 * Unified freebies listing: standalone freebies + products marked is_free in admin.
 */
$freebieItems = [];

$freebieResult = $conn->query(
    'SELECT f.*, "freebie" AS catalog_source,
            (SELECT COUNT(*) FROM digital_resources dr
             WHERE dr.freebie_id = f.id AND dr.is_active = 1) AS resource_count
     FROM freebies f
     WHERE f.is_active = 1
     ORDER BY f.is_featured DESC, f.sort_order ASC, f.id DESC'
);
if ($freebieResult) {
    while ($row = $freebieResult->fetch_assoc()) {
        $row['catalog_source'] = 'freebie';
        $freebieItems[] = $row;
    }
}

$productSql = "
    SELECT p.*, 'product' AS catalog_source,
           (SELECT COUNT(*) FROM digital_resources dr
            WHERE dr.product_id = p.id AND dr.is_active = 1) AS resource_count
    FROM products p
    WHERE p.is_active = 1 AND p.is_free = 1
    ORDER BY p.is_featured DESC, p.sales_count DESC, p.id DESC
";
$productResult = $conn->query($productSql);
if ($productResult) {
    while ($row = $productResult->fetch_assoc()) {
        $row['catalog_source'] = 'product';
        $freebieItems[] = $row;
    }
}

usort($freebieItems, static function (array $a, array $b): int {
    $featA = !empty($a['is_featured']) ? 1 : 0;
    $featB = !empty($b['is_featured']) ? 1 : 0;
    if ($featA !== $featB) {
        return $featB <=> $featA;
    }
    $sortA = (int) ($a['sort_order'] ?? 0);
    $sortB = (int) ($b['sort_order'] ?? 0);
    if ($sortA !== $sortB) {
        return $sortB <=> $sortA;
    }
    return (int) ($b['id'] ?? 0) <=> (int) ($a['id'] ?? 0);
});

$categories = ['All'];
foreach ($freebieItems as $f) {
    $cat = $f['category'] ?? 'General';
    if ($cat && !in_array($cat, $categories, true)) {
        $categories[] = $cat;
    }
}

$totalCount = count($freebieItems);
$downloadTotal = 0;
foreach ($freebieItems as $f) {
    if (($f['catalog_source'] ?? '') === 'product') {
        $downloadTotal += (int) ($f['sales_count'] ?? 0);
    } else {
        $downloadTotal += (int) ($f['download_count'] ?? 0);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
    <title>Free Design Resources – UX Pacific Shop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="icon" type="image/png" href="img/fav.png" />
    <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('style.css')); ?>" />
    <link rel="stylesheet" href="assets/css/freebies.css" />
    <script defer src="https://unpkg.com/@phosphor-icons/web"></script>
</head>
<body class="freebies-page">
<div class="page">
    <?php include 'includes/header.php'; ?>
    <main class="main">

        <!-- ── Hero ── -->
        <section class="shop-all-header freebies-hero-section">
            <div class="fh-eyebrow-wrap">
                <span class="fh-eyebrow"><span class="fh-eyebrow-dot"></span>100% Free &nbsp;·&nbsp; Same checkout as paid digital items</span>
            </div>
            <h1 class="shop-all-title">Free <span>Design Resources</span></h1>
            <p class="shop-all-subtitle">
                Curated UX/UI freebies from UX Pacific — templates, UI kits, icons, and assets. Get Free uses the same cart and order flow at ₹0.
            </p>
            <div class="freebies-stats">
                <div class="freebies-stat">
                    <span class="freebies-stat-num"><?php echo $totalCount; ?>+</span>
                    <span class="freebies-stat-label">Resources</span>
                </div>
                <div class="freebies-stat">
                    <span class="freebies-stat-num"><?php echo number_format($downloadTotal); ?></span>
                    <span class="freebies-stat-label">Downloads</span>
                </div>
                <div class="freebies-stat">
                    <span class="freebies-stat-num"><?php echo max(count($categories) - 1, 0); ?></span>
                    <span class="freebies-stat-label">Categories</span>
                </div>
                <div class="freebies-stat">
                    <span class="freebies-stat-num">100%</span>
                    <span class="freebies-stat-label">Free</span>
                </div>
            </div>
        </section>

        <!-- ── Main listing ── -->
        <section class="top-products shop-listing">

            <div class="freebies-toolbar">
                <div class="freebies-search-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <input
                        type="text"
                        class="freebies-search"
                        id="freebies-search"
                        placeholder="Search resources…"
                        oninput="filterFreebies()"
                    />
                </div>
                <div class="product-filters freebies-filter-row" id="freebies-filters">
                    <?php foreach ($categories as $cat): ?>
                        <button
                            class="filter-btn<?php echo $cat === 'All' ? ' active' : ''; ?>"
                            data-cat="<?php echo htmlspecialchars($cat, ENT_QUOTES); ?>"
                            onclick="setFreebieCategory(this)"
                        ><?php echo htmlspecialchars($cat); ?></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="products-grid" id="freebies-grid">

                <?php if (empty($freebieItems)): ?>
                    <div class="marketplace-empty" style="padding:64px 24px;text-align:center;grid-column:1/-1;">
                        <h3 style="font-size:1.3rem;margin-bottom:8px;">No freebies yet</h3>
                        <p style="color:rgba(255,255,255,0.6);margin-bottom:24px;">Mark a product as <strong>Free</strong> in admin, or add a standalone freebie.</p>
                        <a href="shopAll.php" class="btn btn-primary">Browse Products</a>
                    </div>

                <?php else: ?>
                    <?php foreach ($freebieItems as $f): ?>
                    <?php
                        $source = $f['catalog_source'] ?? 'freebie';
                        $isProduct = $source === 'product';
                        $fid = (int) $f['id'];
                        $cardType = $isProduct ? 'product' : 'freebie';
                        $img = htmlspecialchars(!empty($f['image']) ? $f['image'] : 'img/poster.webp');
                        $name = htmlspecialchars($f['name']);
                        $jsName = htmlspecialchars($f['name'], ENT_QUOTES);
                        $jsImage = htmlspecialchars(!empty($f['image']) ? $f['image'] : 'img/poster.webp', ENT_QUOTES);
                        $cat = htmlspecialchars($f['category'] ?? 'General');
                        $desc = htmlspecialchars(mb_strimwidth($f['description'] ?? '', 0, 100, '…'));
                        $rating = htmlspecialchars(number_format((float) ($f['rating'] ?? 4.5), 1));
                        if ($isProduct) {
                            $downloads = number_format((int) ($f['sales_count'] ?? 0));
                            // Free products use normal product checkout; digital delivery via admin resources.
                            $hasFile = true;
                        } else {
                            $downloads = number_format((int) ($f['download_count'] ?? 0));
                            $hasFile = !empty($f['file_url']) || (int) ($f['resource_count'] ?? 0) > 0;
                        }
                        $nameLower = htmlspecialchars(strtolower($f['name']), ENT_QUOTES);
                        $descLower = htmlspecialchars(strtolower($f['description'] ?? ''), ENT_QUOTES);
                        $catRaw = htmlspecialchars($f['category'] ?? 'General', ENT_QUOTES);
                        $formatPath = $isProduct
                            ? (string) ($f['digital_file_path'] ?? '')
                            : (string) ($f['file_url'] ?? '');
                        $fileExt = strtolower(pathinfo($formatPath, PATHINFO_EXTENSION));
                        $formatLabel = match($fileExt) {
                            'fig'           => 'Figma',
                            'pdf'           => 'PDF',
                            'zip', 'rar', '7z' => 'ZIP',
                            'png', 'jpg', 'jpeg', 'webp' => 'Image',
                            'svg'           => 'SVG',
                            'xd'            => 'Adobe XD',
                            'sketch'        => 'Sketch',
                            default         => $isProduct ? 'Digital' : ($fileExt ? strtoupper($fileExt) : ''),
                        };
                        $jsItemType = $isProduct ? 'product' : 'freebie';
                        $jsAvail = $isProduct ? htmlspecialchars($f['available_type'] ?? 'digital', ENT_QUOTES) : 'digital';
                    ?>
                    <article class="uxp-product-card shop-product-card freebie-card"
                             data-type="<?php echo $cardType; ?>"
                             data-product-id="<?php echo $fid; ?>"
                             data-id="<?php echo $fid; ?>"
                             data-name="<?php echo $name; ?>"
                             data-image="<?php echo $img; ?>"
                             data-category="<?php echo $cat; ?>"
                             data-price="0"
                             data-rating="<?php echo $rating; ?>"
                             data-name-filter="<?php echo $nameLower; ?>"
                             data-desc="<?php echo $descLower; ?>"
                             data-cat="<?php echo $catRaw; ?>">

                        <a href="#"
                           class="uxp-product-media js-product-popup"
                           aria-label="Quick view <?php echo $name; ?>"
                           data-product-id="<?php echo $fid; ?>"
                           data-item-type="<?php echo $cardType; ?>">
                            <img src="<?php echo $img; ?>" alt="<?php echo $name; ?>" loading="lazy"
                                 onerror="this.src='img/poster.webp'" />
                            <?php if (!empty($f['is_featured'])): ?>
                                <span class="freebie-featured-badge">Featured</span>
                            <?php endif; ?>
                            <span class="freebie-free-badge">FREE</span>
                            <?php if ($formatLabel): ?>
                                <span class="freebie-format-badge"><?php echo htmlspecialchars($formatLabel); ?></span>
                            <?php endif; ?>
                        </a>

                        <div class="uxp-product-body">
                            <div class="uxp-product-title-row">
                                <h3 title="<?php echo $name; ?>"><?php echo $name; ?></h3>
                                <div class="uxp-rating" aria-label="Rating <?php echo $rating; ?> out of 5">
                                    <span aria-hidden="true">&#9733;</span>
                                    <b><?php echo $rating; ?></b>
                                </div>
                            </div>

                            <p><?php echo $desc ?: 'Free design resource from UX Pacific.'; ?></p>
                            <p class="uxp-product-spec">Category: <?php echo $cat; ?> · <?php echo $downloads; ?> <?php echo $isProduct ? 'orders' : 'downloads'; ?></p>

                            <div class="uxp-product-meta">
                                <div class="uxp-product-price">₹0</div>
                            </div>

                            <div class="uxp-product-actions">
                                <?php if ($hasFile): ?>
                                <button type="button"
                                    class="uxp-card-btn uxp-card-btn-primary js-buy-now"
                                    data-product-id="<?php echo $fid; ?>"
                                    data-item-type="<?php echo $cardType; ?>">Get Free</button>
                                <button type="button"
                                    class="uxp-card-btn uxp-card-btn-secondary"
                                    onclick="addToCart('<?php echo $fid; ?>',null,1,{name:'<?php echo $jsName; ?>',price:0,image:'<?php echo $jsImage; ?>',category:'<?php echo $cat; ?>',item_type:'<?php echo $jsItemType; ?>'},'<?php echo $jsAvail; ?>')">
                                    Add to Cart
                                </button>
                                <?php else: ?>
                                <span class="uxp-card-btn uxp-card-btn-primary freebie-coming-soon" style="opacity:0.6;pointer-events:none;">Coming Soon</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>
        </section>

        <!-- ── Benefits ── -->
        <section class="top-products freebies-benefits">
            <span class="freebies-benefits-eyebrow">Why us</span>
            <h2 class="freebies-benefits-title">Why Download Our Freebies?</h2>
            <div class="freebies-benefits-grid">
                <div class="freebies-benefit-card">
                    <div class="freebies-benefit-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                    </div>
                    <h3>Premium Quality</h3>
                    <p>Every resource is crafted by professional designers to meet real-world standards.</p>
                </div>
                <div class="freebies-benefit-card">
                    <div class="freebies-benefit-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="16 12 12 8 8 12"/>
                            <line x1="12" y1="16" x2="12" y2="8"/>
                        </svg>
                    </div>
                    <h3>Tracked in My Orders</h3>
                    <p>Free products use the same checkout — downloads appear in your account like paid digital items.</p>
                </div>
                <div class="freebies-benefit-card">
                    <div class="freebies-benefit-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="7 10 12 15 17 10"/>
                            <line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                    </div>
                    <h3>Instant Access</h3>
                    <p>Complete checkout at ₹0 and access files from order confirmation and My Orders.</p>
                </div>
                <div class="freebies-benefit-card">
                    <div class="freebies-benefit-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                    </div>
                    <h3>Easy for admins</h3>
                    <p>Tick <strong>Free product</strong> on any digital product in admin — it appears here automatically.</p>
                </div>
            </div>
        </section>

    </main>
    <?php include 'includes/footer.php'; ?>
</div>
<script src="<?php echo htmlspecialchars(asset_url('script.js')); ?>"></script>
<script>
let currentFreebieCategory = 'All';

function filterFreebies() {
    const q = document.getElementById('freebies-search').value.trim().toLowerCase();
    const cards = document.querySelectorAll('#freebies-grid .freebie-card');
    let visible = 0;

    cards.forEach(card => {
        const matchSearch = !q
            || (card.dataset.nameFilter || card.dataset.name || '').includes(q)
            || (card.dataset.desc || '').includes(q);
        const matchCat = currentFreebieCategory === 'All'
            || (card.dataset.cat || '') === currentFreebieCategory;

        const show = matchSearch && matchCat;
        card.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    let empty = document.getElementById('freebies-no-results');
    if (!visible && cards.length > 0) {
        if (!empty) {
            empty = document.createElement('div');
            empty.id = 'freebies-no-results';
            empty.style.cssText = 'grid-column:1/-1;text-align:center;padding:64px 24px;';
            empty.innerHTML = '<h3 style="margin-bottom:8px;">No results found</h3><p style="color:rgba(255,255,255,0.6);">Try a different keyword or category.</p>';
            document.getElementById('freebies-grid').appendChild(empty);
        }
        empty.style.display = '';
    } else if (empty) {
        empty.style.display = 'none';
    }
}

function setFreebieCategory(btn) {
    document.querySelectorAll('#freebies-filters .filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentFreebieCategory = btn.dataset.cat;
    filterFreebies();
}
</script>
</body>
</html>
