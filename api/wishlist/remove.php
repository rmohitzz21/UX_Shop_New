<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/_table.php';

$user = apiRequireUser();
$input = apiInput();
validateCsrf();

$type = ($input['item_type'] ?? $input['type'] ?? 'product') === 'bundle' ? 'bundle' : 'product';
$itemId = (int) ($input[$type === 'bundle' ? 'bundle_id' : 'product_id'] ?? $input['id'] ?? 0);
if ($itemId <= 0) {
    sendResponse('error', 'Invalid wishlist item.', null, 422);
}

$column = $type === 'bundle' ? 'bundle_id' : 'product_id';
$stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND item_type = ? AND $column = ?");
$stmt->bind_param('isi', $user['id'], $type, $itemId);
$stmt->execute();

sendResponse('success', 'Removed from wishlist.');
