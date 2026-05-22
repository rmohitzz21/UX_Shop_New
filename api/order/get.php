<?php
require_once __DIR__ . '/../_bootstrap.php';
$user = apiRequireUser();

$stmt = $conn->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$res = $stmt->get_result();
$orders = [];
while ($order = $res->fetch_assoc()) {
    $itemsStmt = $conn->prepare('SELECT * FROM order_items WHERE order_id = ?');
    $oid = (int) $order['id'];
    $itemsStmt->bind_param('i', $oid);
    $itemsStmt->execute();
    $itemsRes = $itemsStmt->get_result();
    $items = [];
    while ($item = $itemsRes->fetch_assoc()) $items[] = $item;
    $orders[] = [
        'id' => (int) $order['id'],
        'orderNumber' => $order['order_number'],
        'order_number' => $order['order_number'],
        'date' => $order['created_at'],
        'created_at' => $order['created_at'],
        'status' => $order['status'],
        'paymentMethod' => $order['payment_method'],
        'payment_method' => $order['payment_method'],
        'total' => (float) $order['total'],
        'subtotal' => (float) $order['subtotal'],
        'tax' => (float) $order['tax'],
        'shipping' => (float) $order['shipping'],
        'items' => $items,
    ];
}
sendResponse('success', 'Orders loaded.', $orders);
