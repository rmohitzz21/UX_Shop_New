<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/includes/config.php';
require_once $root . '/includes/auth_rate_limit.php';

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$failures = 0;
$pass = static function (string $label, bool $ok) use (&$failures): void {
    echo ($ok ? '[PASS] ' : '[FAIL] ') . $label . PHP_EOL;
    if (!$ok) {
        $failures++;
    }
};

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Test rate limiting (5 per session, 15 per IP, 15 min window)
for ($i = 0; $i < 5; $i++) {
    authRateLimitCheck('login_test', 5, 15, 900);
}
$pass('Rate limit blocks 6th session attempt', !authRateLimitAllow('login_test', 5, 900));

$pass('Database reachable', $conn->ping());

// Test case-insensitive email lookup
$testEmail = 'testuser@example.com';
$stmt = $conn->prepare('SELECT id, email FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1');
$pass('Case-insensitive query prepare', $stmt instanceof mysqli_stmt);

if ($stmt) {
    $upperEmail = strtoupper($testEmail);
    $stmt->bind_param('s', $upperEmail);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        $pass('Case-insensitive email found', strtolower($row['email']) === strtolower($testEmail));
    } else {
        echo "[INFO] Test user '{$testEmail}' not in DB (skipping case test)\n";
    }
    $stmt->close();
}

// Test password verification logic (without actual user)
$testHash = password_hash('TestPass123!', PASSWORD_DEFAULT);
$pass('password_verify correct', password_verify('TestPass123!', $testHash));
$pass('password_verify wrong', !password_verify('WrongPass', $testHash));

// Test blocked user check logic
$blockedCheck = "SELECT is_blocked FROM users WHERE is_blocked = 1 LIMIT 1";
$result = $conn->query($blockedCheck);
echo "[INFO] Blocked users in DB: " . ($result ? $result->num_rows : 0) . PHP_EOL;

echo PHP_EOL . ($failures === 0 ? "Sign-in helper checks passed.\n" : "{$failures} failed.\n");
exit($failures > 0 ? 1 : 0);
