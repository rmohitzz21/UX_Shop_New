<?php
// api/auth/forgot-password.php
// Step 1: Accept email, generate secure token, send reset link via SMTP.

header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../core/Mailer.php';

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

$email = isset($data['email']) ? trim(strtolower($data['email'])) : '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid email address']);
    exit;
}

// ── Rate limiting: max 3 requests per email per hour ─────────────────────────
$rateStmt = $conn->prepare(
    "SELECT COUNT(*) AS cnt FROM password_reset_tokens
      WHERE user_id = (SELECT id FROM users WHERE LOWER(email) = ? LIMIT 1)
        AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"
);
$rateStmt->bind_param('s', $email);
$rateStmt->execute();
$rateRow = $rateStmt->get_result()->fetch_assoc();
$rateStmt->close();

if ($rateRow && $rateRow['cnt'] >= 3) {
    // Return success to avoid email enumeration, but don't send
    echo json_encode(['status' => 'success', 'message' => 'If that email is registered, a reset link has been sent.']);
    exit;
}

// ── Look up user ──────────────────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT id, email FROM users WHERE LOWER(email) = ? LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Always respond the same way to prevent email enumeration
if (!$user) {
    echo json_encode(['status' => 'success', 'message' => 'If that email is registered, a reset link has been sent.']);
    exit;
}

// ── Generate token ────────────────────────────────────────────────────────────
$rawToken  = bin2hex(random_bytes(32));          // 64-char hex string
$tokenHash = hash('sha256', $rawToken);          // Store hash, not raw token
$expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour

// Invalidate any previous unused tokens for this user
$delStmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE user_id = ? AND used_at IS NULL");
$delStmt->bind_param('i', $user['id']);
$delStmt->execute();
$delStmt->close();

// Insert new token
$insStmt = $conn->prepare(
    "INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)"
);
$insStmt->bind_param('iss', $user['id'], $tokenHash, $expiresAt);
$insStmt->execute();
$insStmt->close();

// ── Send email ────────────────────────────────────────────────────────────────
$appUrl    = rtrim(getenv('APP_URL') ?: 'http://localhost/ux/Ux-Merchandise', '/');
$resetLink = $appUrl . '/reset-password.php?token=' . urlencode($rawToken) . '&email=' . urlencode($user['email']);

$subject = 'Reset your UX Pacific Shop password';
$html = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family:Arial,sans-serif;background:#f4f4f4;padding:20px">
  <div style="max-width:520px;margin:0 auto;background:#fff;border-radius:8px;padding:32px">
    <h2 style="color:#1a1a2e;margin-bottom:8px">Password Reset Request</h2>
    <p style="color:#555">We received a request to reset the password for your UX Pacific Shop account.</p>
    <p style="color:#555">Click the button below to choose a new password. This link expires in <strong>1 hour</strong>.</p>
    <div style="text-align:center;margin:28px 0">
      <a href="{$resetLink}"
         style="background:#e94560;color:#fff;padding:14px 28px;border-radius:6px;text-decoration:none;font-weight:bold;display:inline-block">
        Reset Password
      </a>
    </div>
    <p style="color:#888;font-size:13px">If you didn't request this, you can safely ignore this email — your password won't change.</p>
    <hr style="border:none;border-top:1px solid #eee;margin:24px 0">
    <p style="color:#aaa;font-size:12px">UX Pacific Shop &bull; This is an automated message, please do not reply.</p>
  </div>
</body>
</html>
HTML;

try {
    $mailer = new Mailer();
    $mailer->send($user['email'], $subject, $html);
} catch (Exception $e) {
    // Log the error but don't expose details to client
    error_log('Mailer error (forgot-password): ' . $e->getMessage());
}

echo json_encode([
    'status'  => 'success',
    'message' => 'If that email is registered, a reset link has been sent.'
]);

$conn->close();
