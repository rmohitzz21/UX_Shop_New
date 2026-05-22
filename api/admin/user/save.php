<?php
require_once __DIR__ . '/../_admin.php';
validateCsrf();
$input = adminInput();
$id = (int) ($input['id'] ?? 0);
if ($id <= 0) sendResponse('error', 'User ID is required.', null, 422);
$first = trim((string) ($input['first_name'] ?? $input['firstName'] ?? ''));
$last = trim((string) ($input['last_name'] ?? $input['lastName'] ?? ''));
$phone = trim((string) ($input['phone'] ?? ''));
$role = trim((string) ($input['role'] ?? 'customer'));
if (!in_array($role, ['customer','admin'], true)) $role = 'customer';
$stmt = $conn->prepare('UPDATE users SET first_name = ?, last_name = ?, phone = ?, role = ? WHERE id = ?');
$stmt->bind_param('ssssi', $first, $last, $phone, $role, $id);
$stmt->execute();
sendResponse('success', 'User updated.');
