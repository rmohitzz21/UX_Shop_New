-- Migration 007: Freebie cart and order line items
-- Run once: mysql -u root uxmerchandise < migrations/007_freebie_cart_checkout.sql

ALTER TABLE cart
    MODIFY item_type ENUM('product', 'bundle', 'freebie') NOT NULL DEFAULT 'product';

ALTER TABLE order_items
    MODIFY item_type ENUM('product', 'bundle', 'freebie') NOT NULL DEFAULT 'product';
