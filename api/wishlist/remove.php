<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/_table.php';

$user = apiRequireUser();
$input = apiInput();
validateCsrf();

$productId = (int) ($input['product_id'] ?? $input['id'] ?? 0);
if ($productId <= 0) {
    sendResponse('error', 'Invalid product.', null, 422);
}

$stmt = $conn->prepare('DELETE FROM wishlist WHERE user_id = ? AND product_id = ?');
$stmt->bind_param('ii', $user['id'], $productId);
$stmt->execute();

sendResponse('success', 'Removed from wishlist.');
