<?php
require_once __DIR__ . '/../_admin.php';

$q = trim((string) ($_GET['q'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$where = [];
$params = [];
$types = '';

if ($q !== '') {
    $like = '%' . $q . '%';
    $where[] = '(name LIKE ? OR slug LIKE ? OR description LIKE ?)';
    $params = array_merge($params, [$like, $like, $like]);
    $types .= 'sss';
}
if ($status !== '' && in_array($status, ['0', '1'], true)) {
    $where[] = 'is_active = ?';
    $params[] = (int) $status;
    $types .= 'i';
}

$sql = 'SELECT c.*, COUNT(p.id) AS product_count
        FROM categories c
        LEFT JOIN products p ON p.category = c.name';
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' GROUP BY c.id ORDER BY c.sort_order ASC, c.name ASC';

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = [];
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $rows[] = $row;
sendResponse('success', 'Categories loaded.', $rows);
