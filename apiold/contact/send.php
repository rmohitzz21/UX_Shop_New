<?php
// api/contact/send.php
// Handles real contact form submission — sends email via SMTP and stores message in DB.

header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

validateCsrf();

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}

// Sanitise inputs
$name    = htmlspecialchars(trim($data['name']    ?? ''), ENT_QUOTES, 'UTF-8');
$email   = trim($data['email']   ?? '');
$phone   = htmlspecialchars(trim($data['phone']   ?? ''), ENT_QUOTES, 'UTF-8');
$subject = htmlspecialchars(trim($data['subject'] ?? 'General Enquiry'), ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars(trim($data['message'] ?? ''), ENT_QUOTES, 'UTF-8');

// Validate
if (empty($name) || empty($email) || empty($message)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Name, email and message are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid email address']);
    exit;
}

if (strlen($message) > 5000) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Message is too long (max 5000 characters)']);
    exit;
}

// ── Simple IP-based rate limit: max 3 contact submissions per hour ────────────
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
// We use a simple transient in DB or session — use session-based limit for now
if (!isset($_SESSION['contact_count'])) {
    $_SESSION['contact_count'] = 0;
    $_SESSION['contact_window_start'] = time();
}
if (time() - $_SESSION['contact_window_start'] > 3600) {
    $_SESSION['contact_count'] = 0;
    $_SESSION['contact_window_start'] = time();
}
$_SESSION['contact_count']++;
if ($_SESSION['contact_count'] > 3) {
    http_response_code(429);
    echo json_encode(['status' => 'error', 'message' => 'Too many messages sent. Please try again later.']);
    exit;
}

// ── Send notification emails (to admin and user acknowledgment) ────────────────
$contactData = [
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'subject' => $subject,
    'message' => $message,
    'timestamp' => date('Y-m-d H:i:s'),
    'ip' => $ip
];

$emailsSent = sendContactEmail($contactData);

// ── Store contact message in DB (if table exists) ─────────────────────────────
// Table: contact_messages (id, name, email, phone, subject, message, ip, created_at)
$insertStmt = $conn->prepare(
    "INSERT INTO contact_messages (name, email, phone, subject, message, ip, created_at)
     VALUES (?, ?, ?, ?, ?, ?, NOW())"
);
if ($insertStmt) {
    $insertStmt->bind_param('ssssss', $name, $email, $phone, $subject, $message, $ip);
    $insertStmt->execute();
    $insertStmt->close();
}

$conn->close();

if ($emailsSent) {
    echo json_encode([
        'status'  => 'success',
        'message' => "Thank you {$name}! We've received your message and will be in touch soon."
    ]);
} else {
    // Email failed but message was stored
    echo json_encode([
        'status'  => 'success',
        'message' => "Thank you {$name}! Your message has been received."
    ]);
}
