<?php
require_once __DIR__ . '/../_bootstrap.php';

apiRequirePost();

$input = apiInput();
if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) && !empty($input['csrf_token'])) {
    $_SERVER['HTTP_X_CSRF_TOKEN'] = (string) $input['csrf_token'];
}
validateCsrf();

$firstName = trim((string) ($input['firstName'] ?? ''));
$lastName  = trim((string) ($input['lastName'] ?? ''));
$email     = trim((string) ($input['email'] ?? ''));
$phone     = trim((string) ($input['phone'] ?? ''));
$password  = (string) ($input['password'] ?? '');

if ($firstName === '' || $email === '' || $password === '') {
    sendResponse('error', 'Name, email and password are required.', null, 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendResponse('error', 'Please enter a valid email address.', null, 422);
}

if (strlen($password) < 8) {
    sendResponse('error', 'Password must be at least 8 characters.', null, 422);
}

$stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
    sendResponse('error', 'An account with this email already exists.', null, 409);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$role = 'customer';
$stmt = $conn->prepare('INSERT INTO users (email, password_hash, first_name, last_name, phone, role, is_blocked) VALUES (?, ?, ?, ?, ?, ?, 0)');
$stmt->bind_param('ssssss', $email, $hash, $firstName, $lastName, $phone, $role);
if (!$stmt->execute()) {
    sendResponse('error', 'Could not create your account.', null, 500);
}

$userId = (int) $conn->insert_id;
sendWelcomeEmail($email, $firstName);

sendResponse('success', 'Account created successfully.', ['user_id' => $userId]);
