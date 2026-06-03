-- =============================================================================
-- Migration 004: Unified Cart, Digital Resources, Payment State & Fulfillment
-- Target:        MariaDB 10.4.32 (XAMPP local; same engine in production)
-- Prerequisites: Migrations 001 and 002 applied.
--               Migration 003 may or may not be applied — guards are included.
-- Run:           mysql -u root uxmerchandise < migrations/004_unified_cart_digital_resources_and_fulfillment.sql
-- =============================================================================
-- Implements:
--   1.1  cart       — typed item_type + bundle_id + selected_format; unique index
--   1.2  order_items — selected_format snapshot
--   1.3  orders     — payment_status, paid_at, confirmation_email_sent_at
--   1.4  digital_resources — normalized multi-resource table (new)
--   1.5  digital_downloads — resource_id; upgraded unique key
--   1.6  inventory_reservations — stock reservation at order-create time (new)
-- =============================================================================
-- ROLLBACK NOTES (keep backup before running):


--   cart:                 Remove item_type, bundle_id, selected_format, _item_key
--                         MODIFY product_id INT NOT NULL; restore cart_ibfk_2 FK.
--   order_items:          DROP COLUMN selected_format
--   orders:               DROP COLUMN payment_status, paid_at, confirmation_email_sent_at
--   digital_resources:    DROP TABLE digital_resources
--   digital_downloads:    DROP COLUMN resource_id; restore uq_order_item unique key
--   inventory_reservations: DROP TABLE inventory_reservations
-- =============================================================================

-- =============================================================================
-- PREREQUISITE GUARD: Migration 003 columns (digital_file_path on products/bundles)
-- These are a no-op if migration 003 already ran.
-- IMPORTANT: bundles has no available_type column; AFTER clause is intentionally
-- omitted to let MariaDB append to end of table (avoids "Unknown column" error).
-- =============================================================================

ALTER TABLE `products`
    ADD COLUMN IF NOT EXISTS `digital_file_path`    VARCHAR(500)      DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `download_limit`        TINYINT UNSIGNED  NOT NULL DEFAULT 5,
    ADD COLUMN IF NOT EXISTS `download_expiry_days`  SMALLINT UNSIGNED NOT NULL DEFAULT 30;

ALTER TABLE `bundles`
    ADD COLUMN IF NOT EXISTS `digital_file_path`    VARCHAR(500)      DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `download_limit`        TINYINT UNSIGNED  NOT NULL DEFAULT 5,
    ADD COLUMN IF NOT EXISTS `download_expiry_days`  SMALLINT UNSIGNED NOT NULL DEFAULT 30;

-- =============================================================================
-- PREREQUISITE GUARD: Products schema normalization
-- Formalizes two changes that were applied via admin code outside the migration
-- system (confirmed present in db.sql dump dated 2026-06-02):
--
--   1. available_type: VARCHAR(20) -> ENUM('physical','digital','both') NOT NULL
--      Safe: all existing values ('physical','digital','both') are valid members.
--      Idempotent: MODIFY on an already-ENUM column with the same definition is a no-op.
--
--   2. product_type ENUM('physical','digital'): added by api/admin/product/save.php.
--      Default 'physical'; existing rows that are digital have product_type='physical'
--      (the column is currently unused for fulfillment decisions -- available_type
--      is the authoritative field). Phase 2 may consolidate or remove product_type.
-- =============================================================================

ALTER TABLE `products`
    MODIFY COLUMN `available_type` ENUM('physical','digital','both') NOT NULL DEFAULT 'physical',
    ADD COLUMN IF NOT EXISTS `product_type` ENUM('physical','digital') NOT NULL DEFAULT 'physical';


-- =============================================================================
-- SECTION 1.1 — CART TABLE NORMALIZATION
-- Replace proxy-product bundle pattern with a real typed cart model.
--
-- FK ordering constraint (MariaDB 10.4):
--   A FOREIGN KEY cannot be ADDED to a column that is already a base column of
--   a STORED generated column. To keep this migration idempotent:
--     - product_id FK (cart_ibfk_2): kept in place; MODIFY to nullable without
--       dropping the FK (MariaDB allows nullability change on FK base columns).
--     - bundle_id FK (cart_ibfk_3): added with IF NOT EXISTS BEFORE _item_key
--       is created so it succeeds on both first run and re-run.
--     - _item_key generated column: added last, after all FK work is done.
-- =============================================================================

-- ── 1.1a: Make product_id nullable.
--          The existing FK cart_ibfk_2 is preserved; MariaDB enforces it only for
--          non-NULL values. Dropping and re-adding the FK is intentionally avoided
--          because after _item_key exists, re-adding the FK would be blocked.
ALTER TABLE `cart`
    MODIFY COLUMN `product_id` INT NULL;

-- ── 1.1b: Add item_type, bundle_id, selected_format.
ALTER TABLE `cart`
    ADD COLUMN IF NOT EXISTS `item_type`       ENUM('product','bundle')   NOT NULL DEFAULT 'product'  AFTER `user_id`,
    ADD COLUMN IF NOT EXISTS `bundle_id`       INT                        NULL                         AFTER `item_type`,
    ADD COLUMN IF NOT EXISTS `selected_format` ENUM('digital','physical') NOT NULL DEFAULT 'physical' AFTER `available_type`;

-- ── 1.1c: Backfill selected_format from available_type for all existing rows.
--          'physical' and 'both' map to 'physical' (safe default; 'both' means the
--          customer had not committed to a format, physical is the conservative choice).
UPDATE `cart`
SET `selected_format` = CASE
        WHEN `available_type` = 'digital' THEN 'digital'
        ELSE 'physical'
    END;

-- ── 1.1c-cleanup: Purge orphan cart rows where product_id no longer exists in products.
--          These arise when a product is deleted while FK_CHECKS was disabled (e.g., during
--          a prior failed migration run). They would cause FK constraint violation 1452 when
--          cart_ibfk_2 is first validated. Safe to delete: the referenced product is gone.
DELETE c FROM `cart` c
LEFT JOIN `products` p ON p.id = c.product_id AND c.item_type = 'product'
WHERE c.item_type = 'product'
  AND c.product_id IS NOT NULL
  AND p.id IS NULL;

-- ── 1.1d: Add FK on bundle_id (MUST be done BEFORE _item_key is added).
--          MariaDB 10.4 does not support ADD CONSTRAINT IF NOT EXISTS for FOREIGN KEY
--          (only CHECK constraints support that syntax). A temporary stored procedure
--          guards against duplicate-FK errors on re-run.
DROP PROCEDURE IF EXISTS `_mig004_bundle_fk`;
DELIMITER //
CREATE PROCEDURE `_mig004_bundle_fk`()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA = DATABASE()
          AND TABLE_NAME        = 'cart'
          AND CONSTRAINT_NAME   = 'cart_ibfk_3'
          AND CONSTRAINT_TYPE   = 'FOREIGN KEY'
    ) THEN
        ALTER TABLE `cart`
            ADD CONSTRAINT `cart_ibfk_3` FOREIGN KEY (`bundle_id`)
                REFERENCES `bundles` (`id`) ON DELETE CASCADE;
    END IF;
END //
DELIMITER ;
CALL `_mig004_bundle_fk`();
DROP PROCEDURE IF EXISTS `_mig004_bundle_fk`;

-- ── 1.1e: Add a generated stored column to enable a unique index across nullable FKs.
--          Added after FK work is complete to avoid the MariaDB base-column restriction.
--          COALESCE maps NULL product_id/bundle_id to 0 for a stable key string.
--          size NULL maps to '' so NULL and '' occupy the same slot.
ALTER TABLE `cart`
    ADD COLUMN IF NOT EXISTS `_item_key` VARCHAR(120) GENERATED ALWAYS AS (
        CONCAT(
            `item_type`, ':',
            COALESCE(`product_id`, 0), ':',
            COALESCE(`bundle_id`, 0), ':',
            `selected_format`, ':',
            COALESCE(`size`, '')
        )
    ) STORED;

-- ── 1.1f: One cart row per user/item/format/size combination.
--          Enforced in the DB; PHP also checks before insert/update.
ALTER TABLE `cart`
    ADD UNIQUE KEY IF NOT EXISTS `uq_cart_user_item` (`user_id`, `_item_key`);

-- ── 1.1g: Performance indexes.
ALTER TABLE `cart`
    ADD INDEX IF NOT EXISTS `idx_cart_user_type` (`user_id`, `item_type`),
    ADD INDEX IF NOT EXISTS `idx_cart_bundle_id` (`bundle_id`);

-- ── 1.1h: XOR integrity: exactly one of product_id/bundle_id per item_type.
--          Enforced by the CHECK engine in MariaDB 10.2.1+ (active in 10.4.32).
--          All existing rows satisfy this: product_id NOT NULL, bundle_id NULL, item_type='product'.
ALTER TABLE `cart`
    ADD CONSTRAINT IF NOT EXISTS `chk_cart_item_xor` CHECK (
        (`item_type` = 'product' AND `product_id` IS NOT NULL AND `bundle_id` IS NULL)
        OR
        (`item_type` = 'bundle'  AND `bundle_id`  IS NOT NULL AND `product_id` IS NULL)
    );

-- NOTE: Existing proxy-bundle rows remain as item_type='product' pointing to proxy product rows.
-- They satisfy the CHECK. Phase 2 PHP changes stop proxy creation and clear proxy rows.


-- =============================================================================
-- SECTION 1.2 — ORDER ITEMS: SNAPSHOT SELECTED FORMAT
-- Records which format the customer actually purchased so fulfillment does not
-- depend on mutable catalog values.
-- =============================================================================

ALTER TABLE `order_items`
    ADD COLUMN IF NOT EXISTS `selected_format` ENUM('digital','physical') NOT NULL DEFAULT 'physical'
    AFTER `item_type`;

-- Backfill from the current products catalog (best-effort).
-- Caveat: if a product's available_type changed after the order was placed this
-- backfill may not reflect the original purchase. Acceptable for historical orders.
UPDATE `order_items` oi
    JOIN `products` p ON p.id = oi.product_id AND oi.item_type = 'product'
SET oi.`selected_format` = CASE
        WHEN p.`available_type` = 'digital' THEN 'digital'
        ELSE 'physical'
    END
WHERE oi.`product_id` IS NOT NULL;

-- Bundles have no physical variant; all bundle deliveries are digital.
UPDATE `order_items`
SET `selected_format` = 'digital'
WHERE `item_type` = 'bundle';


-- =============================================================================
-- SECTION 1.3 — ORDERS: EXPLICIT PAYMENT STATE
-- orders.status is already VARCHAR(32) per live schema (ENUM was previously
-- converted outside this migration set). No MODIFY needed.
-- =============================================================================

-- ── 1.3a: Normalise any legacy capitalised status values (from original ENUM).
--          REGEXP '^[A-Z]' matches rows that still have initial-cap values.
UPDATE `orders`
SET `status` = CASE
        WHEN `status` IN ('Pending')                           THEN 'pending'
        WHEN `status` IN ('Awaiting Payment', 'Awaiting_payment',
                          'awaiting payment')                  THEN 'awaiting_payment'
        WHEN `status` IN ('Paid')                              THEN 'paid'
        WHEN `status` IN ('Processing')                        THEN 'processing'
        WHEN `status` IN ('Shipped')                           THEN 'shipped'
        WHEN `status` IN ('Delivered')                         THEN 'delivered'
        WHEN `status` IN ('Cancelled', 'Canceled')             THEN 'cancelled'
        WHEN `status` IN ('Failed')                            THEN 'failed'
        ELSE `status`
    END
WHERE `status` REGEXP '^[A-Z]';

-- Repair any empty-string rows (caused by past strict-mode rejection of unknown ENUM values).
UPDATE `orders` SET `status` = 'pending' WHERE `status` = '' OR `status` IS NULL;

-- ── 1.3b: Add payment_status, paid_at, and email idempotency marker.
ALTER TABLE `orders`
    ADD COLUMN IF NOT EXISTS `payment_status`
        ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending'
        AFTER `status`,
    ADD COLUMN IF NOT EXISTS `paid_at`
        DATETIME NULL
        AFTER `payment_verified_at`,
    ADD COLUMN IF NOT EXISTS `confirmation_email_sent_at`
        DATETIME NULL
        AFTER `paid_at`;

-- ── 1.3c: Backfill payment_status and paid_at from order lifecycle state.
--
-- Assumptions (verify against real order data before running on production):
--   paid / processing / shipped / delivered  → payment was confirmed successfully
--   failed                                   → payment failed before capture
--   pending / awaiting_payment / cancelled   → no confirmed payment on record
--
-- For orders that map to 'paid': use payment_verified_at if available,
-- else status_updated_at, else created_at as the best approximation of paid_at.

UPDATE `orders`
SET `payment_status` = 'paid',
    `paid_at`        = COALESCE(`payment_verified_at`, `status_updated_at`, `created_at`)
WHERE `status` IN ('paid', 'processing', 'shipped', 'delivered')
  AND `payment_status` = 'pending';

UPDATE `orders`
SET `payment_status` = 'failed'
WHERE `status` = 'failed'
  AND `payment_status` = 'pending';

-- Orders with status 'cancelled' that have a payment_id may have been refunded;
-- leave as 'pending' and let admin correct manually via the dashboard.

-- ── 1.3d: Performance indexes for payment queries.
ALTER TABLE `orders`
    ADD INDEX IF NOT EXISTS `idx_orders_payment_status` (`payment_status`),
    ADD INDEX IF NOT EXISTS `idx_orders_paid_at`        (`paid_at`);


-- =============================================================================
-- SECTION 1.4 — DIGITAL RESOURCES TABLE
-- Allows multiple protected resources per product or bundle.
-- resource_type and delivery_mode are validated by CHECK constraints.
-- storage_key and external_url must NEVER appear in public API responses.
-- =============================================================================

CREATE TABLE IF NOT EXISTS `digital_resources` (
    `id`               INT UNSIGNED   NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `product_id`       INT            NULL
                       COMMENT 'Set for product resources; NULL for bundles',
    `bundle_id`        INT            NULL
                       COMMENT 'Set for bundle resources; NULL for products',
    `title`            VARCHAR(255)   NOT NULL,
    `resource_type`    VARCHAR(40)    NOT NULL
                       COMMENT 'Allowed: file | zip | pdf | canva | figma | external_link | instructions',
    `delivery_mode`    VARCHAR(30)    NOT NULL
                       COMMENT 'Allowed: download | open_link | instructions',
    `storage_provider` VARCHAR(30)    NOT NULL DEFAULT 'local'
                       COMMENT 'Allowed: local | r2 | s3 | external',
    `storage_key`      VARCHAR(700)   NULL
                       COMMENT 'Private: cloud object key or local file path - never exposed in public APIs',
    `external_url`     TEXT           NULL
                       COMMENT 'Canva / Figma / approved HTTPS URL - served only after auth and ownership check',
    `instructions`     TEXT           NULL,
    `download_limit`   TINYINT UNSIGNED NOT NULL DEFAULT 5,
    `expiry_days`      SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    `sort_order`       INT            NOT NULL DEFAULT 0,
    `is_active`        TINYINT(1)     NOT NULL DEFAULT 1,
    `created_at`       TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Exactly one owner per row
    CONSTRAINT `chk_dr_owner_xor` CHECK (
        (`product_id` IS NOT NULL AND `bundle_id` IS NULL) OR
        (`bundle_id`  IS NOT NULL AND `product_id` IS NULL)
    ),
    CONSTRAINT `chk_dr_resource_type` CHECK (
        `resource_type` IN ('file','zip','pdf','canva','figma','external_link','instructions')
    ),
    CONSTRAINT `chk_dr_delivery_mode` CHECK (
        `delivery_mode` IN ('download','open_link','instructions')
    ),
    CONSTRAINT `chk_dr_storage_provider` CHECK (
        `storage_provider` IN ('local','r2','s3','external')
    ),
    INDEX `idx_dr_product` (`product_id`, `is_active`, `sort_order`),
    INDEX `idx_dr_bundle`  (`bundle_id`,  `is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================================================
-- SECTION 1.5 — DIGITAL DOWNLOAD ENTITLEMENTS UPGRADE
-- One entitlement per purchased resource (not one per order item).
-- Includes migration 003 guard: creates digital_downloads if absent.
-- =============================================================================

-- Create digital_downloads with the full upgraded schema if it does not exist.
-- If migration 003 already created it (without resource_id), the ALTER below adds it.
CREATE TABLE IF NOT EXISTS `digital_downloads` (
    `id`             INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `order_id`       INT              NOT NULL,
    `order_item_id`  INT              NOT NULL,
    `resource_id`    INT              NOT NULL DEFAULT 0
                     COMMENT '0 = legacy single-file row; >0 = digital_resources.id',
    `user_id`        INT              NOT NULL,
    `product_id`     INT              DEFAULT NULL,
    `bundle_id`      INT              DEFAULT NULL,
    `token`          CHAR(64)         NOT NULL,
    `download_count` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `download_limit` TINYINT UNSIGNED NOT NULL DEFAULT 5,
    `expires_at`     DATETIME         NOT NULL,
    `file_path`      VARCHAR(500)     NOT NULL DEFAULT '',
    `item_name`      VARCHAR(255)     NOT NULL DEFAULT '',
    `created_at`     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_token`               (`token`),
    UNIQUE KEY `uq_order_item_resource` (`order_item_id`, `resource_id`),
    KEY `idx_order_id`    (`order_id`),
    KEY `idx_user_id`     (`user_id`),
    KEY `idx_dd_resource` (`resource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Upgrade path: if migration 003 ran first, the table exists without resource_id.
-- ADD COLUMN IF NOT EXISTS is a no-op if the table was just created above.
ALTER TABLE `digital_downloads`
    ADD COLUMN IF NOT EXISTS `resource_id` INT NOT NULL DEFAULT 0
    COMMENT '0 = legacy single-file row; >0 = digital_resources.id'
    AFTER `order_item_id`;

-- Drop the old per-order-item unique key (one token per order item).
-- DROP INDEX IF EXISTS is a no-op if the table was freshly created above.
ALTER TABLE `digital_downloads`
    DROP INDEX IF EXISTS `uq_order_item`;

-- Add new composite unique key: one entitlement per (order item + resource).
-- resource_id=0 rows (legacy single-file) remain unique per order_item_id.
ALTER TABLE `digital_downloads`
    ADD UNIQUE KEY IF NOT EXISTS `uq_order_item_resource` (`order_item_id`, `resource_id`);

-- Index for queries that join digital_downloads with digital_resources.
ALTER TABLE `digital_downloads`
    ADD INDEX IF NOT EXISTS `idx_dd_resource` (`resource_id`);


-- =============================================================================
-- SECTION 1.6 — INVENTORY RESERVATIONS
-- Atomically reserve physical stock when an order is created.
-- Consume on payment success; release on failure, cancellation, or expiry.
-- Digital resources do not need stock reservation (unlimited delivery by default).
-- =============================================================================

CREATE TABLE IF NOT EXISTS `inventory_reservations` (
    `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `order_id`      INT           NOT NULL,
    `order_item_id` INT           NOT NULL,
    `item_type`     ENUM('product','bundle') NOT NULL DEFAULT 'product',
    `item_id`       INT           NOT NULL,
    `quantity`      INT           NOT NULL DEFAULT 1,
    `expires_at`    DATETIME      NOT NULL
                    COMMENT 'Auto-release threshold for abandoned awaiting_payment orders',
    `released_at`   DATETIME      NULL
                    COMMENT 'Set when voided: payment failed, order cancelled, or expired',
    `consumed_at`   DATETIME      NULL
                    COMMENT 'Set when stock is permanently decremented on payment confirmation',
    `created_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_reservation_order`   (`order_id`),
    INDEX `idx_reservation_item`    (`item_type`, `item_id`),
    INDEX `idx_reservation_expires` (`expires_at`, `released_at`, `consumed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- END OF MIGRATION 004
-- =============================================================================
