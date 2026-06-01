<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../includes/auth_rate_limit.php';

apiRequirePost();
$user  = apiRequireUser();
$input = apiInput();
validateCsrf();

// Rate limit: 5 password change attempts per user per 15 minutes (prevents brute-forcing current password)
$rateLimitKey = 'change_password_' . $user['id'];
if (!authRateLimitAllow($rateLimitKey, 5, 900)) {
    sendResponse('error', 'Too many password change attempts. Please try again later.', null, 429);
}

$currentPassword = (string) ($input['current_password'] ?? '');
$newPassword     = (string) ($input['new_password'] ?? '');
$confirmPassword = (string) ($input['confirm_password'] ?? '');

if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
    sendResponse('error', 'All password fields are required.', null, 422);
}
if (strlen($newPassword) < 8) {
    sendResponse('error', 'New password must be at least 8 characters.', null, 422);
}
if (strlen($newPassword) > 128) {
    sendResponse('error', 'Password is too long.', null, 422);
}
if ($newPassword !== $confirmPassword) {
    sendResponse('error', 'New passwords do not match.', null, 422);
}

$stmt = $conn->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row || !password_verify($currentPassword, (string) $row['password_hash'])) {
    sendResponse('error', 'Current password is incorrect.', null, 401);
}

$newHash = password_hash($newPassword, PASSWORD_DEFAULT);
$update  = $conn->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
$update->bind_param('si', $newHash, $user['id']);
if (!$update->execute()) {
    sendResponse('error', 'Could not update password. Please try again.', null, 500);
}

// Invalidate all user tokens (sessions on other devices)
$revoke = $conn->prepare('DELETE FROM user_tokens WHERE user_id = ?');
$revoke->bind_param('i', $user['id']);
$revoke->execute();

sendResponse('success', 'Password updated successfully.');
