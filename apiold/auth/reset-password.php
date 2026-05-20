<?php
// api/auth/reset-password.php
// Step 3: Accept token + new password, update user password, invalidate token.

header('Content-Type: application/json');
require_once '../../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// CSRF validation
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}

if (empty($data['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $data['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
    exit;
}

$rawToken   = isset($data['token'])    ? trim($data['token'])                 : '';
$email      = isset($data['email'])    ? trim(strtolower($data['email']))     : '';
$password   = isset($data['password']) ? $data['password']                    : '';
$confirmPwd = isset($data['confirm_password']) ? $data['confirm_password']    : '';

// Validate inputs
if ($rawToken === '' || $email === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Token and email are required']);
    exit;
}

if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters']);
    exit;
}

if ($password !== $confirmPwd) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Passwords do not match']);
    exit;
}

$tokenHash = hash('sha256', $rawToken);

// ── Fetch and validate token ──────────────────────────────────────────────────
$stmt = $conn->prepare(
    "SELECT prt.id, prt.user_id, prt.expires_at, prt.used_at
       FROM password_reset_tokens prt
       JOIN users u ON u.id = prt.user_id
      WHERE prt.token_hash = ?
        AND LOWER(u.email)  = ?
      LIMIT 1"
);
$stmt->bind_param('ss', $tokenHash, $email);
$stmt->execute();
$token = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$token) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid reset link']);
    exit;
}

if ($token['used_at'] !== null) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'This reset link has already been used']);
    exit;
}

if (strtotime($token['expires_at']) < time()) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Reset link has expired']);
    exit;
}

// ── Update password + mark token used (transaction) ──────────────────────────
$conn->begin_transaction();

try {
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    $updStmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $updStmt->bind_param('si', $hash, $token['user_id']);
    if (!$updStmt->execute()) {
        throw new Exception('Failed to update password');
    }
    $updStmt->close();

    $usedAt = date('Y-m-d H:i:s');
    $tokStmt = $conn->prepare("UPDATE password_reset_tokens SET used_at = ? WHERE id = ?");
    $tokStmt->bind_param('si', $usedAt, $token['id']);
    $tokStmt->execute();
    $tokStmt->close();

    // Invalidate any active sessions for this user
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $token['user_id']) {
        session_regenerate_id(true);
        unset($_SESSION['user_id'], $_SESSION['role']);
    }

    // Revoke all DB tokens for this user
    $revokeStmt = $conn->prepare("DELETE FROM user_tokens WHERE user_id = ?");
    $revokeStmt->bind_param('i', $token['user_id']);
    $revokeStmt->execute();
    $revokeStmt->close();

    $conn->commit();

    echo json_encode([
        'status'  => 'success',
        'message' => 'Password reset successfully. You can now sign in with your new password.'
    ]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to reset password. Please try again.']);
    error_log('reset-password error: ' . $e->getMessage());
}

$conn->close();
