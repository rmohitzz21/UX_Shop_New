<?php
require_once __DIR__ . '/../_bootstrap.php';

apiRequirePost();
$user  = apiRequireUser();
$input = apiInput();
validateCsrf();

$productId     = (int) ($input['product_id'] ?? 0);
$quantity      = (int) ($input['quantity'] ?? 1);
$size          = trim((string) ($input['size'] ?? ''));
$sizeValue     = $size !== '' ? $size : null;
$availableType = trim((string) ($input['available_type'] ?? 'physical'));

if ($productId <= 0) {
    sendResponse('error', 'Invalid product.', null, 422);
}

if ($quantity <= 0) {
    $stmt = $conn->prepare('DELETE FROM cart WHERE user_id = ? AND product_id = ? AND COALESCE(size, "") = COALESCE(?, "") AND available_type = ?');
    $stmt->bind_param('iiss', $user['id'], $productId, $sizeValue, $availableType);
    $stmt->execute();
    sendResponse('success', 'Item removed.');
}

$quantity = min(10, $quantity);
$stmt = $conn->prepare('UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ? AND COALESCE(size, "") = COALESCE(?, "") AND available_type = ?');
$stmt->bind_param('iiiss', $quantity, $user['id'], $productId, $sizeValue, $availableType);
$stmt->execute();

if ($stmt->affected_rows === 0) {
    sendResponse('error', 'Item not found in cart.', null, 404);
}

sendResponse('success', 'Cart updated.');
