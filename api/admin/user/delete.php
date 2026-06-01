<?php
require_once __DIR__ . '/../_admin.php';
require_once __DIR__ . '/_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', 'Method not allowed.', null, 405);
}
validateCsrf();
$input = adminInput();
$id    = (int) ($input['id'] ?? 0);

if ($id <= 0) {
    sendResponse('error', 'User ID is required.', null, 422);
}
adminUserGuardSelfAction($id);

$user = adminUserFetchById($conn, $id);
if (!$user) {
    sendResponse('error', 'User not found.', null, 404);
}
adminUserGuardPrivileged($user, 'deleted');

$orderCheck = $conn->prepare('SELECT COUNT(*) AS total FROM orders WHERE user_id = ?');
$orderCheck->bind_param('i', $id);
$orderCheck->execute();
$orderCount = (int) ($orderCheck->get_result()->fetch_assoc()['total'] ?? 0);
if ($orderCount > 0) {
    sendResponse('error', 'User has orders and cannot be deleted. Block the user instead.', null, 409);
}

$stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
if ($stmt->affected_rows < 1) {
    sendResponse('error', 'User not found.', null, 404);
}
sendResponse('success', 'User deleted.');
