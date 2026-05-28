<?php
require_once __DIR__ . '/../_admin.php';

// Ensure table exists
$conn->query("CREATE TABLE IF NOT EXISTS freebies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100) DEFAULT 'General',
    image VARCHAR(500) DEFAULT '',
    file_url VARCHAR(500) DEFAULT '',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    download_count INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

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
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY sort_order ASC, is_featured DESC, id DESC';

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = [];
$res  = $stmt->get_result();
while ($row = $res->fetch_assoc()) $rows[] = $row;
sendResponse('success', 'Freebies loaded.', $rows);
