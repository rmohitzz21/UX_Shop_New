<?php
/**
 * CLI smoke test for admin messages API (list + update).
 * Usage: php scripts/test-admin-messages-api.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/includes/config.php';

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "Run from CLI only.\n");
    exit(1);
}

$failures = 0;
$pass = static function (string $label, bool $ok) use (&$failures): void {
    echo ($ok ? '[PASS] ' : '[FAIL] ') . $label . PHP_EOL;
    if (!$ok) {
        $failures++;
    }
};

// Ensure schema columns exist
marketplaceEnsureSchema($conn);

// Seed test message
$testEmail = 'messages-api-test+' . time() . '@example.com';
$stmt = $conn->prepare(
    'INSERT INTO contact_messages (name, email, phone, subject, message, ip, is_read, archived, created_at)
     VALUES (?, ?, ?, ?, ?, ?, 0, 0, NOW())'
);
$name = 'API Test User';
$phone = '+910000000000';
$subject = 'Admin messages API test';
$message = 'Automated test message ' . date('c');
$ip = '127.0.0.1';
$stmt->bind_param('ssssss', $name, $testEmail, $phone, $subject, $message, $ip);
$pass('Insert test contact message', $stmt->execute());
$messageId = (int) $conn->insert_id;
$pass('Test message ID > 0', $messageId > 0);

// Simulate admin session
$_SESSION['admin_id'] = $_SESSION['admin_id'] ?? 1;
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// --- list.php logic ---
$q = $testEmail;
$where = ['archived = 0'];
$params = [];
$types = '';
$like = '%' . $q . '%';
$where[] = '(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)';
$params = array_merge($params, [$like, $like, $like, $like]);
$types .= 'ssss';
$sql = 'SELECT id, email, is_read FROM contact_messages WHERE ' . implode(' AND ', $where) . ' LIMIT 10';
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$found = false;
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    if ((int) $row['id'] === $messageId) {
        $found = true;
        break;
    }
}
$pass('List search finds test message by email', $found);

// Unread filter
$stmt = $conn->prepare('SELECT id FROM contact_messages WHERE archived = 0 AND is_read = 0 AND id = ?');
$stmt->bind_param('i', $messageId);
$stmt->execute();
$pass('Unread filter includes test message', (bool) $stmt->get_result()->fetch_assoc());

require_once $root . '/api/admin/messages/_helpers.php';

$row = adminMessageFetchById($conn, $messageId);
$pass('adminMessageFetchById returns row', $row !== null);

// Mark read
$stmt = $conn->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = ?');
$stmt->bind_param('i', $messageId);
$pass('Mark read update', $stmt->execute() && $stmt->affected_rows >= 0);
$row = adminMessageFetchById($conn, $messageId);
$pass('is_read = 1 after mark read', $row && (int) $row['is_read'] === 1);

// Idempotent read (no row change expected if already read)
$stmt = $conn->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = ? AND is_read = 0');
$stmt->bind_param('i', $messageId);
$stmt->execute();
$pass('Second read is idempotent (0 rows)', $stmt->affected_rows === 0);

// Mark unread
$stmt = $conn->prepare('UPDATE contact_messages SET is_read = 0 WHERE id = ?');
$stmt->bind_param('i', $messageId);
$pass('Mark unread', $stmt->execute());

// Archive
$stmt = $conn->prepare('UPDATE contact_messages SET archived = 1 WHERE id = ?');
$stmt->bind_param('i', $messageId);
$pass('Archive message', $stmt->execute());
$stmt = $conn->prepare('SELECT id FROM contact_messages WHERE archived = 0 AND id = ?');
$stmt->bind_param('i', $messageId);
$stmt->execute();
$pass('Archived message hidden from inbox query', !$stmt->get_result()->fetch_assoc());

// Delete cleanup
$stmt = $conn->prepare('DELETE FROM contact_messages WHERE id = ?');
$stmt->bind_param('i', $messageId);
$pass('Delete test message', $stmt->execute());
$pass('Message removed', adminMessageFetchById($conn, $messageId) === null);

// 404 path
$pass('Missing message returns null', adminMessageFetchById($conn, 999999999) === null);

echo PHP_EOL . ($failures === 0 ? "All tests passed.\n" : "{$failures} test(s) failed.\n");
exit($failures > 0 ? 1 : 0);
