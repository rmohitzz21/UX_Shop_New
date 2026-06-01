-- Migration 003: Digital product delivery
-- Run once against the uxmerchandise database.
-- Requires MySQL 8.0+ for ADD COLUMN IF NOT EXISTS.

-- ── products ────────────────────────────────────────────────────────────────
ALTER TABLE `products`
    ADD COLUMN IF NOT EXISTS `digital_file_path`     VARCHAR(500)           DEFAULT NULL  AFTER `available_type`,
    ADD COLUMN IF NOT EXISTS `download_limit`         TINYINT UNSIGNED  NOT NULL DEFAULT 5  AFTER `digital_file_path`,
    ADD COLUMN IF NOT EXISTS `download_expiry_days`   SMALLINT UNSIGNED NOT NULL DEFAULT 30 AFTER `download_limit`;

-- ── bundles ─────────────────────────────────────────────────────────────────
ALTER TABLE `bundles`
    ADD COLUMN IF NOT EXISTS `digital_file_path`     VARCHAR(500)           DEFAULT NULL  AFTER `available_type`,
    ADD COLUMN IF NOT EXISTS `download_limit`         TINYINT UNSIGNED  NOT NULL DEFAULT 5  AFTER `digital_file_path`,
    ADD COLUMN IF NOT EXISTS `download_expiry_days`   SMALLINT UNSIGNED NOT NULL DEFAULT 30 AFTER `download_limit`;

-- ── digital_downloads ───────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `digital_downloads` (
    `id`             INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `order_id`       INT               NOT NULL,
    `order_item_id`  INT               NOT NULL,
    `user_id`        INT               NOT NULL,
    `product_id`     INT               DEFAULT NULL,
    `bundle_id`      INT               DEFAULT NULL,
    `token`          CHAR(64)          NOT NULL,
    `download_count` TINYINT UNSIGNED  NOT NULL DEFAULT 0,
    `download_limit` TINYINT UNSIGNED  NOT NULL DEFAULT 5,
    `expires_at`     DATETIME          NOT NULL,
    `file_path`      VARCHAR(500)      NOT NULL DEFAULT '',
    `item_name`      VARCHAR(255)      NOT NULL DEFAULT '',
    `created_at`     DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_token`      (`token`),
    UNIQUE KEY `uq_order_item` (`order_item_id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_user_id`  (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
