<?php
require_once __DIR__ . '/../_admin.php';

$q = trim((string) ($_GET['q'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));

// Ensure columns exist (migration guard — safe to run every time)
$conn->query("ALTER TABLE contact_messages ADD COLUMN IF NOT EXISTS is_read TINYINT(1) NOT NULL DEFAULT 0");
$conn->query("ALTER TABLE contact_messages ADD COLUMN IF NOT EXISTS archived TINYINT(1) NOT NULL DEFAULT 0");

$where = ['archived = 0'];
$params = [];
$types = '';

if ($q !== '') {
    $like = '%' . $q . '%';
    $where[] = '(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)';
    $params = array_merge($params, [$like, $like, $like, $like]);
    $types .= 'ssss';
}
if ($status === '1' || $status === '0') {
    $where[] = 'is_read = ?';
    $params[] = (int) $status;
    $types .= 'i';
}

$sql = 'SELECT id, name, email, phone, subject, message, is_read, archived, created_at
        FROM contact_messages
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY is_read ASC, created_at DESC
        LIMIT 100';

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = [];
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $rows[] = $row;
sendResponse('success', 'Messages loaded.', $rows);
