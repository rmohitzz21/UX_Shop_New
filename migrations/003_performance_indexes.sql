-- ─────────────────────────────────────────────────────────────────────────────
-- 003_performance_indexes.sql
-- Purpose: add covering indexes for the hot-path queries (cart, products,
--          orders, downloads). Idempotent — each index uses a unique name and
--          will fail-soft via the conditional procedure below if it exists.
--
-- Run on production once. Safe to re-run.
-- ─────────────────────────────────────────────────────────────────────────────

DELIMITER //

DROP PROCEDURE IF EXISTS _add_index_if_missing //
CREATE PROCEDURE _add_index_if_missing(
    IN tbl   VARCHAR(64),
    IN idx   VARCHAR(64),
    IN cols  VARCHAR(255)
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = tbl
          AND index_name = idx
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', tbl, '` ADD INDEX `', idx, '` (', cols, ')');
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END IF;
END //

DELIMITER ;

-- ── cart ─────────────────────────────────────────────────────────────────────
CALL _add_index_if_missing('cart', 'idx_cart_user',            '`user_id`');
CALL _add_index_if_missing('cart', 'idx_cart_user_type_prod',  '`user_id`, `item_type`, `product_id`');
CALL _add_index_if_missing('cart', 'idx_cart_user_type_bund',  '`user_id`, `item_type`, `bundle_id`');

-- ── products ─────────────────────────────────────────────────────────────────
CALL _add_index_if_missing('products', 'idx_products_active',           '`is_active`');
CALL _add_index_if_missing('products', 'idx_products_type_active',      '`available_type`, `is_active`');
CALL _add_index_if_missing('products', 'idx_products_featured_active',  '`is_featured`, `is_active`');

-- ── bundles ──────────────────────────────────────────────────────────────────
CALL _add_index_if_missing('bundles',  'idx_bundles_active',            '`is_active`');

-- ── freebies ─────────────────────────────────────────────────────────────────
CALL _add_index_if_missing('freebies', 'idx_freebies_active',           '`is_active`');

-- ── orders ───────────────────────────────────────────────────────────────────
CALL _add_index_if_missing('orders',   'idx_orders_user',               '`user_id`');
CALL _add_index_if_missing('orders',   'idx_orders_user_created',       '`user_id`, `created_at`');
CALL _add_index_if_missing('orders',   'idx_orders_status',             '`status`');
CALL _add_index_if_missing('orders',   'idx_orders_payment_status',     '`payment_status`');
CALL _add_index_if_missing('orders',   'idx_orders_razorpay_order',     '`razorpay_order_id`');

-- ── order_items (critical for the "free 1-per-account" lookup) ───────────────
CALL _add_index_if_missing('order_items', 'idx_oi_order',               '`order_id`');
CALL _add_index_if_missing('order_items', 'idx_oi_product_price',       '`product_id`, `price`');
CALL _add_index_if_missing('order_items', 'idx_oi_bundle',              '`bundle_id`');

-- ── digital_downloads ────────────────────────────────────────────────────────
CALL _add_index_if_missing('digital_downloads', 'idx_dd_order',         '`order_id`');
CALL _add_index_if_missing('digital_downloads', 'idx_dd_token',         '`token`');

-- ── digital_resources ────────────────────────────────────────────────────────
CALL _add_index_if_missing('digital_resources', 'idx_dr_product_active','`product_id`, `is_active`');
CALL _add_index_if_missing('digital_resources', 'idx_dr_bundle_active', '`bundle_id`,  `is_active`');
CALL _add_index_if_missing('digital_resources', 'idx_dr_freebie_active','`freebie_id`, `is_active`');

-- ── inventory_reservations ───────────────────────────────────────────────────
CALL _add_index_if_missing('inventory_reservations', 'idx_ir_order',           '`order_id`');
CALL _add_index_if_missing('inventory_reservations', 'idx_ir_expires_released','`expires_at`, `released_at`');

DROP PROCEDURE IF EXISTS _add_index_if_missing;
