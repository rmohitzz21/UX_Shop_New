<?php
/**
 * Idempotent schema fix for remote DB missing migrations 004/008.
 * Run: php migrations/011_admin_dashboard_remote_fix.php
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';

function hasColumn(mysqli $conn, string $table, string $col): bool {
    $stmt = $conn->prepare('SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1');
    $stmt->bind_param('ss', $table, $col);
    $stmt->execute();
    return (bool) $stmt->get_result()->fetch_row();
}

function hasTable(mysqli $conn, string $table): bool {
    $stmt = $conn->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1');
    $stmt->bind_param('s', $table);
    $stmt->execute();
    return (bool) $stmt->get_result()->fetch_row();
}

function runSql(mysqli $conn, string $sql, string $label): void {
    if (!$conn->query($sql)) {
        throw new RuntimeException("$label failed: " . $conn->error);
    }
    echo "OK: $label\n";
}

$steps = [];

try {
    if (!hasColumn($conn, 'products', 'is_free')) {
        runSql($conn, "ALTER TABLE products ADD COLUMN is_free TINYINT(1) NOT NULL DEFAULT 0 AFTER is_featured", 'products.is_free');
        runSql($conn, 'ALTER TABLE products ADD INDEX idx_products_is_free (is_free, is_active)', 'products.is_free index');
    } else {
        echo "SKIP: products.is_free exists\n";
    }

    if (!hasColumn($conn, 'orders', 'payment_status')) {
        runSql($conn, "ALTER TABLE orders ADD COLUMN payment_status ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending' AFTER status", 'orders.payment_status');
        runSql($conn, 'ALTER TABLE orders ADD COLUMN paid_at DATETIME NULL AFTER payment_status', 'orders.paid_at');
        runSql($conn, "UPDATE orders SET payment_status = 'paid', paid_at = COALESCE(status_updated_at, created_at) WHERE status IN ('paid','processing','shipped','delivered')", 'orders.payment_status backfill paid');
        runSql($conn, "UPDATE orders SET payment_status = 'failed' WHERE status = 'failed'", 'orders.payment_status backfill failed');
        runSql($conn, 'ALTER TABLE orders ADD INDEX idx_orders_payment_status (payment_status)', 'orders.payment_status index');
    } else {
        echo "SKIP: orders.payment_status exists\n";
    }

    if (!hasTable($conn, 'digital_resources')) {
        runSql($conn, "CREATE TABLE digital_resources (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            product_id INT NULL,
            bundle_id INT NULL,
            title VARCHAR(255) NOT NULL,
            resource_type VARCHAR(40) NOT NULL,
            delivery_mode VARCHAR(30) NOT NULL,
            storage_provider VARCHAR(30) NOT NULL DEFAULT 'local',
            storage_key VARCHAR(700) NULL,
            external_url TEXT NULL,
            instructions TEXT NULL,
            download_limit TINYINT UNSIGNED NOT NULL DEFAULT 5,
            expiry_days SMALLINT UNSIGNED NOT NULL DEFAULT 30,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_dr_product (product_id, is_active, sort_order),
            INDEX idx_dr_bundle (bundle_id, is_active, sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", 'digital_resources table');
    } else {
        echo "SKIP: digital_resources exists\n";
    }

    if (!hasTable($conn, 'digital_downloads')) {
        runSql($conn, "CREATE TABLE digital_downloads (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id INT NOT NULL,
            order_item_id INT NOT NULL,
            resource_id INT NOT NULL DEFAULT 0,
            user_id INT NOT NULL,
            product_id INT DEFAULT NULL,
            bundle_id INT DEFAULT NULL,
            token CHAR(64) NOT NULL,
            download_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
            download_limit TINYINT UNSIGNED NOT NULL DEFAULT 5,
            expires_at DATETIME NOT NULL,
            file_path VARCHAR(500) NOT NULL DEFAULT '',
            item_name VARCHAR(255) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_token (token),
            UNIQUE KEY uq_order_item_resource (order_item_id, resource_id),
            KEY idx_order_id (order_id),
            KEY idx_user_id (user_id),
            KEY idx_dd_resource (resource_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", 'digital_downloads table');
    } else {
        echo "SKIP: digital_downloads exists\n";
    }

    if (!hasColumn($conn, 'bundle_items', 'bundle_id')) {
        $count = 0;
        $r = $conn->query('SELECT COUNT(*) c FROM bundle_items');
        if ($r) $count = (int) ($r->fetch_assoc()['c'] ?? 0);
        if ($count > 0) {
            throw new RuntimeException('bundle_items has legacy schema and non-zero rows; manual migration required.');
        }
        runSql($conn, 'DROP TABLE bundle_items', 'drop legacy bundle_items');
        runSql($conn, "CREATE TABLE bundle_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            bundle_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_bundle_product (bundle_id, product_id),
            INDEX idx_bundle_items_product (product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'bundle_items table');
    } else {
        echo "SKIP: bundle_items.bundle_id exists\n";
    }

    if (!hasTable($conn, 'contact_messages')) {
        runSql($conn, "CREATE TABLE contact_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(160) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(40) NULL,
            subject VARCHAR(180) NULL,
            message TEXT NOT NULL,
            ip VARCHAR(64) NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            archived TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_contact_created (created_at),
            INDEX idx_contact_read (is_read)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'contact_messages table');
    } else {
        if (!hasColumn($conn, 'contact_messages', 'is_read')) {
            runSql($conn, 'ALTER TABLE contact_messages ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0', 'contact_messages.is_read');
        } else {
            echo "SKIP: contact_messages.is_read exists\n";
        }
        if (!hasColumn($conn, 'contact_messages', 'archived')) {
            runSql($conn, 'ALTER TABLE contact_messages ADD COLUMN archived TINYINT(1) NOT NULL DEFAULT 0', 'contact_messages.archived');
        } else {
            echo "SKIP: contact_messages.archived exists\n";
        }
    }

    if (!hasTable($conn, 'shop_settings')) {
        runSql($conn, "CREATE TABLE shop_settings (
            setting_key VARCHAR(64) NOT NULL PRIMARY KEY,
            setting_value TEXT NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", 'shop_settings table');
    } else {
        echo "SKIP: shop_settings exists\n";
    }

    echo "\nMigration 011 completed successfully.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Migration failed: " . $e->getMessage() . "\n");
    exit(1);
}
