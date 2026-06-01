<?php
require_once __DIR__ . '/../_admin.php';
require_once __DIR__ . '/_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', 'Method not allowed.', null, 405);
}
validateCsrf();
$input  = adminInput();
$id     = (int) ($input['id'] ?? 0);
$action = strtolower(trim((string) ($input['action'] ?? 'block')));

if ($id <= 0) {
    sendResponse('error', 'User ID is required.', null, 422);
}
adminUserGuardSelfAction($id);

$user = adminUserFetchById($conn, $id);
if (!$user) {
    sendResponse('error', 'User not found.', null, 404);
}
adminUserGuardPrivileged($user, 'blocked');

$blocked = $action === 'unblock' ? 0 : 1;
if ((int) ($user['is_blocked'] ?? 0) === $blocked) {
    sendResponse('success', $blocked ? 'User is already blocked.' : 'User is already active.');
}

$stmt = $conn->prepare('UPDATE users SET is_blocked = ? WHERE id = ?');
$stmt->bind_param('ii', $blocked, $id);
if (!$stmt->execute()) {
    sendResponse('error', 'Could not update user status.', null, 500);
}
sendResponse('success', $blocked ? 'User blocked.' : 'User unblocked.');
