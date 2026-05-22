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
    $where[] = '(name LIKE ? OR sku LIKE ? OR category LIKE ? OR tags LIKE ?)';
    array_push($params, $like, $like, $like, $like);
    $types .= 'ssss';
}
if ($category !== '') {
    $where[] = 'category = ?';
    $params[] = $category;
    $types .= 's';
}
if ($status !== '') {
    $where[] = 'is_active = ?';
    $params[] = adminBool($status, 1);
    $types .= 'i';
}

$sql = 'SELECT * FROM products WHERE ' . implode(' AND ', $where) . ' ORDER BY updated_at DESC, id DESC';
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = [];
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $rows[] = $row;
}
sendResponse('success', 'Products loaded.', $rows);
