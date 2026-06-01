<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../includes/auth_rate_limit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', 'Method not allowed.', null, 405);
}

$input = apiInput();
if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) && !empty($input['csrf_token'])) {
    $_SERVER['HTTP_X_CSRF_TOKEN'] = (string) $input['csrf_token'];
}
validateCsrf();

// IP-based rate limit: 5 forgot-password requests per IP per hour
if (!authRateLimitByIp('forgot_password', 5, 3600)) {
    sendResponse('error', 'Too many reset requests. Please try again later.', null, 429);
}

$email = strtolower(trim((string) ($input['email'] ?? '')));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendResponse('error', 'Please enter a valid email address.', null, 422);
}

$generic = 'If that email is registered, a reset link has been sent.';

$stmt = $conn->prepare('SELECT id, email, first_name FROM users WHERE LOWER(email) = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user) {
    sendResponse('success', $generic);
}

// Rate-limit: max 3 reset requests per hour per user
$rate = $conn->prepare('SELECT COUNT(*) AS c FROM password_reset_tokens WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)');
$rate->bind_param('i', $user['id']);
$rate->execute();
if ((int) ($rate->get_result()->fetch_assoc()['c'] ?? 0) >= 3) {
    sendResponse('success', $generic);
}

$rawToken  = bin2hex(random_bytes(32));
$tokenHash = hash('sha256', $rawToken);
$expiresAt = date('Y-m-d H:i:s', time() + 3600);

$conn->begin_transaction();
try {
    $delete = $conn->prepare('DELETE FROM password_reset_tokens WHERE user_id = ? AND used_at IS NULL');
    $delete->bind_param('i', $user['id']);
    $delete->execute();

    $insert = $conn->prepare('INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)');
    $insert->bind_param('iss', $user['id'], $tokenHash, $expiresAt);
    if (!$insert->execute()) {
        throw new RuntimeException($insert->error);
    }
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    error_log('api/auth/forgot-password.php: ' . $e->getMessage());
    sendResponse('error', 'Could not create reset link. Please try again.', null, 500);
}

$appUrl = rtrim((string) (getenv('APP_URL') ?: ''), '/');
if ($appUrl === '') {
    $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = rtrim(str_replace('\\', '/', dirname(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')))), '/');
    $appUrl   = $scheme . '://' . $host . $basePath;
}
$resetLink = $appUrl . '/reset-password.php?token=' . urlencode($rawToken) . '&email=' . urlencode($user['email']);

// Send reset email — failure is non-fatal (link also logged for dev/fallback)
$emailSent = sendPasswordResetEmail((string) $user['email'], (string) ($user['first_name'] ?? ''), $resetLink);
if (!$emailSent) {
    error_log('Password reset link for ' . $user['email'] . ': ' . $resetLink);
}

sendResponse('success', $generic);
