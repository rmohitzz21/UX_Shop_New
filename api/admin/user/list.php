<?php
require_once __DIR__ . '/../_admin.php';

$sql = "SELECT u.id, u.email, u.first_name, u.last_name, u.phone, u.role, u.is_blocked, u.created_at,
        COUNT(o.id) AS order_count, COALESCE(SUM(o.total), 0) AS lifetime_value
        FROM users u
        LEFT JOIN orders o ON o.user_id = u.id
        GROUP BY u.id
        ORDER BY u.created_at DESC, u.id DESC";
$rows = [];
$res = $conn->query($sql);
while ($res && $row = $res->fetch_assoc()) $rows[] = $row;
sendResponse('success', 'Users loaded.', $rows);
