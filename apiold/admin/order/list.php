<?php
header('Content-Type: application/json');

require_once '../../../includes/config.php';

// Enforce Admin Access
requireAdmin();

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

$sql = "
    SELECT 
        o.id,
        o.order_number,
        o.total,
        o.status,
        o.payment_method,
        o.shipping_address,
        o.created_at,
        u.email,
        u.first_name,
        u.last_name,
        u.is_blocked,
        u.id as user_id,
        (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as items_count
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
";

$result = $conn->query($sql);

$orders = [];

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

$conn->close();

sendResponse("success", "Orders fetched", $orders);
?>
