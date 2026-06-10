<?php
require_once __DIR__ . '/../_admin.php';

$q = trim((string) ($_GET['q'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$category = trim((string) ($_GET['category'] ?? ''));
$where = ['1=1'];
$params = [];
$types = '';

if ($q !== '') {
    $like = '%' . $q . '%';
    $where[] = '(p.name LIKE ? OR p.sku LIKE ? OR p.category LIKE ? OR p.tags LIKE ?)';
    array_push($params, $like, $like, $like, $like);
    $types .= 'ssss';
}
if ($category !== '') {
    $where[] = 'p.category = ?';
    $params[] = $category;
    $types .= 's';
}
if ($status !== '') {
    $where[] = 'p.is_active = ?';
    $params[] = adminBool($status, 1);
    $types .= 'i';
}

$sql = 'SELECT p.*,
        (SELECT COUNT(DISTINCT o.user_id)
         FROM order_items oi
         INNER JOIN orders o ON o.id = oi.order_id
         WHERE oi.product_id = p.id
           AND oi.item_type = \'product\'
           AND LOWER(o.payment_status) = \'paid\') AS buyer_count,
        (SELECT COALESCE(SUM(dd.download_count), 0)
         FROM digital_downloads dd
         WHERE dd.product_id = p.id) AS download_total
        FROM products p
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY p.updated_at DESC, p.id DESC';
$stmt = $conn->prepare($sql);
if (!$stmt) sendResponse('error', 'Could not load products: ' . $conn->error, null, 500);
if ($params) $stmt->bind_param($types, ...$params);
if (!$stmt->execute()) sendResponse('error', 'Could not load products: ' . $stmt->error, null, 500);
$rows = [];
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $rows[] = $row;
}
sendResponse('success', 'Products loaded.', $rows);
