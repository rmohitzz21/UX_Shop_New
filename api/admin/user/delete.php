<?php
require_once __DIR__ . '/../_admin.php';
validateCsrf();
$input = adminInput();
$id = (int) ($input['id'] ?? 0);
if ($id <= 0) sendResponse('error', 'User ID is required.', null, 422);
if ($id === (int) ($_SESSION['admin_id'] ?? 0)) sendResponse('error', 'You cannot delete your own account.', null, 422);
$stmt = $conn->prepare('DELETE FROM users WHERE id = ? AND NOT EXISTS (SELECT 1 FROM orders WHERE user_id = ?)');
$stmt->bind_param('ii', $id, $id);
$stmt->execute();
if ($stmt->affected_rows < 1) sendResponse('error', 'User has orders and cannot be deleted. Block the user instead.', null, 409);
sendResponse('success', 'User deleted.');
