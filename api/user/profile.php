<?php
require_once __DIR__ . '/../_bootstrap.php';

$user = apiRequireUser();

$stmt = $conn->prepare('SELECT id, email, first_name, last_name, phone, role, created_at FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row) {
    sendResponse('error', 'Account not found.', null, 404);
}

sendResponse('success', 'Profile loaded.', [
    'id'         => (int) $row['id'],
    'email'      => $row['email'],
    'firstName'  => $row['first_name'] ?? '',
    'lastName'   => $row['last_name'] ?? '',
    'phone'      => $row['phone'] ?? '',
    'role'       => $row['role'] ?? 'customer',
    'created_at' => $row['created_at'],
]);
