<?php
// Table managed by migrations/006_freebies_wishlist_tables.sql
// Guard: only run DDL in non-production environments where migration hasn't been applied yet
if ((getenv('APP_ENV') ?: 'local') !== 'production') {
    $conn->query('CREATE TABLE IF NOT EXISTS wishlist (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NULL,
        bundle_id INT NULL,
        item_type ENUM(\'product\',\'bundle\') NOT NULL DEFAULT \'product\',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_wishlist_item (user_id, item_type, product_id, bundle_id),
        INDEX idx_wishlist_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
}
