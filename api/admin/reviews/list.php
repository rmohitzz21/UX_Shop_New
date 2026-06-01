<?php
require_once __DIR__ . '/../_admin.php';

$q      = trim((string) ($_GET['q'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));

$where  = [];
$params = [];
$types  = '';

if ($q !== '') {
    $like = '%' . $q . '%';
    $where[] = '(p.name LIKE ? OR b.name LIKE ? OR u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR r.comment LIKE ?)';
    array_push($params, $like, $like, $like, $like, $like, $like);
    $types .= 'ssssss';
}
if ($status === '1' || $status === '0') {
    $where[] = 'r.is_approved = ?';
    $params[] = (int) $status;
    $types .= 'i';
}

$sql = "SELECT r.id, r.rating, r.comment, r.is_approved, r.created_at, r.product_id, r.bundle_id,
               COALESCE(p.name, b.name, 'Unknown item') AS product_name,
               CASE
                 WHEN r.bundle_id IS NOT NULL AND r.bundle_id > 0 THEN 'bundle'
                 WHEN r.product_id IS NOT NULL AND r.product_id > 0 THEN 'product'
                 ELSE 'unknown'
               END AS item_type,
               TRIM(CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,''))) AS user_name,
               u.email AS user_email
        FROM reviews r
        LEFT JOIN products p ON p.id = r.product_id
        LEFT JOIN bundles b ON b.id = r.bundle_id
        LEFT JOIN users u ON u.id = r.user_id";
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY r.created_at DESC LIMIT 200';

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$rows = [];
$res  = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    if (trim((string) ($row['user_name'] ?? '')) === '') {
        $row['user_name'] = $row['user_email'] ?: 'Guest';
    }
    $rows[] = $row;
}
sendResponse('success', 'Reviews loaded.', $rows);
