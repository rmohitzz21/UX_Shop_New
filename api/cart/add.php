<?php
require_once __DIR__ . '/../_bootstrap.php';

apiRequirePost();
$user  = apiRequireUser();
$input = apiInput();
validateCsrf();

$rawType  = strtolower((string) ($input['item_type'] ?? 'product'));
$itemType = in_array($rawType, ['product', 'bundle'], true) ? $rawType : 'product';

$quantity = max(1, min(10, (int) ($input['quantity'] ?? 1)));
$size     = trim((string) ($input['size'] ?? ''));
$sizeVal  = $size !== '' ? $size : null;

if ($itemType === 'product') {
    $catalogId = (int) ($input['product_id'] ?? $input['id'] ?? 0);
    if ($catalogId <= 0) {
        sendResponse('error', 'Invalid product.', null, 422);
    }
    $chk = $conn->prepare('SELECT id, available_type FROM products WHERE id = ? AND is_active = 1 LIMIT 1');
    $chk->bind_param('i', $catalogId);
    $chk->execute();
    $row = $chk->get_result()->fetch_assoc();
    if (!$row) {
        sendResponse('error', 'Product not found or unavailable.', null, 404);
    }
    $catalogAvailType = (string) $row['available_type'];
    $productId = $catalogId;
    $bundleId  = null;
} else {
    $catalogId = (int) ($input['bundle_id'] ?? $input['id'] ?? $input['product_id'] ?? 0);
    if ($catalogId <= 0) {
        sendResponse('error', 'Invalid bundle.', null, 422);
    }
    $chk = $conn->prepare('SELECT id FROM bundles WHERE id = ? AND is_active = 1 LIMIT 1');
    $chk->bind_param('i', $catalogId);
    $chk->execute();
    if (!$chk->get_result()->fetch_assoc()) {
        sendResponse('error', 'Bundle not found or unavailable.', null, 404);
    }
    $catalogAvailType = 'digital';
    $productId = null;
    $bundleId  = $catalogId;
}

// Resolve selected_format: catalog wins for single-type items; 'both' defers to client
$requestedFormat = trim((string) ($input['selected_format'] ?? $input['available_type'] ?? ''));
if ($catalogAvailType === 'digital') {
    $selectedFormat = 'digital';
} elseif ($catalogAvailType === 'physical') {
    $selectedFormat = 'physical';
} else {
    $selectedFormat = $requestedFormat === 'digital' ? 'digital' : 'physical';
}

// Upsert: increment if same (user / item / format / size) row exists
if ($itemType === 'product') {
    $sel = $conn->prepare(
        'SELECT id, quantity FROM cart
         WHERE user_id = ? AND item_type = ? AND product_id = ? AND selected_format = ?
           AND COALESCE(size, "") = COALESCE(?, "")
         LIMIT 1'
    );
    $sel->bind_param('isiss', $user['id'], $itemType, $productId, $selectedFormat, $sizeVal);
} else {
    $sel = $conn->prepare(
        'SELECT id, quantity FROM cart
         WHERE user_id = ? AND item_type = ? AND bundle_id = ? AND selected_format = ?
           AND COALESCE(size, "") = COALESCE(?, "")
         LIMIT 1'
    );
    $sel->bind_param('isiss', $user['id'], $itemType, $bundleId, $selectedFormat, $sizeVal);
}
$sel->execute();
$existing = $sel->get_result()->fetch_assoc();

if ($existing) {
    $newQty = min(10, (int) $existing['quantity'] + $quantity);
    $upd = $conn->prepare('UPDATE cart SET quantity = ? WHERE id = ?');
    $upd->bind_param('ii', $newQty, $existing['id']);
    $upd->execute();
    sendResponse('success', 'Cart updated.', ['cart_id' => (int) $existing['id']]);
}

if ($itemType === 'product') {
    $ins = $conn->prepare(
        'INSERT INTO cart (user_id, item_type, product_id, quantity, size, available_type, selected_format)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $ins->bind_param('isiisss', $user['id'], $itemType, $productId, $quantity, $sizeVal, $catalogAvailType, $selectedFormat);
} else {
    $ins = $conn->prepare(
        'INSERT INTO cart (user_id, item_type, bundle_id, quantity, size, available_type, selected_format)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $ins->bind_param('isiisss', $user['id'], $itemType, $bundleId, $quantity, $sizeVal, $catalogAvailType, $selectedFormat);
}

if (!$ins->execute()) {
    sendResponse('error', 'Could not add item to cart.', null, 500);
}

sendResponse('success', 'Item added to cart.', ['cart_id' => (int) $conn->insert_id]);
