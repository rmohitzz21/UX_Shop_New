<?php
require_once __DIR__ . '/../_bootstrap.php';

apiRequirePost();

$input = apiInput();

// CSRF validation
if (empty($input['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $input['csrf_token'])) {
    sendResponse('error', 'Invalid CSRF token', null, 403);
}

$email    = trim((string) ($input['email'] ?? ''));
$password = (string) ($input['password'] ?? '');

if ($email === '' || $password === '') {
    sendResponse('error', 'Email and password are required.', null, 422);
}

// Session-based rate limiting (5 attempts per 10 minutes)
if (!isset($_SESSION['admin_login_attempts'])) {
    $_SESSION['admin_login_attempts'] = 0;
    $_SESSION['admin_last_attempt_time'] = time();
}
if ($_SESSION['admin_login_attempts'] >= 5) {
    $elapsed = time() - (int) $_SESSION['admin_last_attempt_time'];
    if ($elapsed < 600) {
        sendResponse('error', 'Too many failed attempts. Please try again later.', null, 429);
    }
    $_SESSION['admin_login_attempts'] = 0;
    $_SESSION['admin_last_attempt_time'] = time();
}

$allowedRoles = ['admin', 'super_admin', 'superadmin'];

$stmt = $conn->prepare('SELECT id, email, password_hash, role FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user) {
    $normalizedRole = strtolower(trim((string) ($user['role'] ?? '')));
    if (in_array($normalizedRole, $allowedRoles, true) && password_verify($password, (string) $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_login_attempts']    = 0;
        $_SESSION['admin_last_attempt_time'] = time();
        $_SESSION['admin_id']    = (int) $user['id'];
        $_SESSION['admin_email'] = $user['email'];
        $_SESSION['role']        = $normalizedRole;
        // Rotate CSRF token on successful admin login
        $_SESSION['csrf_token']  = bin2hex(random_bytes(32));

        sendResponse('success', 'Login successful', [
            'id'         => (int) $user['id'],
            'email'      => $user['email'],
            'role'       => $normalizedRole,
            'csrf_token' => $_SESSION['csrf_token'],
        ]);
    }
}

$_SESSION['admin_login_attempts']++;
$_SESSION['admin_last_attempt_time'] = time();
sendResponse('error', 'Invalid admin credentials or access denied.', null, 401);
