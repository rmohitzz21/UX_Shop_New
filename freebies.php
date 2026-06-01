<?php
require_once 'includes/config.php';

// Fetch active freebies
$freebies = [];
$result = $conn->query(
    'SELECT * FROM freebies WHERE is_active = 1 ORDER BY is_featured DESC, sort_order ASC, id DESC'
);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $freebies[] = $row;
    }
}

// Collect unique categories for filter pills
$categories = ['All'];
foreach ($freebies as $f) {
    $cat = $f['category'] ?? 'General';
    if ($cat && !in_array($cat, $categories)) {
        $categories[] = $cat;
    }
}

$totalCount    = count($freebies);
$downloadTotal = array_sum(array_column($freebies, 'download_count'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
    <title>Free Design Resources – UX Pacific Shop</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="icon" type="image/x-icon" href="img/faviconUXP444@4x-789.png" />
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="assets/css/freebies.css" />
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>
<body class="freebies-page">
<div class="page">
    <?php include 'includes/header.php'; ?>
    <main class="main">

        <!-- ── Hero ── -->
        <section class="shop-all-header freebies-hero-section">
            <div class="fh-eyebrow-wrap">
                <span class="fh-eyebrow"><span class="fh-eyebrow-dot"></span>100% Free &nbsp;·&nbsp; No Sign-up Required</span>
            </div>
            <h1 class="shop-all-title">Free <span>Design Resources</span></h1>
            <p class="shop-all-subtitle">
                Curated UX/UI freebies from UX Pacific — templates, UI kits, icons, and assets. No strings attached.
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

            <!-- Search + filter toolbar -->
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

            <!-- Cards grid -->
            <div class="products-grid" id="freebies-grid">

                <?php if (empty($freebies)): ?>
                    <div class="marketplace-empty" style="padding:64px 24px;text-align:center;grid-column:1/-1;">
                        <h3 style="font-size:1.3rem;margin-bottom:8px;">No freebies yet</h3>
                        <p style="color:rgba(255,255,255,0.6);margin-bottom:24px;">Check back soon — free design resources are on the way!</p>
                        <a href="shopAll.php" class="btn btn-primary">Browse Products</a>
                    </div>

                <?php else: ?>
                    <?php foreach ($freebies as $f): ?>
                    <?php
                        $img = htmlspecialchars(!empty($f['image']) ? $f['image'] : '');
                        $name = htmlspecialchars($f['name']);
                        $cat = htmlspecialchars($f['category'] ?? 'General');
                        $desc = htmlspecialchars($f['description'] ?? '');
                        $downloads = number_format((int) $f['download_count']);
                        $dlUrl = 'api/freebies/download.php?id=' . (int) $f['id'];
                        $nameLower = htmlspecialchars(strtolower($f['name']), ENT_QUOTES);
                        $descLower = htmlspecialchars(strtolower($f['description'] ?? ''), ENT_QUOTES);
                        $catRaw = htmlspecialchars($f['category'] ?? 'General', ENT_QUOTES);
                        $fileExt = strtolower(pathinfo((string) ($f['file_url'] ?? ''), PATHINFO_EXTENSION));
                        $formatLabel = match($fileExt) {
                            'fig'           => 'Figma',
                            'pdf'           => 'PDF',
                            'zip', 'rar', '7z' => 'ZIP',
                            'png', 'jpg', 'jpeg', 'webp' => 'Image',
                            'svg'           => 'SVG',
                            'xd'            => 'Adobe XD',
                            'sketch'        => 'Sketch',
                            default         => $fileExt ? strtoupper($fileExt) : '',
                        };
                    ?>
                    <article class="prod-card freebie-card"
                             data-name="<?php echo $nameLower; ?>"
                             data-desc="<?php echo $descLower; ?>"
                             data-cat="<?php echo $catRaw; ?>">

                        <div class="prod-img">
                            <?php if ($img): ?>
                                <img src="<?php echo $img; ?>" alt="<?php echo $name; ?>" loading="lazy" onerror="this.src='img/poster.webp'" />
                            <?php else: ?>
                                <img src="img/poster.webp" alt="<?php echo $name; ?>" loading="lazy" />
                            <?php endif; ?>

                            <?php if (!empty($f['is_featured'])): ?>
                                <span class="freebie-featured-badge">Featured</span>
                            <?php endif; ?>
                            <span class="freebie-free-badge">FREE</span>
                            <?php if ($formatLabel): ?>
                                <span class="freebie-format-badge"><?php echo htmlspecialchars($formatLabel); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="prod-info">
                            <div class="prod-header">
                                <h3 title="<?php echo $name; ?>"><?php echo $name; ?></h3>
                                <span class="freebie-cat-chip"><?php echo $cat; ?></span>
                            </div>

                            <p class="prod-desc"><?php echo $desc ?: '—'; ?></p>

                            <p class="prod-specs">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;vertical-align:-1px;">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                    <polyline points="7 10 12 15 17 10"/>
                                    <line x1="12" y1="15" x2="12" y2="3"/>
                                </svg>
                                <?php echo $downloads; ?> downloads
                            </p>

                            <div class="prod-actions">
                                <?php if (!empty($f['file_url'])): ?>
                                    <a href="<?php echo $dlUrl; ?>"
                                       class="btn btn-primary freebie-dl-btn">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;flex-shrink:0;">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                            <polyline points="7 10 12 15 17 10"/>
                                            <line x1="12" y1="15" x2="12" y2="3"/>
                                        </svg>
                                        Download Free
                                    </a>
                                <?php else: ?>
                                    <span class="btn btn-outline freebie-dl-btn freebie-coming-soon">Coming Soon</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div><!-- /products-grid -->
        </section><!-- /top-products -->

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
                    <h3>No Attribution Required</h3>
                    <p>Use in personal and commercial projects without crediting us.</p>
                </div>
                <div class="freebies-benefit-card">
                    <div class="freebies-benefit-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="7 10 12 15 17 10"/>
                            <line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                    </div>
                    <h3>Instant Download</h3>
                    <p>No sign-up needed. Click download and get your resource immediately.</p>
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
                    <h3>Updated Regularly</h3>
                    <p>New free resources are added every month. Come back often!</p>
                </div>
            </div>
        </section>

    </main>
    <?php include 'includes/footer.php'; ?>
</div>
<script src="script.js"></script>
<script>
let currentFreebieCategory = 'All';

function filterFreebies() {
    const q = document.getElementById('freebies-search').value.trim().toLowerCase();
    const cards = document.querySelectorAll('#freebies-grid .freebie-card');
    let visible = 0;

    cards.forEach(card => {
        const matchSearch = !q
            || (card.dataset.name || '').includes(q)
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
