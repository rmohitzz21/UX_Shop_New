<?php
require_once '../../includes/config.php';

if (empty($_SESSION['user_id'])) {
    sendResponse('error', 'Login required.', null, 401);
}

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$itemType = in_array($data['item_type'] ?? '', ['product', 'bundle']) ? $data['item_type'] : null;
$itemId   = isset($data['item_id']) ? (int) $data['item_id'] : 0;

if (!$itemType || $itemId <= 0) {
    sendResponse('error', 'Invalid item.', null, 400);
}

$uid = (int) $_SESSION['user_id'];

if ($itemType === 'product') {
    $check = $conn->prepare('SELECT id FROM wishlist WHERE user_id=? AND item_type="product" AND product_id=?');
    $check->bind_param('ii', $uid, $itemId);
} else {
    $check = $conn->prepare('SELECT id FROM wishlist WHERE user_id=? AND item_type="bundle" AND bundle_id=?');
    $check->bind_param('ii', $uid, $itemId);
}
$check->execute();
$existing = $check->get_result()->fetch_assoc();

if ($existing) {
    $del = $conn->prepare('DELETE FROM wishlist WHERE id=?');
    $del->bind_param('i', $existing['id']);
    $del->execute();
    sendResponse('success', 'Removed from wishlist.', ['action' => 'removed']);
} else {
    if ($itemType === 'product') {
        $ins = $conn->prepare('INSERT INTO wishlist (user_id, item_type, product_id) VALUES (?,?,?)');
        $ins->bind_param('isi', $uid, $itemType, $itemId);
    } else {
        $ins = $conn->prepare('INSERT INTO wishlist (user_id, item_type, bundle_id) VALUES (?,?,?)');
        $ins->bind_param('isi', $uid, $itemType, $itemId);
    }
    $ins->execute();
    sendResponse('success', 'Added to wishlist.', ['action' => 'added']);
}
