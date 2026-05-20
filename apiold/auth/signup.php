<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';

// SEC-13: Rate limit signup — max 5 attempts per IP per hour
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$rateLimitKey = 'signup_rl_' . md5($ip);
if (!isset($_SESSION[$rateLimitKey])) {
    $_SESSION[$rateLimitKey] = ['count' => 0, 'window_start' => time()];
}
if (time() - $_SESSION[$rateLimitKey]['window_start'] > 3600) {
    $_SESSION[$rateLimitKey] = ['count' => 0, 'window_start' => time()];
}
$_SESSION[$rateLimitKey]['count']++;
if ($_SESSION[$rateLimitKey]['count'] > 5) {
    http_response_code(429);
    echo json_encode(['status' => 'error', 'message' => 'Too many signup attempts. Please try again later.']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit;
}

// CSRF validation
if (empty($input['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $input['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
    exit;
}

$email = $input['email'] ?? '';
$password = $input['password'] ?? '';
$firstName = $input['firstName'] ?? '';
$lastName = $input['lastName'] ?? '';
$phone = $input['phone'] ?? '';

// Handle fullName if provided (splitting logic)
if (empty($firstName) && !empty($input['fullName'])) {
    $parts = explode(' ', trim($input['fullName']), 2);
    $firstName = $parts[0];
    $lastName = $parts[1] ?? '';
}

// Basic validation
if (empty($email) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Email and password are required']);
    exit;
}

// Email format validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email format']);
    exit;
}

// Password strength validation
if (strlen($password) < 8) {
    echo json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters']);
    exit;
}

// Name validation
if (empty($firstName) || strlen(trim($firstName)) < 2) {
    echo json_encode(['status' => 'error', 'message' => 'First name is required (at least 2 characters)']);
    exit;
}

// Check if email exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Email already exists']);
    exit;
}
$stmt->close();

// Hash password
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// Insert user
$stmt = $conn->prepare("INSERT INTO users (email, password_hash, first_name, last_name, phone, role) VALUES (?, ?, ?, ?, ?, 'customer')");
$stmt->bind_param("sssss", $email, $passwordHash, $firstName, $lastName, $phone);

if ($stmt->execute()) {
    // Get the new user's ID
    $userId = $conn->insert_id;
    
    // Send welcome email
    sendWelcomeEmail($email, $firstName . ' ' . $lastName);
    
    echo json_encode(['status' => 'success', 'message' => 'Account created successfully! Please check your email for a welcome message.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Registration failed. Please try again.']);
}

$stmt->close();
$conn->close();
?>