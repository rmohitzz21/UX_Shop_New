<?php
require_once __DIR__ . '/../_admin.php';
require_once __DIR__ . '/_helpers.php';

$q              = trim((string) ($_GET['q'] ?? ''));
$blockedFilter  = trim((string) ($_GET['blocked'] ?? ''));
$includeAdmins  = trim((string) ($_GET['include_admins'] ?? '')) === '1';

$where  = [];
$params = [];
$types  = '';

if (!$includeAdmins) {
    $privileged = adminUserPrivilegedRoles();
    $placeholders = implode(',', array_fill(0, count($privileged), '?'));
    $where[] = "LOWER(u.role) NOT IN ({$placeholders})";
    foreach ($privileged as $role) {
        $params[] = strtolower($role);
        $types .= 's';
    }
}

if ($q !== '') {
    $like = '%' . $q . '%';
    $where[] = '(u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.phone LIKE ?)';
    array_push($params, $like, $like, $like, $like);
    $types .= 'ssss';
}

if ($blockedFilter !== '' && in_array($blockedFilter, ['0', '1'], true)) {
    $where[] = 'u.is_blocked = ?';
    $params[] = (int) $blockedFilter;
    $types .= 'i';
}

$sql = "SELECT u.id, u.email, u.first_name, u.last_name, u.phone, u.role, u.is_blocked, u.created_at,
        COUNT(o.id) AS order_count, COALESCE(SUM(o.total), 0) AS lifetime_value
        FROM users u
        LEFT JOIN orders o ON o.user_id = u.id";
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' GROUP BY u.id ORDER BY u.created_at DESC, u.id DESC';

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
sendResponse('success', 'Users loaded.', $rows);
