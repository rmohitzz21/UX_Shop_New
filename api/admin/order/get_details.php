<?php
require_once __DIR__ . '/../_admin.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) sendResponse('error', 'Order ID is required.', null, 422);

$stmt = $conn->prepare('SELECT o.*, u.email, u.first_name, u.last_name, u.phone FROM orders o LEFT JOIN users u ON u.id = o.user_id WHERE o.id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if (!$order) sendResponse('error', 'Order not found.', null, 404);

$itemStmt = $conn->prepare("SELECT oi.*, COALESCE(oi.product_name, p.name, b.name, 'Item') AS name, COALESCE(oi.product_image, p.image, b.image, 'img/poster.webp') AS image FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id LEFT JOIN bundles b ON b.id = oi.bundle_id WHERE oi.order_id = ? ORDER BY oi.id ASC");
$itemStmt->bind_param('i', $id);
$itemStmt->execute();
$items = [];
$res = $itemStmt->get_result();
while ($row = $res->fetch_assoc()) $items[] = $row;
$order['items'] = $items;
if (!empty($order['shipping_address']) && is_string($order['shipping_address'])) {
    $decoded = json_decode($order['shipping_address'], true);
    if (is_array($decoded)) {
        $order['shipping_address_parsed'] = $decoded;
    }
}
sendResponse('success', 'Order loaded.', $order);
