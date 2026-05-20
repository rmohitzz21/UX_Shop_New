<?php
header('Content-Type: application/json');

require_once '../../../includes/config.php';

// Enforce Admin Access
requireAdmin();

if ($conn->connect_error) {
    sendResponse("error", "Database connection failed", null, 500);
}

if (!isset($_GET['id'])) {
    sendResponse("error", "Order ID is required", null, 400);
}

$orderId = intval($_GET['id']);

// Fetch order details
$sql = "
    SELECT 
        o.*,
        u.email,
        u.first_name,
        u.last_name,
        u.phone
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $orderId);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    sendResponse("error", "Order not found", null, 404);
}

// Fetch order items
$sqlItems = "
    SELECT 
        oi.*,
        p.name,
        p.image
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
";

$stmtItems = $conn->prepare($sqlItems);
$stmtItems->bind_param("i", $orderId);
$stmtItems->execute();
$resultItems = $stmtItems->get_result();
$items = [];
while ($item = $resultItems->fetch_assoc()) {
    $items[] = $item;
}

$order['items'] = $items;

sendResponse("success", "Order details fetched", $order);

$stmt->close();
$stmtItems->close();
$conn->close();
?>
