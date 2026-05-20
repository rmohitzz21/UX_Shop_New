<?php
// api/auth/logout.php
// Load config first — ensures consistent session cookie params (HttpOnly, SameSite=Lax)
require_once '../../includes/config.php';

header('Content-Type: application/json');

// Revoke the server-side token if stored
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $delStmt = $conn->prepare("DELETE FROM user_tokens WHERE user_id = ?");
    if ($delStmt) {
        $delStmt->bind_param('i', $user_id);
        $delStmt->execute();
        $delStmt->close();
    }
}
if (isset($conn)) $conn->close();

// Destroy session
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 86400,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();

echo json_encode(['status' => 'success', 'message' => 'Logged out successfully']);
