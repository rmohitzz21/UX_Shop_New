-- Migration 011: Per-product custom fields
-- Adds a JSON column to products for admin-defined custom info rows.

ALTER TABLE products
    ADD COLUMN IF NOT EXISTS custom_fields TEXT NULL COMMENT 'JSON array of {label, value} pairs shown in Product Information';
