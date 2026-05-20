<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/helpers.php';

// SEC-06: require user session only — admin sessions rejected
requireUserAuth();
$userId = (int) $_SESSION['user_id'];

// Single JOIN query — eliminates N+1 (PERF-01)
// Uses snapshot columns (product_name, product_image) so deleted products
// never break order history (PERF-03)
$sql = "
    SELECT
        o.id            AS order_id,
        o.order_number,
        o.total,
        o.status,
        o.payment_method,
        o.created_at,
        o.shipping_address,
        oi.id           AS item_id,
        oi.product_id,
        oi.quantity,
        oi.price,
        oi.size,
        COALESCE(oi.product_name, p.name, 'Deleted Product') AS item_name,
        COALESCE(oi.product_image, p.image, 'img/sticker.webp') AS item_image
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    LEFT JOIN products p     ON p.id = oi.product_id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC, oi.id ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

// Group rows by order
$ordersMap = [];
while ($row = $result->fetch_assoc()) {
    $oid = $row['order_id'];

    if (!isset($ordersMap[$oid])) {
        $ordersMap[$oid] = [
            'orderNumber' => $row['order_number'],
            'date'        => $row['created_at'],
            'status'      => $row['status'],
            'total'       => (float) $row['total'],
            'shipping'    => json_decode($row['shipping_address'], true),
            'items'       => []
        ];
    }

    if (!empty($row['item_id'])) {
        $ordersMap[$oid]['items'][] = [
            'id'       => $row['product_id'],
            'name'     => $row['item_name'],
            'image'    => $row['item_image'],
            'price'    => (float) $row['price'],
            'quantity' => (int)   $row['quantity'],
            'size'     => $row['size']
        ];
    }
}

$stmt->close();
$conn->close();

sendResponse("success", "Orders fetched successfully", array_values($ordersMap));
