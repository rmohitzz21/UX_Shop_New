<?php
require_once __DIR__ . '/../includes/config.php';

$r = $conn->query("SHOW TABLES LIKE 'contact_messages'");
echo 'contact_messages table: ' . ($r && $r->num_rows ? 'OK' : 'MISSING') . PHP_EOL;
echo 'ADMIN_NOTIFICATION_EMAIL: ' . (getenv('ADMIN_NOTIFICATION_EMAIL') ?: '(not set)') . PHP_EOL;
echo 'SMTP_HOST: ' . (getenv('SMTP_HOST') ?: '(not set)') . PHP_EOL;
echo 'SMTP_USER: ' . (getenv('SMTP_USER') ?: '(not set)') . PHP_EOL;

$testEmail = 'contact-flow-test-' . time() . '@example.com';
$stmt = $conn->prepare(
    'INSERT INTO contact_messages (name, email, phone, subject, message, ip, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())'
);
$name = 'CLI Test';
$phone = '+91 9999999999';
$subject = 'Test enquiry';
$message = 'Automated contact flow DB test';
$ip = '127.0.0.1';
$stmt->bind_param('ssssss', $name, $testEmail, $phone, $subject, $message, $ip);
if (!$stmt->execute()) {
    echo 'DB insert: FAILED — ' . $stmt->error . PHP_EOL;
    exit(1);
}
$id = (int) $conn->insert_id;
echo 'DB insert: OK (id=' . $id . ')' . PHP_EOL;

$del = $conn->prepare('DELETE FROM contact_messages WHERE id = ?');
$del->bind_param('i', $id);
$del->execute();
echo 'DB cleanup: OK' . PHP_EOL;
