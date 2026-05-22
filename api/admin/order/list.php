<?php
require_once __DIR__ . '/../_admin.php';

$sql = "SELECT o.*, u.email, u.first_name, u.last_name, u.phone,
        COUNT(oi.id) AS items_count
        FROM orders o
        LEFT JOIN users u ON u.id = o.user_id
        LEFT JOIN order_items oi ON oi.order_id = o.id
        GROUP BY o.id
        ORDER BY o.created_at DESC, o.id DESC";
$rows = [];
$res = $conn->query($sql);
while ($res && $row = $res->fetch_assoc()) {
    $rows[] = $row;
}
sendResponse('success', 'Orders loaded.', $rows);
