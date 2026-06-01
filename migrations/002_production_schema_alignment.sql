-- Migration 002: Production schema alignment
-- Consolidates all tables and columns defined in marketplace.php / freebies.php.
-- Run once: mysql -u root uxmerchandise < migrations/002_production_schema_alignment.sql

-- ── Core catalog ──────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS categories (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(120) NOT NULL UNIQUE,
    slug       VARCHAR(140) NOT NULL UNIQUE,
    description TEXT NULL,
    icon       VARCHAR(255) NULL,
    accent     VARCHAR(40) DEFAULT 'purple',
    is_active  TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS products (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(255) NOT NULL,
    slug             VARCHAR(255) NULL,
    sku              VARCHAR(80)  NULL,
    description      TEXT NULL,
    whats_included   TEXT NULL,
    file_specification TEXT NULL,
    high_resolution  VARCHAR(80)  NULL DEFAULT 'Yes',
    compatible_software VARCHAR(255) NULL,
    software_version VARCHAR(120) NULL,
    files_included   VARCHAR(255) NULL,
    grid_columns     VARCHAR(80)  NULL,
    layout_type      VARCHAR(120) NULL,
    license_type     VARCHAR(80)  NULL DEFAULT 'Premium',
    category         VARCHAR(100) NULL,
    tags             VARCHAR(500) NULL,
    related_products VARCHAR(255) NULL,
    price            DECIMAL(10,2) NOT NULL DEFAULT 0,
    old_price        DECIMAL(10,2) NULL,
    commercial_price DECIMAL(10,2) NULL,
    image            VARCHAR(255) NULL,
    additional_images TEXT NULL,
    stock            INT NOT NULL DEFAULT 0,
    rating           DECIMAL(3,2) DEFAULT 4.50,
    available_type   VARCHAR(20) DEFAULT 'digital',
    is_active        TINYINT(1) NOT NULL DEFAULT 1,
    is_featured      TINYINT(1) NOT NULL DEFAULT 0,
    sales_count      INT NOT NULL DEFAULT 0,
    view_count       INT NOT NULL DEFAULT 0,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Columns that marketplace.php adds at runtime (safe to add here idempotently)
ALTER TABLE products
    ADD COLUMN IF NOT EXISTS slug               VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS sku                VARCHAR(80)  NULL,
    ADD COLUMN IF NOT EXISTS tags               VARCHAR(500) NULL,
    ADD COLUMN IF NOT EXISTS is_active          TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS is_featured        TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS sales_count        INT NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS view_count         INT NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS commercial_price   DECIMAL(10,2) NULL,
    ADD COLUMN IF NOT EXISTS additional_images  TEXT NULL,
    ADD COLUMN IF NOT EXISTS whats_included     TEXT NULL,
    ADD COLUMN IF NOT EXISTS file_specification TEXT NULL,
    ADD COLUMN IF NOT EXISTS related_products   VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS high_resolution    VARCHAR(80)  NULL DEFAULT 'Yes',
    ADD COLUMN IF NOT EXISTS compatible_software VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS software_version   VARCHAR(120) NULL,
    ADD COLUMN IF NOT EXISTS files_included     VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS grid_columns       VARCHAR(80)  NULL,
    ADD COLUMN IF NOT EXISTS layout_type        VARCHAR(120) NULL,
    ADD COLUMN IF NOT EXISTS license_type       VARCHAR(80)  NULL DEFAULT 'Premium';

CREATE TABLE IF NOT EXISTS bundles (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(255) NOT NULL,
    slug           VARCHAR(255) NULL,
    description    TEXT NULL,
    whats_included TEXT NULL,
    file_specification TEXT NULL,
    category       VARCHAR(100) DEFAULT 'Bundles',
    tags           VARCHAR(500) NULL,
    included_items TEXT NULL,
    badge_text     VARCHAR(80)  NULL DEFAULT 'Best Seller',
    price          DECIMAL(10,2) NOT NULL DEFAULT 0,
    old_price      DECIMAL(10,2) NULL,
    image          VARCHAR(255) NULL,
    additional_images TEXT NULL,
    rating         DECIMAL(3,2) DEFAULT 4.70,
    stock          INT NOT NULL DEFAULT 999,
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    is_featured    TINYINT(1) NOT NULL DEFAULT 0,
    sales_count    INT NOT NULL DEFAULT 0,
    view_count     INT NOT NULL DEFAULT 0,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_bundle_active_featured (is_active, is_featured),
    INDEX idx_bundle_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE bundles
    ADD COLUMN IF NOT EXISTS included_items    TEXT NULL,
    ADD COLUMN IF NOT EXISTS view_count        INT NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS additional_images TEXT NULL,
    ADD COLUMN IF NOT EXISTS whats_included    TEXT NULL,
    ADD COLUMN IF NOT EXISTS file_specification TEXT NULL,
    ADD COLUMN IF NOT EXISTS badge_text        VARCHAR(80) NULL DEFAULT 'Best Seller';

CREATE TABLE IF NOT EXISTS bundle_items (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    bundle_id  INT NOT NULL,
    product_id INT NOT NULL,
    quantity   INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_bundle_product (bundle_id, product_id),
    INDEX idx_bundle_items_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Freebies ──────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS freebies (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(255) NOT NULL,
    slug           VARCHAR(255) NOT NULL,
    description    TEXT,
    category       VARCHAR(100) DEFAULT 'General',
    image          VARCHAR(500) DEFAULT '',
    file_url       VARCHAR(500) DEFAULT '',
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    is_featured    TINYINT(1) NOT NULL DEFAULT 0,
    sort_order     INT NOT NULL DEFAULT 0,
    download_count INT NOT NULL DEFAULT 0,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── User & auth ───────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS addresses (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    first_name    VARCHAR(100) NOT NULL,
    last_name     VARCHAR(100) NOT NULL,
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255) NULL,
    city          VARCHAR(120) NOT NULL,
    state         VARCHAR(120) NOT NULL,
    zip_code      VARCHAR(30)  NOT NULL,
    country       VARCHAR(80)  NOT NULL DEFAULT 'IN',
    phone         VARCHAR(40)  NOT NULL,
    is_default    TINYINT(1) NOT NULL DEFAULT 0,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_addresses_user (user_id, is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at    DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_password_reset_token (token_hash),
    INDEX idx_password_reset_user (user_id, used_at, expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Orders ────────────────────────────────────────────────────────────────────

ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS status_updated_at   TIMESTAMP NULL,
    ADD COLUMN IF NOT EXISTS customer_note       TEXT NULL,
    ADD COLUMN IF NOT EXISTS payment_id          VARCHAR(120) NULL,
    ADD COLUMN IF NOT EXISTS razorpay_order_id   VARCHAR(120) NULL,
    ADD COLUMN IF NOT EXISTS payment_verified_at DATETIME NULL,
    ADD COLUMN IF NOT EXISTS payment_currency    VARCHAR(10) NULL DEFAULT 'INR';

ALTER TABLE order_items
    MODIFY COLUMN product_id INT NULL,
    ADD COLUMN IF NOT EXISTS bundle_id     INT NULL,
    ADD COLUMN IF NOT EXISTS product_name  VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS product_image VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS item_type     ENUM('product','bundle') NOT NULL DEFAULT 'product';

-- ── Contact & reviews ─────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS contact_messages (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(160) NOT NULL,
    email      VARCHAR(255) NOT NULL,
    phone      VARCHAR(40)  NULL,
    subject    VARCHAR(180) NULL,
    message    TEXT NOT NULL,
    ip         VARCHAR(64)  NULL,
    is_read    TINYINT(1) NOT NULL DEFAULT 0,
    archived   TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_contact_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE contact_messages
    ADD COLUMN IF NOT EXISTS is_read  TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS archived TINYINT(1) NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS reviews (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    product_id  INT NULL,
    bundle_id   INT NULL,
    rating      TINYINT NOT NULL,
    comment     TEXT NULL,
    is_approved TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_reviews_product (product_id),
    INDEX idx_reviews_bundle (bundle_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Admin & supporting tables ─────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS admins (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NULL,
    email         VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    name          VARCHAR(160) DEFAULT 'Admin',
    is_active     TINYINT(1) NOT NULL DEFAULT 1,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS featured_items (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    item_type  ENUM('product','bundle') NOT NULL,
    item_id    INT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active  TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_featured_item (item_type, item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS inventory_movements (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    item_type    ENUM('product','bundle') NOT NULL DEFAULT 'product',
    item_id      INT NOT NULL,
    admin_id     INT NULL,
    change_qty   INT NOT NULL,
    stock_before INT NOT NULL DEFAULT 0,
    stock_after  INT NOT NULL DEFAULT 0,
    reason       VARCHAR(160) NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_inventory_item (item_type, item_id, created_at),
    INDEX idx_inventory_admin (admin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Performance indexes ───────────────────────────────────────────────────────

ALTER TABLE products
    ADD INDEX IF NOT EXISTS idx_products_active    (is_active),
    ADD INDEX IF NOT EXISTS idx_products_featured  (is_featured),
    ADD INDEX IF NOT EXISTS idx_products_category  (category, is_active);

ALTER TABLE orders
    ADD INDEX IF NOT EXISTS idx_orders_user_status (user_id, status),
    ADD INDEX IF NOT EXISTS idx_orders_razorpay    (razorpay_order_id);
