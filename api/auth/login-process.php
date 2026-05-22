<?php
require_once __DIR__ . '/../_bootstrap.php';

// Validate CSRF token
$csrf_token = (string) ($_POST['csrf_token'] ?? '');
if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf_token)) {
    $_SESSION['error'] = 'Security token mismatch. Please try again.';
    header('Location: ../../signin.php');
    exit;
}

// Get email and password from POST
$email = trim((string) ($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

if ($email === '' || $password === '') {
    $_SESSION['error'] = 'Email and password are required.';
    header('Location: ../../signin.php');
    exit;
}

// Query user
$stmt = $conn->prepare('SELECT id, email, password_hash, first_name, last_name, role, is_blocked FROM users WHERE email = ? ORDER BY id DESC LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || !password_verify($password, (string) $user['password_hash'])) {
    $_SESSION['error'] = 'Invalid email or password.';
    header('Location: ../../signin.php');
    exit;
}

if ((int) ($user['is_blocked'] ?? 0) === 1) {
    $_SESSION['error'] = 'This account is blocked. Please contact support.';
    header('Location: ../../signin.php');
    exit;
}

// Regenerate session and set user data
session_regenerate_id(true);
$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['first_name'] = $user['first_name'] ?? '';
$_SESSION['last_name'] = $user['last_name'] ?? '';
$_SESSION['user_role'] = $user['role'] ?? 'customer';

// Clear any error messages
if (isset($_SESSION['error'])) {
    unset($_SESSION['error']);
}

// Redirect to home page (relative to /UX_Shop_New/)
header('Location: ../../index.php');
exit;
