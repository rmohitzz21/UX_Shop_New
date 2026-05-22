<?php
require_once __DIR__ . '/../_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', 'Method not allowed.', null, 405);
}

$input = apiInput();
if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) && !empty($input['csrf_token'])) {
    $_SERVER['HTTP_X_CSRF_TOKEN'] = (string) $input['csrf_token'];
}
validateCsrf();

$rawToken = trim((string) ($input['token'] ?? ''));
$email = strtolower(trim((string) ($input['email'] ?? '')));
$password = (string) ($input['password'] ?? '');
$confirm = (string) ($input['confirm_password'] ?? '');

if ($rawToken === '' || $email === '') {
    sendResponse('error', 'Token and email are required.', null, 422);
}
if (strlen($password) < 8) {
    sendResponse('error', 'Password must be at least 8 characters.', null, 422);
}
if ($password !== $confirm) {
    sendResponse('error', 'Passwords do not match.', null, 422);
}

$tokenHash = hash('sha256', $rawToken);
$stmt = $conn->prepare('SELECT prt.id, prt.user_id, prt.expires_at, prt.used_at FROM password_reset_tokens prt JOIN users u ON u.id = prt.user_id WHERE prt.token_hash = ? AND LOWER(u.email) = ? LIMIT 1');
$stmt->bind_param('ss', $tokenHash, $email);
$stmt->execute();
$token = $stmt->get_result()->fetch_assoc();
if (!$token || $token['used_at'] !== null || strtotime($token['expires_at']) < time()) {
    sendResponse('error', 'Invalid or expired reset link.', null, 400);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$conn->begin_transaction();
try {
    $update = $conn->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $update->bind_param('si', $hash, $token['user_id']);
    if (!$update->execute()) {
        throw new RuntimeException($update->error);
    }

    $usedAt = date('Y-m-d H:i:s');
    $mark = $conn->prepare('UPDATE password_reset_tokens SET used_at = ? WHERE id = ?');
    $mark->bind_param('si', $usedAt, $token['id']);
    $mark->execute();

    $revoke = $conn->prepare('DELETE FROM user_tokens WHERE user_id = ?');
    $revoke->bind_param('i', $token['user_id']);
    $revoke->execute();

    $conn->commit();
    sendResponse('success', 'Password reset successfully. You can now sign in with your new password.');
} catch (Throwable $e) {
    $conn->rollback();
    error_log('api/auth/reset-password.php: ' . $e->getMessage());
    sendResponse('error', 'Failed to reset password. Please try again.', null, 500);
}
