<?php
/**
 * Create bundles tables + insert sample bundles (one file setup).
 *
 * Browser: http://localhost/Shop/UX_SHOP/UX_Shop_New/seed-bundles.php
 * CLI:     php seed-bundles.php
 *
 * Tables created:
 *   - bundles       (main bundle products, Best Seller = is_featured)
 *   - bundle_items  (links bundles to products)
 *
 * Optional .env: SEED_KEY=your-secret (then use ?key=your-secret in browser)
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';

$isCli = PHP_SAPI === 'cli';
$seedKey = trim((string) (getenv('SEED_KEY') ?: ''));
if (!$isCli && $seedKey !== '') {
    $provided = trim((string) ($_GET['key'] ?? ''));
    if (!hash_equals($seedKey, $provided)) {
        http_response_code(403);
        exit('Forbidden. Provide ?key= matching SEED_KEY in .env');
    }
}

/**
 * Create / upgrade bundles-related tables in MySQL.
 *
 * @return array{tables: list<string>, messages: list<string>}
 */
function seedEnsureBundleTables(mysqli $conn): array {
    $messages = [];

    $conn->query("CREATE TABLE IF NOT EXISTS bundles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NULL,
        description TEXT NULL,
        whats_included TEXT NULL,
        file_specification TEXT NULL,
        category VARCHAR(100) DEFAULT 'Bundles',
        tags VARCHAR(500) NULL,
        included_items TEXT NULL,
        price DECIMAL(10,2) NOT NULL DEFAULT 0,
        old_price DECIMAL(10,2) NULL,
        image VARCHAR(255) NULL,
        badge_text VARCHAR(80) NULL DEFAULT 'Best Seller',
        additional_images TEXT NULL,
        rating DECIMAL(3,2) DEFAULT 4.70,
        stock INT NOT NULL DEFAULT 999,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        is_featured TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = Best Seller horizontal slider',
        sales_count INT NOT NULL DEFAULT 0,
        view_count INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_bundles_slug (slug),
        INDEX idx_bundle_active_featured (is_active, is_featured),
        INDEX idx_bundle_category (category)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $messages[] = 'Table `bundles` ready.';

    $conn->query("CREATE TABLE IF NOT EXISTS bundle_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        bundle_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_bundle_product (bundle_id, product_id),
        INDEX idx_bundle_items_bundle (bundle_id),
        INDEX idx_bundle_items_product (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $messages[] = 'Table `bundle_items` ready (links bundles ↔ products).';

    // Add any missing columns on older databases
    $columns = [
        'included_items' => "`included_items` TEXT NULL AFTER `tags`",
        'whats_included' => "`whats_included` TEXT NULL AFTER `description`",
        'file_specification' => "`file_specification` TEXT NULL AFTER `whats_included`",
        'badge_text' => "`badge_text` VARCHAR(80) NULL DEFAULT 'Best Seller' AFTER `image`",
        'additional_images' => "`additional_images` TEXT NULL AFTER `image`",
        'view_count' => "`view_count` INT NOT NULL DEFAULT 0 AFTER `sales_count`",
    ];
    foreach ($columns as $col => $def) {
        $stmt = $conn->prepare(
            'SELECT COUNT(*) AS c FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
        );
        $table = 'bundles';
        $stmt->bind_param('ss', $table, $col);
        $stmt->execute();
        if ((int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0) === 0) {
            $conn->query("ALTER TABLE `bundles` ADD COLUMN $def");
            $messages[] = "Added column `bundles.$col`.";
        }
    }

    return [
        'tables' => ['bundles', 'bundle_items'],
        'messages' => $messages,
    ];
}

function seedSlug(string $name): string {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $name), '-'));
    return $slug !== '' ? $slug : bin2hex(random_bytes(4));
}

/** @return list<array<string, mixed>> */
function seedBundleCatalog(): array {
    return [
        [
            'name' => 'Ultimate UI/UX Career Bundle',
            'description' => 'Everything you need to build a professional UI/UX portfolio and land your dream job — templates, workbook, interview prep, and more.',
            'category' => 'Career Pack',
            'tags' => 'portfolio,career,interview-prep,case-study',
            'price' => 1499,
            'old_price' => 2999,
            'image' => 'img/poster.webp',
            'rating' => 4.9,
            'is_featured' => 1,
            'badge_text' => 'Best Seller',
            'whats_included' => "15+ UI Screens\nUX Workbook\nInterview Guide\nPortfolio Templates\nResume & Cover Letter Kit\nLinkedIn Banner Set",
        ],
        [
            'name' => 'Portfolio Builder Kit',
            'description' => 'Build a recruiter-ready UI/UX portfolio with templates, writing prompts, and device mockups.',
            'category' => 'Portfolio',
            'tags' => 'portfolio,case-study,figma',
            'price' => 999,
            'old_price' => 2499,
            'image' => 'img/poster1.webp',
            'rating' => 4.8,
            'is_featured' => 1,
            'badge_text' => 'Best Seller',
            'whats_included' => "Complete case study template\nPortfolio website layout\nTypography & grid system\nUX writing guide",
        ],
        [
            'name' => 'UX Interview Prep Bundle',
            'description' => 'Research prompts, whiteboard exercises, presentation templates, and interview scorecards.',
            'category' => 'Interview Prep',
            'tags' => 'interview-prep,career,whiteboard',
            'price' => 899,
            'old_price' => 1999,
            'image' => 'img/poster2.webp',
            'rating' => 4.7,
            'is_featured' => 1,
            'badge_text' => 'Best Seller',
            'whats_included' => "Interview question bank\nWhiteboard challenge templates\nPresentation deck\nSelf-assessment scorecard",
        ],
        [
            'name' => 'SaaS Launch Bundle',
            'description' => 'Landing page, SaaS dashboard, email templates, and launch checklist in one pack.',
            'category' => 'UI Kits',
            'tags' => 'ui-kits,saas,launch',
            'price' => 1299,
            'old_price' => 2999,
            'image' => 'img/poster3.webp',
            'rating' => 4.9,
            'is_featured' => 0,
            'badge_text' => 'Most Popular',
            'whats_included' => "Landing page template\nDashboard UI kit\nEmail sequence templates\nLaunch checklist & timeline",
        ],
        [
            'name' => 'Research Sprint Bundle',
            'description' => 'Scripts, analysis sheets, journey maps, and reporting templates for fast UX research.',
            'category' => 'Design System',
            'tags' => 'research,sprint,journey-map',
            'price' => 799,
            'old_price' => 1799,
            'image' => 'img/poster4.webp',
            'rating' => 4.6,
            'is_featured' => 0,
            'badge_text' => 'New',
            'whats_included' => "User interview scripts\nAnalysis spreadsheets\nJourney map templates\nResearch report format",
        ],
        [
            'name' => 'Freelance Client Kit',
            'description' => 'Contracts, proposals, pricing sheets, and delivery templates for freelance UX designers.',
            'category' => 'Freelancing',
            'tags' => 'freelancing,contract,proposal',
            'price' => 699,
            'old_price' => 1499,
            'image' => 'img/poster.webp',
            'rating' => 4.5,
            'is_featured' => 0,
            'badge_text' => 'Pro Pick',
            'whats_included' => "Client proposal template\nStatement of work\nPricing calculator\nHandoff checklist",
        ],
        [
            'name' => 'Creator Starter Bundle',
            'description' => 'Brand assets, social templates, mockups, and portfolio blocks for digital creators.',
            'category' => 'Career Pack',
            'tags' => 'creator,brand,social',
            'price' => 1099,
            'old_price' => 2599,
            'image' => 'img/poster1.webp',
            'rating' => 4.8,
            'is_featured' => 0,
            'badge_text' => 'Bundle',
            'whats_included' => "Brand identity kit\nSocial media templates\nDevice mockups\nPortfolio section blocks",
        ],
    ];
}

// ── Step 1: Create tables ───────────────────────────────────────────────────
$schema = seedEnsureBundleTables($conn);

// ── Step 2: Insert / update bundle rows ─────────────────────────────────────
$bundles = seedBundleCatalog();
$inserted = 0;
$updated = 0;
$errors = [];

$check = $conn->prepare('SELECT id FROM bundles WHERE slug = ? LIMIT 1');
$insert = $conn->prepare(
    'INSERT INTO bundles (name, slug, description, category, tags, price, old_price, image, badge_text, rating, stock, is_active, is_featured, whats_included, included_items)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 999, 1, ?, ?, ?)'
);
$update = $conn->prepare(
    'UPDATE bundles SET name=?, description=?, category=?, tags=?, price=?, old_price=?, image=?, badge_text=?, rating=?, is_active=1, is_featured=?, whats_included=?, included_items=?
     WHERE slug=?'
);

foreach ($bundles as $row) {
    $slug = seedSlug($row['name']);
    $includedLines = array_filter(array_map('trim', explode("\n", (string) $row['whats_included'])));
    $includedJson = json_encode(array_values($includedLines), JSON_UNESCAPED_UNICODE);

    $check->bind_param('s', $slug);
    $check->execute();
    $existing = $check->get_result()->fetch_assoc();

    $name = $row['name'];
    $description = $row['description'];
    $category = $row['category'];
    $tags = $row['tags'];
    $price = (float) $row['price'];
    $oldPrice = (float) $row['old_price'];
    $image = $row['image'];
    $badgeText = $row['badge_text'];
    $rating = (float) $row['rating'];
    $featured = (int) $row['is_featured'];
    $whats = $row['whats_included'];

    if ($existing) {
        $update->bind_param(
            'ssssddssdisss',
            $name,
            $description,
            $category,
            $tags,
            $price,
            $oldPrice,
            $image,
            $badgeText,
            $rating,
            $featured,
            $whats,
            $includedJson,
            $slug
        );
        if ($update->execute()) {
            $updated++;
        } else {
            $errors[] = $name . ': ' . $update->error;
        }
    } else {
        $insert->bind_param(
            'sssssddssdiss',
            $name,
            $slug,
            $description,
            $category,
            $tags,
            $price,
            $oldPrice,
            $image,
            $badgeText,
            $rating,
            $featured,
            $whats,
            $includedJson
        );
        if ($insert->execute()) {
            $inserted++;
        } else {
            $errors[] = $name . ': ' . $insert->error;
        }
    }
}

$featuredCount = (int) ($conn->query('SELECT COUNT(*) AS c FROM bundles WHERE is_active = 1 AND is_featured = 1')->fetch_assoc()['c'] ?? 0);
$totalCount = (int) ($conn->query('SELECT COUNT(*) AS c FROM bundles WHERE is_active = 1')->fetch_assoc()['c'] ?? 0);

$summary = [
    'schema' => $schema,
    'inserted' => $inserted,
    'updated' => $updated,
    'errors' => $errors,
    'active_bundles' => $totalCount,
    'best_seller_slider' => $featuredCount,
    'note' => 'is_featured = 1 → Best Seller slider on bundles.php. Toggle in Admin → Bundles.',
];

if ($isCli) {
    echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit($errors ? 1 : 0);
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Install &amp; Seed Bundles</title>
    <style>
        body { font-family: Inter, system-ui, sans-serif; background: #0a0a12; color: #eee; padding: 2rem; max-width: 720px; margin: 0 auto; line-height: 1.5; }
        h1 { font-size: 1.35rem; }
        h2 { font-size: 1rem; margin-top: 1.5rem; color: #a78bfa; }
        .ok { color: #86efac; }
        .err { color: #fca5a5; }
        ul { padding-left: 1.2rem; }
        a { color: #a78bfa; }
        code, pre { background: #1a1a28; padding: 2px 6px; border-radius: 4px; font-size: 0.85rem; }
        pre { padding: 12px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 0.9rem; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid rgba(255,255,255,0.08); }
    </style>
</head>
<body>
    <h1>Bundles database setup complete</h1>

    <h2>Tables created</h2>
    <ul>
        <?php foreach ($schema['messages'] as $msg): ?>
            <li class="ok"><?php echo htmlspecialchars($msg); ?></li>
        <?php endforeach; ?>
    </ul>

    <h2>Sample data</h2>
    <p class="ok">Inserted: <strong><?php echo (int) $inserted; ?></strong> · Updated: <strong><?php echo (int) $updated; ?></strong></p>
    <p>Active bundles: <strong><?php echo $totalCount; ?></strong> · Best Seller slider: <strong><?php echo $featuredCount; ?></strong></p>

    <?php if ($errors): ?>
        <ul class="err"><?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul>
    <?php endif; ?>

    <h2>`bundles` table columns</h2>
    <table>
        <tr><th>Column</th><th>Purpose</th></tr>
        <tr><td><code>is_featured</code></td><td>1 = show in Best Seller horizontal slider</td></tr>
        <tr><td><code>is_active</code></td><td>1 = visible on shop</td></tr>
        <tr><td><code>badge_text</code></td><td>e.g. Best Seller, Most Popular</td></tr>
        <tr><td><code>whats_included</code></td><td>Bullet list (text)</td></tr>
        <tr><td><code>included_items</code></td><td>Same list as JSON (for APIs)</td></tr>
        <tr><td><code>price</code> / <code>old_price</code></td><td>Pricing on cards</td></tr>
    </table>

    <p style="margin-top:1.5rem;"><?php echo htmlspecialchars($summary['note']); ?></p>
    <p><a href="bundles.php">View bundles page</a> · <a href="admin/admin-dashboard.php">Admin panel</a></p>
</body>
</html>
