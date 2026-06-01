<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../includes/auth_rate_limit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', 'Method not allowed.', null, 405);
}

$input = apiInput();
if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) && !empty($input['csrf_token'])) {
    $_SERVER['HTTP_X_CSRF_TOKEN'] = (string) $input['csrf_token'];
}
validateCsrf();

$name = trim((string) ($input['name'] ?? ''));
$email = strtolower(trim((string) ($input['email'] ?? '')));
$phone = preg_replace('/[^\d+\-\s()]/', '', trim((string) ($input['phone'] ?? '')));
$subject = trim((string) ($input['subject'] ?? 'General enquiry'));
$message = trim((string) ($input['message'] ?? ''));

if ($name === '' || $email === '' || $message === '') {
    sendResponse('error', 'Name, email and message are required.', null, 422);
}
if (strlen($name) > 100) {
    sendResponse('error', 'Name is too long.', null, 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
    sendResponse('error', 'Please enter a valid email address.', null, 422);
}
if (strlen($subject) > 200) {
    sendResponse('error', 'Subject is too long.', null, 422);
}
if (strlen($phone) > 30) {
    sendResponse('error', 'Phone number is too long.', null, 422);
}
if (strlen($message) > 5000) {
    sendResponse('error', 'Message is too long. Please keep it under 5000 characters.', null, 422);
}

// Session-based rate limiting: 3 per hour
if (!isset($_SESSION['contact_count'], $_SESSION['contact_window_start']) || time() - (int) $_SESSION['contact_window_start'] > 3600) {
    $_SESSION['contact_count'] = 0;
    $_SESSION['contact_window_start'] = time();
}
$_SESSION['contact_count']++;
if ($_SESSION['contact_count'] > 3) {
    sendResponse('error', 'Too many messages sent. Please try again later.', null, 429);
}

// IP-based rate limiting: 5 per hour (prevents session bypass)
if (!authRateLimitByIp('contact_form', 5, 3600)) {
    sendResponse('error', 'Too many messages from your network. Please try again later.', null, 429);
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$stmt = $conn->prepare('INSERT INTO contact_messages (name, email, phone, subject, message, ip, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
if (!$stmt) {
    error_log('api/contact/send.php prepare failed: ' . $conn->error);
    sendResponse('error', 'Could not save your message.', null, 500);
}
$stmt->bind_param('ssssss', $name, $email, $phone, $subject, $message, $ip);
if (!$stmt->execute()) {
    error_log('api/contact/send.php insert failed: ' . $stmt->error);
    sendResponse('error', 'Could not save your message.', null, 500);
}

// Send notification email (non-fatal — message is saved to DB regardless)
sendContactEmail(['name' => $name, 'email' => $email, 'phone' => $phone, 'subject' => $subject, 'message' => $message]);

sendResponse('success', "Thank you {$name}. Your message has been received.");
