<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../includes/auth_rate_limit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', 'Method not allowed.', null, 405);
}

// Rate limit: 10 verify attempts per IP per 15 minutes to prevent token brute-force
if (!authRateLimitByIp('verify_reset_token', 10, 900)) {
    sendResponse('error', 'Too many verification attempts. Please try again later.', null, 429);
}

$input = apiInput();
$rawToken = trim((string) ($input['token'] ?? ''));
$email = strtolower(trim((string) ($input['email'] ?? '')));
if ($rawToken === '' || $email === '') {
    sendResponse('error', 'Token and email are required.', null, 422);
}

$tokenHash = hash('sha256', $rawToken);
$stmt = $conn->prepare('SELECT prt.id, prt.expires_at, prt.used_at FROM password_reset_tokens prt JOIN users u ON u.id = prt.user_id WHERE prt.token_hash = ? AND LOWER(u.email) = ? LIMIT 1');
$stmt->bind_param('ss', $tokenHash, $email);
$stmt->execute();
$token = $stmt->get_result()->fetch_assoc();

if (!$token || $token['used_at'] !== null || strtotime($token['expires_at']) < time()) {
    sendResponse('error', 'Invalid or expired reset link.', null, 400);
}

sendResponse('success', 'Token is valid.');
