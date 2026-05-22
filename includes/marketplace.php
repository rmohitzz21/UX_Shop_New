<?php

function tableExists(mysqli $conn, string $table): bool {
    $stmt = $conn->prepare('SELECT COUNT(*) AS c FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
    $stmt->bind_param('s', $table);
    $stmt->execute();
    return ((int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0)) > 0;
}

function columnExists(mysqli $conn, string $table, string $column): bool {
    $stmt = $conn->prepare('SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    return ((int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0)) > 0;
}

function addColumnIfMissing(mysqli $conn, string $table, string $column, string $definition): void {
    if (!columnExists($conn, $table, $column)) {
        $conn->query("ALTER TABLE `$table` ADD COLUMN $definition");
    }
}

function ensureAutoIncrementPrimaryKey(mysqli $conn, string $table): void {
    if (!tableExists($conn, $table) || !columnExists($conn, $table, 'id')) {
        return;
    }

    $keyRes = $conn->query("SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY'");
    if ($keyRes && $keyRes->num_rows > 0) {
        $conn->query("ALTER TABLE `$table` MODIFY `id` INT NOT NULL AUTO_INCREMENT");
        return;
    }

    $maxId = (int) ($conn->query("SELECT COALESCE(MAX(id), 0) AS max_id FROM `$table` WHERE id > 0")->fetch_assoc()['max_id'] ?? 0);
    $conn->query("SET @uxp_next_id := {$maxId}");
    $orderBy = columnExists($conn, $table, 'created_at') ? 'created_at ASC, id ASC' : 'id ASC';
    $conn->query("UPDATE `$table` SET id = (@uxp_next_id := @uxp_next_id + 1) WHERE id <= 0 ORDER BY {$orderBy}");
    $conn->query("ALTER TABLE `$table` MODIFY `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY");
}

function marketplaceEnsureSchema(mysqli $conn): void {
    static $done = false;
    if ($done) return;
    $done = true;

    $conn->query("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL UNIQUE,
        slug VARCHAR(140) NOT NULL UNIQUE,
        description TEXT NULL,
        icon VARCHAR(255) NULL,
        accent VARCHAR(40) DEFAULT 'purple',
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT NULL,
        category VARCHAR(100) NULL,
        price DECIMAL(10,2) NOT NULL DEFAULT 0,
        old_price DECIMAL(10,2) NULL,
        image VARCHAR(255) NULL,
        stock INT NOT NULL DEFAULT 0,
        rating DECIMAL(3,2) DEFAULT 4.50,
        available_type VARCHAR(20) DEFAULT 'digital',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    marketplaceEnsureProductPrimaryKey($conn);

    addColumnIfMissing($conn, 'products', 'slug', "`slug` VARCHAR(255) NULL AFTER `name`");
    addColumnIfMissing($conn, 'products', 'tags', "`tags` VARCHAR(500) NULL AFTER `category`");
    addColumnIfMissing($conn, 'products', 'is_active', "`is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `available_type`");
    addColumnIfMissing($conn, 'products', 'is_featured', "`is_featured` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_active`");
    addColumnIfMissing($conn, 'products', 'sales_count', "`sales_count` INT NOT NULL DEFAULT 0 AFTER `is_featured`");
    addColumnIfMissing($conn, 'products', 'view_count', "`view_count` INT NOT NULL DEFAULT 0 AFTER `sales_count`");
    addColumnIfMissing($conn, 'products', 'commercial_price', "`commercial_price` DECIMAL(10,2) NULL AFTER `old_price`");
    addColumnIfMissing($conn, 'products', 'additional_images', "`additional_images` TEXT NULL AFTER `image`");
    addColumnIfMissing($conn, 'products', 'whats_included', "`whats_included` TEXT NULL AFTER `description`");
    addColumnIfMissing($conn, 'products', 'file_specification', "`file_specification` TEXT NULL AFTER `whats_included`");
    addColumnIfMissing($conn, 'products', 'related_products', "`related_products` VARCHAR(255) NULL AFTER `tags`");
    addColumnIfMissing($conn, 'products', 'sku', "`sku` VARCHAR(80) NULL AFTER `slug`");

    $conn->query("CREATE TABLE IF NOT EXISTS bundles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NULL,
        description TEXT NULL,
        category VARCHAR(100) DEFAULT 'Bundles',
        tags VARCHAR(500) NULL,
        price DECIMAL(10,2) NOT NULL DEFAULT 0,
        old_price DECIMAL(10,2) NULL,
        image VARCHAR(255) NULL,
        rating DECIMAL(3,2) DEFAULT 4.70,
        stock INT NOT NULL DEFAULT 999,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        is_featured TINYINT(1) NOT NULL DEFAULT 0,
        sales_count INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_bundle_active_featured (is_active, is_featured),
        INDEX idx_bundle_category (category)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    addColumnIfMissing($conn, 'bundles', 'included_items', "`included_items` TEXT NULL AFTER `tags`");

    $conn->query("CREATE TABLE IF NOT EXISTS bundle_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        bundle_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_bundle_product (bundle_id, product_id),
        INDEX idx_bundle_items_product (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS wishlist (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NULL,
        bundle_id INT NULL,
        item_type ENUM('product','bundle') NOT NULL DEFAULT 'product',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_wishlist_item (user_id, item_type, product_id, bundle_id),
        INDEX idx_wishlist_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NULL,
        bundle_id INT NULL,
        rating TINYINT NOT NULL,
        comment TEXT NULL,
        is_approved TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_reviews_product (product_id),
        INDEX idx_reviews_bundle (bundle_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS featured_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_type ENUM('product','bundle') NOT NULL,
        item_id INT NOT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_featured_item (item_type, item_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS addresses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        address_line1 VARCHAR(255) NOT NULL,
        address_line2 VARCHAR(255) NULL,
        city VARCHAR(120) NOT NULL,
        state VARCHAR(120) NOT NULL,
        zip_code VARCHAR(30) NOT NULL,
        country VARCHAR(80) NOT NULL DEFAULT 'IN',
        phone VARCHAR(40) NOT NULL,
        is_default TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_addresses_user (user_id, is_default)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS contact_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(160) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(40) NULL,
        subject VARCHAR(180) NULL,
        message TEXT NOT NULL,
        ip VARCHAR(64) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_contact_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS password_reset_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token_hash CHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        used_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_password_reset_token (token_hash),
        INDEX idx_password_reset_user (user_id, used_at, expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS inventory_movements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_type ENUM('product','bundle') NOT NULL DEFAULT 'product',
        item_id INT NOT NULL,
        admin_id INT NULL,
        change_qty INT NOT NULL,
        stock_before INT NOT NULL DEFAULT 0,
        stock_after INT NOT NULL DEFAULT 0,
        reason VARCHAR(160) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_inventory_item (item_type, item_id, created_at),
        INDEX idx_inventory_admin (admin_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        name VARCHAR(160) DEFAULT 'Admin',
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    if (tableExists($conn, 'orders')) {
        ensureAutoIncrementPrimaryKey($conn, 'orders');
        addColumnIfMissing($conn, 'orders', 'status_updated_at', "`status_updated_at` TIMESTAMP NULL AFTER `status`");
        addColumnIfMissing($conn, 'orders', 'customer_note', "`customer_note` TEXT NULL AFTER `shipping_address`");
    }
    if (tableExists($conn, 'order_items')) {
        ensureAutoIncrementPrimaryKey($conn, 'order_items');
        $conn->query("ALTER TABLE `order_items` MODIFY `product_id` INT NULL");
        addColumnIfMissing($conn, 'order_items', 'product_name', "`product_name` VARCHAR(255) NULL AFTER `size`");
        addColumnIfMissing($conn, 'order_items', 'product_image', "`product_image` VARCHAR(255) NULL AFTER `product_name`");
        addColumnIfMissing($conn, 'order_items', 'item_type', "`item_type` ENUM('product','bundle') NOT NULL DEFAULT 'product' AFTER `product_image`");
        addColumnIfMissing($conn, 'order_items', 'bundle_id', "`bundle_id` INT NULL AFTER `product_id`");
    }
    ensureAutoIncrementPrimaryKey($conn, 'users');
    ensureAutoIncrementPrimaryKey($conn, 'cart');
    ensureAutoIncrementPrimaryKey($conn, 'user_tokens');

    $categories = [
        ['Templates', 'templates', 'Get hired with ready-to-use templates', 'green', 1],
        ['UI Kits', 'ui-kits', 'Build stunning interfaces faster', 'pink', 2],
        ['Mockups', 'mockups', 'Present your work professionally', 'purple', 3],
        ['UX Resources', 'ux-resources', 'Master UX research and strategy', 'orange', 4],
        ['Workbooks', 'workbooks', 'Practice systems for UX learners', 'green', 5],
        ['Bundles', 'bundles', 'Curated packs for faster outcomes', 'purple', 6],
    ];
    $stmt = $conn->prepare('INSERT IGNORE INTO categories (name, slug, description, accent, sort_order) VALUES (?, ?, ?, ?, ?)');
    foreach ($categories as $cat) {
        $stmt->bind_param('ssssi', $cat[0], $cat[1], $cat[2], $cat[3], $cat[4]);
        $stmt->execute();
    }

    $count = (int) ($conn->query('SELECT COUNT(*) AS c FROM products WHERE is_active = 1')->fetch_assoc()['c'] ?? 0);
    if ($count < 6) {
        marketplaceSeedProducts($conn);
    } else {
        $conn->query("UPDATE products SET is_active = 1 WHERE is_active IS NULL");
    }

    $bundleCount = (int) ($conn->query('SELECT COUNT(*) AS c FROM bundles')->fetch_assoc()['c'] ?? 0);
    if ($bundleCount === 0) {
        marketplaceSeedBundles($conn);
    }
}

function marketplaceEnsureProductPrimaryKey(mysqli $conn): void {
    $keyRes = $conn->query("SHOW KEYS FROM products WHERE Key_name = 'PRIMARY'");
    if ($keyRes && $keyRes->num_rows > 0) {
        return;
    }

    $maxId = (int) ($conn->query('SELECT COALESCE(MAX(id), 0) AS max_id FROM products WHERE id > 0')->fetch_assoc()['max_id'] ?? 0);
    $zeroRes = $conn->query('SELECT name FROM products WHERE id = 0 ORDER BY created_at ASC, name ASC');
    while ($zeroRes && $row = $zeroRes->fetch_assoc()) {
        $maxId++;
        $stmt = $conn->prepare('UPDATE products SET id = ? WHERE id = 0 AND name = ? LIMIT 1');
        $stmt->bind_param('is', $maxId, $row['name']);
        $stmt->execute();
    }

    $conn->query('ALTER TABLE products MODIFY id INT NOT NULL AUTO_INCREMENT PRIMARY KEY');
}

function marketplaceSeedProducts(mysqli $conn): void {
    $rows = [
        ['UXPacific UI Template', 'Premium Figma landing page and dashboard templates with responsive auto layout.', 'Templates', 'figma,landing,dashboard,template', 399, 899, 'img/poster1.webp', 999, 4.8, 'digital', 1],
        ['UX Research Workbook', 'A practical workbook for UX learners and product designers.', 'Workbooks', 'ux,research,workbook,pdf', 499, 999, 'img/poster2.webp', 999, 4.7, 'digital', 1],
        ['Mobile App Mockup Pack', 'High-resolution device mockups for app presentations and portfolios.', 'Mockups', 'mockup,mobile,presentation', 299, 699, 'img/poster3.webp', 999, 4.6, 'digital', 1],
        ['Design Career Badge Pack', 'Clean badge assets for communities, profiles, and achievements.', 'UX Resources', 'badges,community,assets', 199, 499, 'img/poster.webp', 999, 4.5, 'digital', 0],
        ['SaaS UI Kit Essentials', 'A polished UI kit for SaaS dashboards, forms, charts, and settings flows.', 'UI Kits', 'ui-kit,saas,figma,components', 599, 1299, 'img/poster4.webp', 999, 4.9, 'digital', 1],
        ['Portfolio Layout System', 'Reusable portfolio sections, grids, and case study blocks for UX designers.', 'Templates', 'portfolio,case-study,layout', 349, 799, 'img/shopnew.png', 999, 4.7, 'digital', 0],
    ];
    $exists = $conn->prepare('SELECT id FROM products WHERE name = ? LIMIT 1');
    $stmt = $conn->prepare('INSERT INTO products (name, slug, description, whats_included, file_specification, category, tags, price, old_price, commercial_price, image, additional_images, stock, rating, available_type, is_active, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)');
    foreach ($rows as $row) {
        $exists->bind_param('s', $row[0]);
        $exists->execute();
        if ($exists->get_result()->fetch_assoc()) {
            continue;
        }
        $slug = slugify($row[0]);
        $included = "- Editable source files\n- Usage guide\n- Bonus checklist";
        $specs = "- Figma / PDF files\n- 1440px layout references\n- Personal and commercial license options";
        $commercial = round((float) $row[4] * 1.4, 2);
        $additional = json_encode([$row[6], 'img/poster.webp', 'img/poster2.webp']);
        $stmt->bind_param('sssssssdddssidsi', $row[0], $slug, $row[1], $included, $specs, $row[2], $row[3], $row[4], $row[5], $commercial, $row[6], $additional, $row[7], $row[8], $row[9], $row[10]);
        $stmt->execute();
    }
}

function marketplaceSeedBundles(mysqli $conn): void {
    $rows = [
        ['Portfolio Builder Kit', 'Build a recruiter-ready UI/UX portfolio with templates, writing prompts, and mockups.', 'Bundles', 'portfolio,career,case-study', 999, 2499, 'img/poster.webp', 4.8, 1],
        ['UX Interview Prep Bundle', 'Research prompts, whiteboard exercises, presentation templates, and interview scorecards.', 'Bundles', 'career,interview,ux', 899, 1999, 'img/poster1.webp', 4.7, 1],
        ['SaaS Launch Bundle', 'Landing page, SaaS dashboard, email templates, and launch checklist in one pack.', 'Bundles', 'saas,launch,template', 1299, 2999, 'img/poster2.webp', 4.9, 1],
        ['Research Sprint Bundle', 'Scripts, analysis sheets, journey maps, and reporting templates for fast UX research.', 'Bundles', 'research,sprint,journey-map', 799, 1799, 'img/poster3.webp', 4.6, 1],
        ['Creator Starter Bundle', 'Brand assets, social templates, mockups, and portfolio blocks for digital creators.', 'Bundles', 'creator,brand,social', 1099, 2599, 'img/poster4.webp', 4.8, 0],
    ];
    $stmt = $conn->prepare('INSERT INTO bundles (name, slug, description, category, tags, price, old_price, image, rating, is_active, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)');
    foreach ($rows as $row) {
        $slug = slugify($row[0]);
        $stmt->bind_param('sssssddsdi', $row[0], $slug, $row[1], $row[2], $row[3], $row[4], $row[5], $row[6], $row[7], $row[8]);
        $stmt->execute();
    }
}

function slugify(string $value): string {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $value), '-'));
    return $slug !== '' ? $slug : bin2hex(random_bytes(4));
}

function e($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function money($value): string {
    return '₹' . number_format((float) $value, 0);
}

function marketplaceImage(?string $image): string {
    $image = trim((string) $image);
    return $image !== '' ? $image : 'img/poster.webp';
}

function marketplaceProductCard(array $item, string $type = 'product'): string {
    $id = (int) $item['id'];
    $name = e($item['name'] ?? 'Untitled');
    $category = e($item['category'] ?? ($type === 'bundle' ? 'Bundles' : 'Digital Product'));
    $description = e($item['description'] ?? '');
    $image = e(marketplaceImage($item['image'] ?? ''));
    $price = money($item['price'] ?? 0);
    $old = !empty($item['old_price']) ? '<span class="old">' . money($item['old_price']) . '</span>' : '';
    $rating = number_format((float) ($item['rating'] ?? 4.5), 1);
    $stock = (int) ($item['stock'] ?? 999);
    $stockLabel = $stock > 0 ? 'In stock' : 'Out of stock';
    $detailsUrl = $type === 'bundle' ? 'bundles.php' : "product.php?id={$id}";
    return <<<HTML
<article class="prod-card marketplace-card" data-type="{$type}" data-id="{$id}" data-category="{$category}">
  <button type="button" class="wishlist-float" aria-label="Add to wishlist" onclick="toggleMarketplaceWishlist('{$type}', {$id})">♡</button>
  <div class="prod-img">
    <img src="{$image}" alt="{$name}" loading="lazy" onerror="this.src='img/poster.webp'" />
    <div class="prod-badge"><img src="img/ss/Vector.png" alt="" /></div>
  </div>
  <div class="prod-info">
    <div class="prod-header">
      <h3>{$name}</h3>
      <div class="rating">★ {$rating}</div>
    </div>
    <p class="prod-desc">{$description}</p>
    <p class="prod-specs">{$category} · {$stockLabel}</p>
    <div class="prod-price"><span class="current">{$price}</span>{$old}</div>
    <div class="prod-actions">
      <button class="btn btn-primary btn-sm" type="button" onclick="addMarketplaceItemToCart('{$type}', {$id})">Add to Cart</button>
      <a class="btn btn-outline btn-sm" href="{$detailsUrl}">View Details</a>
    </div>
  </div>
</article>
HTML;
}
