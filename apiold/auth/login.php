<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    sendResponse("error", "Invalid input", null, 400);
}

$email = $input['email'] ?? '';
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    sendResponse("error", "Email and password are required", null, 400);
}

// Rate Limiting
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = time();
}

if ($_SESSION['login_attempts'] >= 5) {
    $time_since_last_attempt = time() - $_SESSION['last_attempt_time'];
    if ($time_since_last_attempt < 600) { // 10 minutes lock
        sendResponse("error", "Too many failed attempts. Please try again in " . ceil((600 - $time_since_last_attempt) / 60) . " minutes.", null, 429);
    } else {
        // Reset after timeout
        $_SESSION['login_attempts'] = 0;
        $_SESSION['last_attempt_time'] = time();
    }
}

// CSRF Check - enforce token validation
$csrf_token = $input['csrf_token'] ?? $_POST['csrf_token'] ?? '';
if (empty($csrf_token) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
   sendResponse("error", "Invalid or missing CSRF token", null, 403);
}

$stmt = $conn->prepare("SELECT id, email, password_hash, first_name, last_name, role, is_blocked FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    if ($user['is_blocked'] == 1) {
        sendResponse("error", "Your account has been blocked. Please contact support.", null, 403);
    }

    if (password_verify($password, $user['password_hash'])) {
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        // Reset login attempts on success
        $_SESSION['login_attempts'] = 0;

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['username'] = $user['first_name'] . ' ' . $user['last_name'];
        
        // Generate token for client-side session tracking
        $token = bin2hex(random_bytes(32));

        // Store refresh token in DB
        $expires_at = date('Y-m-d H:i:s', time() + 86400 * 7); // 7 days
        $tokenStmt = $conn->prepare("INSERT INTO user_tokens (user_id, refresh_token, expires_at) VALUES (?, ?, ?)");
        if ($tokenStmt) {
            $tokenStmt->bind_param("iss", $user['id'], $token, $expires_at);
            $tokenStmt->execute();
            $tokenStmt->close();
        }

        sendResponse("success", "Login successful", [
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'firstName' => $user['first_name'],
                'lastName' => $user['last_name'],
                'role' => $user['role']
            ],
            'tokens' => [
                'access_token' => $token,
                'refresh_token' => $token
            ]
        ]);
    } else {
        $_SESSION['login_attempts']++;
        $_SESSION['last_attempt_time'] = time();
        sendResponse("error", "Invalid credentials", null, 401);
    }
} else {
    $_SESSION['login_attempts']++;
    $_SESSION['last_attempt_time'] = time();
    sendResponse("error", "Invalid credentials", null, 401);
}

$stmt->close();
$conn->close();
?>