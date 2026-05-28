<?php
require_once __DIR__ . '/../_admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', 'Method not allowed.', null, 405);
}
validateCsrf();
$input = adminInput();
$id    = (int) ($input['id'] ?? 0);

if ($id <= 0) {
    sendResponse('error', 'User ID is required.', null, 422);
}

$first = trim((string) ($input['first_name'] ?? $input['firstName'] ?? ''));
$last  = trim((string) ($input['last_name']  ?? $input['lastName']  ?? ''));
$phone = trim((string) ($input['phone'] ?? ''));
$role  = trim((string) ($input['role'] ?? 'customer'));

if (!in_array($role, ['customer', 'admin', 'super_admin'], true)) {
    $role = 'customer';
}

$stmt = $conn->prepare('UPDATE users SET first_name = ?, last_name = ?, phone = ?, role = ? WHERE id = ?');
$stmt->bind_param('ssssi', $first, $last, $phone, $role, $id);
$stmt->execute();
if ($stmt->affected_rows < 1) {
    sendResponse('error', 'User not found or no changes made.', null, 404);
}
sendResponse('success', 'User updated.');
