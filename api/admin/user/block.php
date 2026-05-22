<?php
require_once __DIR__ . '/../_admin.php';
validateCsrf();
$input = adminInput();
$id = (int) ($input['id'] ?? 0);
$action = strtolower(trim((string) ($input['action'] ?? 'block')));
if ($id <= 0) sendResponse('error', 'User ID is required.', null, 422);
if ($id === (int) ($_SESSION['admin_id'] ?? 0)) sendResponse('error', 'You cannot block your own account.', null, 422);
$blocked = $action === 'unblock' ? 0 : 1;
$stmt = $conn->prepare('UPDATE users SET is_blocked = ? WHERE id = ?');
$stmt->bind_param('ii', $blocked, $id);
$stmt->execute();
sendResponse('success', $blocked ? 'User blocked.' : 'User unblocked.');
