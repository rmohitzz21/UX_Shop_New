<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/_table.php';

$user = apiRequireUser();
$stmt = $conn->prepare('
    SELECT p.*
    FROM wishlist w
    INNER JOIN products p ON p.id = w.product_id
    WHERE w.user_id = ?
    ORDER BY w.created_at DESC
');
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = apiProductPayload($row);
}

sendResponse('success', 'Wishlist loaded.', $items);
