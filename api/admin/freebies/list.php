<?php
require_once __DIR__ . '/../_admin.php';
require_once __DIR__ . '/_helpers.php';

adminEnsureFreebiesTable($conn);

$q      = trim((string) ($_GET['q'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$where  = [];
$params = [];
$types  = '';

if ($q !== '') {
    $like     = '%' . $q . '%';
    $where[]  = '(name LIKE ? OR slug LIKE ? OR description LIKE ? OR category LIKE ?)';
    $params   = array_merge($params, [$like, $like, $like, $like]);
    $types   .= 'ssss';
}
if ($status !== '' && in_array($status, ['0', '1'], true)) {
    $where[]  = 'is_active = ?';
    $params[] = (int) $status;
    $types   .= 'i';
}

$sql = 'SELECT * FROM freebies';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY sort_order ASC, is_featured DESC, id DESC';

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$rows = [];
$res  = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $rows[] = $row;
}
sendResponse('success', 'Freebies loaded.', $rows);
