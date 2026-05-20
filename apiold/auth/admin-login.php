<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    sendResponse("error", "Invalid input", null, 400);
}

// CSRF validation
if (empty($input['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $input['csrf_token'])) {
    sendResponse("error", "Invalid CSRF token", null, 403);
}

$email = trim((string)($input['email'] ?? ''));
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    sendResponse("error", "Email and password are required", null, 400);
}

// Rate Limiting
if (!isset($_SESSION['admin_login_attempts'])) {
    $_SESSION['admin_login_attempts'] = 0;
    $_SESSION['admin_last_attempt_time'] = time();
}

if ($_SESSION['admin_login_attempts'] >= 5) {
    $time_since_last_attempt = time() - $_SESSION['admin_last_attempt_time'];
    if ($time_since_last_attempt < 600) { // 10 minutes lock
        sendResponse("error", "Too many failed attempts. Please try again later.", null, 429);
    } else {
        // Reset after timeout
        $_SESSION['admin_login_attempts'] = 0;
        $_SESSION['admin_last_attempt_time'] = time();
    }
}

// Find user by email first, then check role
$stmt = $conn->prepare("SELECT id, email, password_hash, role FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    $normalizedRole = strtolower(trim((string)($user['role'] ?? '')));
    $allowedAdminRoles = ['admin', 'super_admin', 'superadmin'];

    if (!in_array($normalizedRole, $allowedAdminRoles, true)) {
        $_SESSION['admin_login_attempts']++;
        $_SESSION['admin_last_attempt_time'] = time();
        error_log('Admin login denied for non-admin role: ' . ($user['role'] ?? 'unknown'));
        $stmt->close();
        $conn->close();
        sendResponse("error", "Invalid admin credentials or access denied", null, 401);
    }

    if (password_verify($password, $user['password_hash'])) {
        $stmt->close();
        $conn->close();

        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        // Reset attempts on success
        $_SESSION['admin_login_attempts'] = 0;
        $_SESSION['admin_last_attempt_time'] = time();

        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_email'] = $user['email'];
        $_SESSION['role'] = $normalizedRole;

        sendResponse("success", "Login successful", [
            "id" => $user['id'],
            "email" => $user['email'],
            "role" => $user['role']
        ]);
    }
}

$_SESSION['admin_login_attempts']++;
$_SESSION['admin_last_attempt_time'] = time();
$stmt->close();
$conn->close();
sendResponse("error", "Invalid admin credentials or access denied", null, 401);
?>
