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

$user = adminUserFetchById($conn, $id);
if (!$user) {
    sendResponse('error', 'User not found.', null, 404);
}

$first = trim((string) ($input['first_name'] ?? $input['firstName'] ?? ''));
$last  = trim((string) ($input['last_name']  ?? $input['lastName']  ?? ''));
$phone = trim((string) ($input['phone'] ?? ''));
$role  = strtolower(trim((string) ($input['role'] ?? 'customer')));

if (adminUserIsPrivileged($user['role'] ?? '')) {
    sendResponse('error', 'Admin accounts cannot be edited from the customer users panel.', null, 403);
}

if (!in_array($role, ['customer'], true)) {
    $role = 'customer';
}

$stmt = $conn->prepare('UPDATE users SET first_name = ?, last_name = ?, phone = ?, role = ? WHERE id = ?');
$stmt->bind_param('ssssi', $first, $last, $phone, $role, $id);
if (!$stmt->execute()) {
    sendResponse('error', 'Could not update user.', null, 500);
}
sendResponse('success', 'User updated.');
