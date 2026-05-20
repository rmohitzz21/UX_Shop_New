<?php
require_once __DIR__ . '/../_bootstrap.php';

$user = apiRequireUser();
$input = apiInput();
validateCsrf();

$items = $input['cart'] ?? [];
if (!is_array($items)) {
    $items = [];
}

foreach ($items as $item) {
    if (!is_array($item)) {
        continue;
    }
    $payload = [
        'product_id' => $item['id'] ?? null,
        'quantity' => $item['quantity'] ?? 1,
        'size' => $item['size'] ?? null,
        'available_type' => $item['available_type'] ?? 'physical',
        'details' => $item,
    ];
    $productId = apiEnsureProduct($conn, $payload);
    $quantity = max(1, min(10, (int) ($payload['quantity'] ?? 1)));
    $size = trim((string) ($payload['size'] ?? ''));
    $sizeValue = $size !== '' ? $size : null;
    $availableType = $payload['available_type'];
    if ($availableType === 'both') {
        $availableType = 'physical';
    }

    $stmt = $conn->prepare('SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND COALESCE(size, "") = COALESCE(?, "") AND available_type = ? LIMIT 1');
    $stmt->bind_param('iiss', $user['id'], $productId, $sizeValue, $availableType);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    if ($existing) {
        $newQty = min(10, (int) $existing['quantity'] + $quantity);
        $stmt = $conn->prepare('UPDATE cart SET quantity = ? WHERE id = ?');
        $stmt->bind_param('ii', $newQty, $existing['id']);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare('INSERT INTO cart (user_id, product_id, quantity, size, available_type) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('iiiss', $user['id'], $productId, $quantity, $sizeValue, $availableType);
        $stmt->execute();
    }
}

sendResponse('success', 'Cart merged.');
