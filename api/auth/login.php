<?php
require_once __DIR__ . '/../_bootstrap.php';

apiRequirePost();

$input = apiInput();
if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) && !empty($input['csrf_token'])) {
    $_SERVER['HTTP_X_CSRF_TOKEN'] = (string) $input['csrf_token'];
}
validateCsrf();

$email    = trim((string) ($input['email'] ?? ''));
$password = (string) ($input['password'] ?? '');

if ($email === '' || $password === '') {
    sendResponse('error', 'Email and password are required.', null, 422);
}

$stmt = $conn->prepare('SELECT id, email, password_hash, first_name, last_name, phone, role, is_blocked FROM users WHERE email = ? ORDER BY id DESC LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || !password_verify($password, (string) $user['password_hash'])) {
    sendResponse('error', 'Invalid email or password.', null, 401);
}

if ((int) ($user['is_blocked'] ?? 0) === 1) {
    sendResponse('error', 'This account is blocked. Please contact support.', null, 403);
}

session_regenerate_id(true);

$_SESSION['user_id']    = (int) $user['id'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['first_name'] = $user['first_name'] ?? '';
$_SESSION['last_name']  = $user['last_name'] ?? '';
$_SESSION['user_role']  = $user['role'] ?? 'customer';
$_SESSION['phone']      = $user['phone'] ?? '';

// Rotate CSRF token on login to prevent CSRF fixation
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

sendResponse('success', 'Signed in successfully.', [
    'user' => [
        'id'        => (int) $user['id'],
        'email'     => $user['email'],
        'firstName' => $user['first_name'] ?? '',
        'lastName'  => $user['last_name'] ?? '',
        'role'      => $user['role'] ?? 'customer',
    ],
    'csrf_token' => $_SESSION['csrf_token'],
]);
