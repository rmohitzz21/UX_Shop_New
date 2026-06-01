<?php
require_once __DIR__ . '/../_admin.php';

$q      = trim((string) ($_GET['q'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));

$where  = ['archived = 0'];
$params = [];
$types  = '';

if ($q !== '') {
    $like    = '%' . $q . '%';
    $where[] = '(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)';
    $params  = array_merge($params, [$like, $like, $like, $like]);
    $types  .= 'ssss';
}
if ($status === '1' || $status === '0') {
    $where[] = 'is_read = ?';
    $params[] = (int) $status;
    $types   .= 'i';
}

$sql = 'SELECT id, name, email, phone, subject, message, is_read, archived, created_at
        FROM contact_messages
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY is_read ASC, created_at DESC
        LIMIT 500';

$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log('api/admin/messages/list.php prepare: ' . $conn->error);
    sendResponse('error', 'Could not load messages.', null, 500);
}
if ($params) {
    $stmt->bind_param($types, ...$params);
}
if (!$stmt->execute()) {
    error_log('api/admin/messages/list.php execute: ' . $stmt->error);
    sendResponse('error', 'Could not load messages.', null, 500);
}

$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
sendResponse('success', 'Messages loaded.', $rows);
