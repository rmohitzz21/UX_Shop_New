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

for ($i = 0; $i < 5; $i++) {
    authRateLimitAllow('signup_cli', 5, 3600);
}
$pass('Rate limit blocks 6th attempt', !authRateLimitAllow('signup_cli', 5, 3600));

$parts = preg_split('/\s+/', 'Jane Q Public', 2);
$pass('fullName split first', ($parts[0] ?? '') === 'Jane');
$pass('fullName split last', ($parts[1] ?? '') === 'Q Public');

$pass('Database reachable', $conn->ping());

$email = 'signup-cli-' . time() . '@example.com';
$stmt  = $conn->prepare('SELECT id FROM users WHERE LOWER(email) = ? LIMIT 1');
$pass('Duplicate-check prepare', $stmt instanceof mysqli_stmt);
if ($stmt) {
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $pass('New email not registered yet', !$stmt->get_result()->fetch_assoc());
    $stmt->close();
}

echo PHP_EOL . ($failures === 0 ? "Signup helper checks passed (full flow: test on signup.php in browser).\n" : "{$failures} failed.\n");
exit($failures > 0 ? 1 : 0);
