<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/_table.php';

$user = apiRequireUser();
$input = apiInput();
validateCsrf();

$type = ($input['item_type'] ?? $input['type'] ?? 'product') === 'bundle' ? 'bundle' : 'product';
if ($type === 'bundle') {
    $bundleId = (int) ($input['bundle_id'] ?? $input['id'] ?? 0);
    if ($bundleId <= 0) sendResponse('error', 'Invalid bundle.', null, 400);
    $productId = null;
    $stmt = $conn->prepare('INSERT IGNORE INTO wishlist (user_id, item_type, product_id, bundle_id) VALUES (?, "bundle", ?, ?)');
    $stmt->bind_param('iii', $user['id'], $productId, $bundleId);
} else {
    $productId = apiEnsureProduct($conn, $input);
    $bundleId = null;
    $stmt = $conn->prepare('INSERT IGNORE INTO wishlist (user_id, item_type, product_id, bundle_id) VALUES (?, "product", ?, ?)');
    $stmt->bind_param('iii', $user['id'], $productId, $bundleId);
}
$stmt->execute();

sendResponse('success', 'Added to wishlist.', ['product_id' => $productId, 'bundle_id' => $bundleId]);
