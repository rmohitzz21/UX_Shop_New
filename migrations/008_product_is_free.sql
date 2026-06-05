-- Migration 008: Mark products as free (auto-list on freebies page)
-- Run once: mysql -u root uxmerchandise < migrations/008_product_is_free.sql

ALTER TABLE products
    ADD COLUMN is_free TINYINT(1) NOT NULL DEFAULT 0 AFTER is_featured,
    ADD INDEX idx_products_is_free (is_free, is_active);
