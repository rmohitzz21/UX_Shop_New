<?php
require_once __DIR__ . '/../_bootstrap.php';

$user = apiRequireUser();

// Cleanup invalid cart rows (deleted/archived products) for this user
$cleanup = $conn->prepare('
    DELETE c
    FROM cart c
    LEFT JOIN products p ON p.id = c.product_id
    WHERE c.user_id = ? AND (p.id IS NULL OR p.is_active = 0)
');
$cleanup->bind_param('i', $user['id']);
$cleanup->execute();

$stmt = $conn->prepare('
    SELECT c.product_id AS id, c.quantity, c.size, c.available_type, p.name, p.price, p.image, p.description, p.category
    FROM cart c
    INNER JOIN products p ON p.id = c.product_id
    WHERE c.user_id = ? AND p.is_active = 1
    ORDER BY c.created_at DESC, c.id DESC
');
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = [
        'id' => (string) $row['id'],
        'name' => $row['name'],
        'price' => (float) $row['price'],
        'image' => apiProductImage($row['image'] ?? ''),
        'description' => $row['description'] ?? '',
        'category' => $row['category'] ?? '',
        'quantity' => (int) $row['quantity'],
        'size' => $row['size'] ?? null,
        'available_type' => $row['available_type'] ?? 'physical',
    ];
}

sendResponse('success', 'Cart loaded.', $items);
