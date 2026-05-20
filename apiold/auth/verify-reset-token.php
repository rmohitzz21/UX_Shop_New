<?php
// api/auth/verify-reset-token.php
// Step 2: Validate that a reset token is valid and unexpired.
// Called by the frontend before showing the new-password form.

header('Content-Type: application/json');
require_once '../../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}

$rawToken = isset($data['token']) ? trim($data['token']) : '';
$email    = isset($data['email']) ? trim(strtolower($data['email'])) : '';

if ($rawToken === '' || $email === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Token and email are required']);
    exit;
}

$tokenHash = hash('sha256', $rawToken);

$stmt = $conn->prepare(
    "SELECT prt.id, prt.expires_at, prt.used_at
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
    echo json_encode(['status' => 'error', 'message' => 'Invalid or expired reset link']);
    exit;
}

if ($token['used_at'] !== null) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'This reset link has already been used']);
    exit;
}

if (strtotime($token['expires_at']) < time()) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Reset link has expired. Please request a new one.']);
    exit;
}

echo json_encode(['status' => 'success', 'message' => 'Token is valid']);

$conn->close();
