<?php
require_once __DIR__ . '/../_admin.php';

$q      = trim((string) ($_GET['q'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$page   = max(1, (int) ($_GET['page'] ?? 1));
$limit  = max(1, min(100, (int) ($_GET['limit'] ?? 25)));
$offset = ($page - 1) * $limit;

$where  = ['1=1'];
$params = [];
$types  = '';

if ($q !== '') {
    $like    = '%' . $q . '%';
    $where[] = '(o.order_number LIKE ? OR u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)';
    array_push($params, $like, $like, $like, $like);
    $types  .= 'ssss';
}
if ($status !== '') {
    $where[] = 'o.status = ?';
    $params[] = $status;
    $types   .= 's';
}

$whereSql = implode(' AND ', $where);

// Count total matching rows
$cntSql  = "SELECT COUNT(*) AS c FROM orders o LEFT JOIN users u ON u.id = o.user_id WHERE {$whereSql}";
$cntStmt = $conn->prepare($cntSql);
if ($params) {
    $cntStmt->bind_param($types, ...$params);
}
$cntStmt->execute();
$total = (int) ($cntStmt->get_result()->fetch_assoc()['c'] ?? 0);

// Fetch paginated rows
$sql = "SELECT o.*, u.email, u.first_name, u.last_name, u.phone,
        COUNT(oi.id) AS items_count
        FROM orders o
        LEFT JOIN users u ON u.id = o.user_id
        LEFT JOIN order_items oi ON oi.order_id = o.id
        WHERE {$whereSql}
        GROUP BY o.id
        ORDER BY o.created_at DESC, o.id DESC
        LIMIT ? OFFSET ?";

$types2  = $types . 'ii';
$params2 = [...$params, $limit, $offset];

$stmt = $conn->prepare($sql);
$stmt->bind_param($types2, ...$params2);
$stmt->execute();

$rows = [];
$res  = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $rows[] = $row;
}

sendResponse('success', 'Orders loaded.', [
    'orders'   => $rows,
    'total'    => $total,
    'page'     => $page,
    'limit'    => $limit,
    'has_more' => ($offset + $limit) < $total,
]);
