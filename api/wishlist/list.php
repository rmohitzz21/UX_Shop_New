<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/_table.php';

$user = apiRequireUser();
$stmt = $conn->prepare('
    SELECT w.item_type, p.id AS product_id, b.id AS bundle_id,
           COALESCE(p.id, b.id) AS id,
           COALESCE(p.name, b.name) AS name,
           COALESCE(p.description, b.description) AS description,
           COALESCE(p.category, b.category) AS category,
           COALESCE(p.price, b.price) AS price,
           COALESCE(p.old_price, b.old_price) AS old_price,
           COALESCE(p.image, b.image) AS image,
           COALESCE(p.stock, b.stock) AS stock,
           COALESCE(p.rating, b.rating) AS rating,
           COALESCE(p.available_type, "digital") AS available_type
    FROM wishlist w
    LEFT JOIN products p ON p.id = w.product_id AND w.item_type = "product"
    LEFT JOIN bundles b ON b.id = w.bundle_id AND w.item_type = "bundle"
    WHERE w.user_id = ?
    ORDER BY w.created_at DESC
');
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $payload = apiProductPayload($row);
    $payload['type'] = $row['item_type'] ?? 'product';
    $items[] = $payload;
}

sendResponse('success', 'Wishlist loaded.', $items);
