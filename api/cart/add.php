<?php
require_once __DIR__ . '/../_bootstrap.php';

apiRequirePost();
$user  = apiRequireUser();
$input = apiInput();
validateCsrf();

$quantity      = max(1, min(10, (int) ($input['quantity'] ?? 1)));
$size          = trim((string) ($input['size'] ?? ''));
$sizeValue     = $size !== '' ? $size : null;
$availableType = trim((string) ($input['available_type'] ?? 'physical'));
if (!in_array($availableType, ['physical', 'digital', 'both'], true)) {
    $availableType = 'physical';
}
if ($availableType === 'both') {
    $availableType = 'physical';
}

$productId = apiEnsureProduct($conn, $input);

$stmt = $conn->prepare('SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND COALESCE(size, "") = COALESCE(?, "") AND available_type = ? LIMIT 1');
$stmt->bind_param('iiss', $user['id'], $productId, $sizeValue, $availableType);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();

if ($existing) {
    $newQty = min(10, (int) $existing['quantity'] + $quantity);
    $stmt   = $conn->prepare('UPDATE cart SET quantity = ? WHERE id = ?');
    $stmt->bind_param('ii', $newQty, $existing['id']);
    $stmt->execute();
} else {
    $stmt = $conn->prepare('INSERT INTO cart (user_id, product_id, quantity, size, available_type) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('iiiss', $user['id'], $productId, $quantity, $sizeValue, $availableType);
    $stmt->execute();
}

sendResponse('success', 'Item added to cart.', ['product_id' => $productId]);
