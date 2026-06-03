-- Migration 006: Freebies and wishlist tables (moved from runtime PHP DDL)
-- Run once: mysql -u root uxmerchandise < migrations/006_freebies_wishlist_tables.sql

CREATE TABLE IF NOT EXISTS freebies (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(255) NOT NULL,
    slug         VARCHAR(255) NOT NULL,
    description  TEXT,
    category     VARCHAR(100) DEFAULT 'General',
    image        VARCHAR(500) DEFAULT '',
    file_url     VARCHAR(500) DEFAULT '',
    is_active    TINYINT(1)   NOT NULL DEFAULT 1,
    is_featured  TINYINT(1)   NOT NULL DEFAULT 0,
    sort_order   INT          NOT NULL DEFAULT 0,
    download_count INT        NOT NULL DEFAULT 0,
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_freebies_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS wishlist (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT  NOT NULL,
    product_id INT  NULL,
    bundle_id  INT  NULL,
    item_type  ENUM('product','bundle') NOT NULL DEFAULT 'product',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_wishlist_item (user_id, item_type, product_id, bundle_id),
    INDEX idx_wishlist_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
