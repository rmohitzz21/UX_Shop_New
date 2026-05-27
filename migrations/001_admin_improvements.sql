-- Migration 001: Admin dashboard improvements
-- Run once on the uxmerchandise database.

-- 1. Add is_read to contact_messages (safe: adds column if missing)
ALTER TABLE contact_messages
  ADD COLUMN IF NOT EXISTS is_read TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS archived TINYINT(1) NOT NULL DEFAULT 0;

-- 2. Add is_approved to reviews if missing (schema already has it, but safe guard)
ALTER TABLE reviews
  ADD COLUMN IF NOT EXISTS is_approved TINYINT(1) NOT NULL DEFAULT 1;

-- 3. Performance indexes

-- Products
ALTER TABLE products
  ADD INDEX IF NOT EXISTS idx_products_category_active (category, is_active),
  ADD INDEX IF NOT EXISTS idx_products_featured (is_featured),
  ADD INDEX IF NOT EXISTS idx_products_created (created_at);

-- Orders
ALTER TABLE orders
  ADD INDEX IF NOT EXISTS idx_orders_status (status),
  ADD INDEX IF NOT EXISTS idx_orders_created (created_at),
  ADD INDEX IF NOT EXISTS idx_orders_user (user_id);

-- Users
ALTER TABLE users
  ADD INDEX IF NOT EXISTS idx_users_role (role),
  ADD INDEX IF NOT EXISTS idx_users_blocked (is_blocked),
  ADD INDEX IF NOT EXISTS idx_users_created (created_at);

-- Categories
ALTER TABLE categories
  ADD INDEX IF NOT EXISTS idx_categories_slug (slug),
  ADD INDEX IF NOT EXISTS idx_categories_active (is_active);

-- Bundles
ALTER TABLE bundles
  ADD INDEX IF NOT EXISTS idx_bundles_slug (slug),
  ADD INDEX IF NOT EXISTS idx_bundles_active_featured (is_active, is_featured);

-- Contact messages
ALTER TABLE contact_messages
  ADD INDEX IF NOT EXISTS idx_contact_read (is_read);

-- Reviews
ALTER TABLE reviews
  ADD INDEX IF NOT EXISTS idx_reviews_approved (is_approved),
  ADD INDEX IF NOT EXISTS idx_reviews_product (product_id);
