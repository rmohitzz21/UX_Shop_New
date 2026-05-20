<?php
require_once __DIR__ . '/../_bootstrap.php';

$user = apiRequireUser();
$input = apiInput();
validateCsrf();

$productId = (int) ($input['product_id'] ?? 0);
$size = trim((string) ($input['size'] ?? ''));
$sizeValue = $size !== '' ? $size : null;
$availableType = trim((string) ($input['available_type'] ?? 'physical'));

if ($productId <= 0) {
    sendResponse('error', 'Invalid product.', null, 422);
}

$stmt = $conn->prepare('DELETE FROM cart WHERE user_id = ? AND product_id = ? AND COALESCE(size, "") = COALESCE(?, "") AND available_type = ?');
$stmt->bind_param('iiss', $user['id'], $productId, $sizeValue, $availableType);
$stmt->execute();

sendResponse('success', 'Item removed from cart.');
