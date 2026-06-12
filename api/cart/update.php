<?php
require_once __DIR__ . '/../_bootstrap.php';

apiRequirePost();
$user  = apiRequireUser();
$input = apiInput();
validateCsrf();

$quantity = (int) ($input['quantity'] ?? 1);
$cartId   = (int) ($input['cart_id']  ?? 0);

if ($cartId > 0) {
    if ($quantity <= 0) {
        $stmt = $conn->prepare('DELETE FROM cart WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $cartId, $user['id']);
        $stmt->execute();
        sendResponse('success', 'Item removed.');
    }

    $quantity = min(10, $quantity);
    $stmt = $conn->prepare('UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?');
    $stmt->bind_param('iii', $quantity, $cartId, $user['id']);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        sendResponse('error', 'Item not found in cart.', null, 404);
    }

    sendResponse('success', 'Cart updated.');
}

$itemType = apiNormalizeCartItemType((string) ($input['item_type'] ?? 'product'));
$size          = trim((string) ($input['size'] ?? ''));
$sizeVal       = $size !== '' ? $size : null;
$selectedFormat = trim((string) ($input['selected_format'] ?? $input['available_type'] ?? 'digital'));

if ($itemType === 'bundle') {
    $bundleId = (int) ($input['bundle_id'] ?? $input['product_id'] ?? $input['id'] ?? 0);
    if ($bundleId <= 0) {
        sendResponse('error', 'cart_id or bundle_id is required.', null, 422);
    }

    if ($quantity <= 0) {
        $stmt = $conn->prepare(
            'DELETE FROM cart WHERE user_id = ? AND item_type = ? AND bundle_id = ? AND selected_format = ?'
        );
        $stmt->bind_param('isis', $user['id'], $itemType, $bundleId, $selectedFormat);
        $stmt->execute();
        sendResponse('success', 'Item removed.');
    }

    $quantity = min(10, $quantity);
    $stmt = $conn->prepare(
        'UPDATE cart SET quantity = ? WHERE user_id = ? AND item_type = ? AND bundle_id = ? AND selected_format = ?'
    );
    $stmt->bind_param('iisis', $quantity, $user['id'], $itemType, $bundleId, $selectedFormat);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        sendResponse('error', 'Item not found in cart.', null, 404);
    }

    sendResponse('success', 'Cart updated.');
}

$productId = (int) ($input['product_id'] ?? $input['id'] ?? 0);
if ($productId <= 0) {
    sendResponse('error', 'cart_id or product_id is required.', null, 422);
}

if ($quantity <= 0) {
    $stmt = $conn->prepare(
        'DELETE FROM cart WHERE user_id = ? AND item_type = ? AND product_id = ? AND COALESCE(size, "") = COALESCE(?, "") AND selected_format = ?'
    );
    $stmt->bind_param('isiss', $user['id'], $itemType, $productId, $sizeVal, $selectedFormat);
    $stmt->execute();
    sendResponse('success', 'Item removed.');
}

$quantity = min(10, $quantity);
$stmt = $conn->prepare(
    'UPDATE cart SET quantity = ? WHERE user_id = ? AND item_type = ? AND product_id = ? AND COALESCE(size, "") = COALESCE(?, "") AND selected_format = ?'
);
$stmt->bind_param('iisiss', $quantity, $user['id'], $itemType, $productId, $sizeVal, $selectedFormat);
$stmt->execute();

if ($stmt->affected_rows === 0) {
    sendResponse('error', 'Item not found in cart.', null, 404);
}

sendResponse('success', 'Cart updated.');
