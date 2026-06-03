<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../includes/auth_rate_limit.php';

apiRequirePost();

if (!authRateLimitCheck('signup', 5, 10, 3600)) {
    sendResponse('error', 'Too many signup attempts. Please try again later.', null, 429);
}

$input = apiInput();
if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) && !empty($input['csrf_token'])) {
    $_SERVER['HTTP_X_CSRF_TOKEN'] = (string) $input['csrf_token'];
}
validateCsrf();

$firstName = trim((string) ($input['firstName'] ?? ''));
$lastName  = trim((string) ($input['lastName'] ?? ''));
$fullName  = trim((string) ($input['fullName'] ?? ''));

if ($firstName === '' && $fullName !== '') {
    $parts     = preg_split('/\s+/', $fullName, 2);
    $firstName = $parts[0] ?? '';
    $lastName  = $parts[1] ?? '';
}

$email    = strtolower(trim((string) ($input['email'] ?? '')));
$phone    = trim((string) ($input['phone'] ?? ''));
$password = (string) ($input['password'] ?? '');

if ($firstName === '' || $email === '' || $password === '') {
    sendResponse('error', 'Name, email and password are required.', null, 422);
}

if (strlen($firstName) > 100 || strlen($lastName) > 100) {
    sendResponse('error', 'Name is too long.', null, 422);
}

if (strlen($phone) > 40) {
    sendResponse('error', 'Phone number is too long.', null, 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendResponse('error', 'Please enter a valid email address.', null, 422);
}

if (strlen($email) > 255) {
    sendResponse('error', 'Email is too long.', null, 422);
}

if (strlen($password) < 8) {
    sendResponse('error', 'Password must be at least 8 characters.', null, 422);
}

if (strlen($password) > 128) {
    sendResponse('error', 'Password is too long.', null, 422);
}

$stmt = $conn->prepare('SELECT id FROM users WHERE LOWER(email) = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
    sendResponse('error', 'An account with this email already exists.', null, 409);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$role = 'customer';
$stmt = $conn->prepare(
    'INSERT INTO users (email, password_hash, first_name, last_name, phone, role, is_blocked) VALUES (?, ?, ?, ?, ?, ?, 0)'
);
if (!$stmt) {
    error_log('api/auth/signup.php prepare insert: ' . $conn->error);
    sendResponse('error', 'Could not create your account.', null, 500);
}

$stmt->bind_param('ssssss', $email, $hash, $firstName, $lastName, $phone, $role);
if (!$stmt->execute()) {
    error_log('api/auth/signup.php insert: ' . $stmt->error);
    sendResponse('error', 'Could not create your account.', null, 500);
}

$userId = (int) $conn->insert_id;
try {
    require_once __DIR__ . '/../../includes/EmailService.php';
    EmailService::sendWelcome(['email' => $email, 'first_name' => $firstName]);
} catch (Throwable $e) {
    error_log('api/auth/signup.php welcome email: ' . $e->getMessage());
}

session_regenerate_id(true);

$_SESSION['user_id']    = $userId;
$_SESSION['user_email'] = $email;
$_SESSION['first_name'] = $firstName;
$_SESSION['last_name']  = $lastName;
$_SESSION['user_role']  = $role;
$_SESSION['phone']      = $phone;
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

sendResponse('success', 'Account created successfully.', [
    'user_id' => $userId,
    'user' => [
        'id'        => $userId,
        'email'     => $email,
        'firstName' => $firstName,
        'lastName'  => $lastName,
        'role'      => $role,
    ],
    'csrf_token' => $_SESSION['csrf_token'],
    'auto_login' => true,
]);
