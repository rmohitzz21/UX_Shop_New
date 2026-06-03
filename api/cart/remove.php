<?php
require_once __DIR__ . '/../_bootstrap.php';

apiRequirePost();
$user  = apiRequireUser();
$input = apiInput();
validateCsrf();

$cartId = (int) ($input['cart_id'] ?? 0);

if ($cartId > 0) {
    $stmt = $conn->prepare('DELETE FROM cart WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $cartId, $user['id']);
    $stmt->execute();
    sendResponse('success', 'Item removed.', ['removed' => $stmt->affected_rows > 0]);
}

// Legacy fallback: product_id + size + available_type (pre-Phase-2 clients)
$productId     = (int) ($input['product_id'] ?? 0);
$size          = trim((string) ($input['size'] ?? ''));
$sizeVal       = $size !== '' ? $size : null;
$availableType = trim((string) ($input['available_type'] ?? 'physical'));

if ($productId <= 0) {
    sendResponse('error', 'cart_id or product_id is required.', null, 422);
}

$stmt = $conn->prepare('DELETE FROM cart WHERE user_id = ? AND product_id = ? AND COALESCE(size, "") = COALESCE(?, "") AND available_type = ?');
$stmt->bind_param('iiss', $user['id'], $productId, $sizeVal, $availableType);
$stmt->execute();

sendResponse('success', 'Item removed.', ['removed' => $stmt->affected_rows > 0]);
