<?php
require_once __DIR__ . '/../_bootstrap.php';

$adminId = (int) ($_SESSION['admin_id'] ?? 0);
if ($adminId <= 0) {
    sendResponse('success', 'Not authenticated.', ['authenticated' => false]);
}

sendResponse('success', 'Authenticated.', [
    'authenticated' => true,
    'id'            => $adminId,
    'email'         => $_SESSION['admin_email'] ?? '',
    'role'          => $_SESSION['role'] ?? 'admin',
]);
