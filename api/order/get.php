<?php
require_once __DIR__ . '/../_bootstrap.php';

$user = apiRequireUser();

// Fetch all orders for the user in one query
$stmt = $conn->prepare('SELECT id, order_number, created_at, status, payment_method, total, subtotal, tax, shipping FROM orders WHERE user_id = ? ORDER BY created_at DESC');
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$orderRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($orderRows)) {
    sendResponse('success', 'Orders loaded.', []);
}

// Collect all order IDs and fetch all items in a single query (avoids N+1)
$orderIds    = array_column($orderRows, 'id');
$placeholders = implode(',', array_fill(0, count($orderIds), '?'));
$types        = str_repeat('i', count($orderIds));

$itemStmt = $conn->prepare(
    "SELECT oi.order_id, oi.id AS item_id, oi.product_id, oi.bundle_id, oi.quantity,
            oi.price, oi.size, oi.item_type,
            COALESCE(oi.product_name, p.name, b.name, 'Item') AS name,
            COALESCE(oi.product_image, p.image, b.image, 'img/poster.webp') AS image
     FROM order_items oi
     LEFT JOIN products p ON p.id = oi.product_id AND oi.item_type = 'product'
     LEFT JOIN bundles  b ON b.id = oi.bundle_id  AND oi.item_type = 'bundle'
     WHERE oi.order_id IN ({$placeholders})
     ORDER BY oi.order_id DESC, oi.id ASC"
);
$itemStmt->bind_param($types, ...$orderIds);
$itemStmt->execute();
$allItems = $itemStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Group items by order_id
$itemsByOrder = [];
foreach ($allItems as $item) {
    $itemsByOrder[(int) $item['order_id']][] = $item;
}

$orders = [];
foreach ($orderRows as $order) {
    $oid      = (int) $order['id'];
    $orders[] = [
        'id'             => $oid,
        'orderNumber'    => $order['order_number'],
        'order_number'   => $order['order_number'],
        'date'           => $order['created_at'],
        'created_at'     => $order['created_at'],
        'status'         => $order['status'],
        'paymentMethod'  => $order['payment_method'],
        'payment_method' => $order['payment_method'],
        'total'          => (float) $order['total'],
        'subtotal'       => (float) $order['subtotal'],
        'tax'            => (float) $order['tax'],
        'shipping'       => (float) $order['shipping'],
        'items'          => $itemsByOrder[$oid] ?? [],
    ];
}

sendResponse('success', 'Orders loaded.', $orders);
