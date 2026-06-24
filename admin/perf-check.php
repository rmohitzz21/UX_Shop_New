<?php
/**
 * perf-check.php — live-server performance diagnostic.
 *
 * Lives under /admin so it's already behind admin auth. Returns plain text.
 * Safe to keep around; not safe to expose more broadly.
 *
 * Delete or move when no longer needed.
 */

require_once __DIR__ . '/../includes/config.php';

// Only admins can view this (matches admin-dashboard.php session keys).
if (empty($_SESSION['admin_id'])) {
    http_response_code(403);
    exit('Forbidden');
}

header('Content-Type: text/plain; charset=utf-8');

$line = function (string $k, $v, ?string $note = null): void {
    printf("%-32s %s%s\n", $k, (string) $v, $note ? "  ← {$note}" : '');
};

$ms = function (float $started): float {
    return round((microtime(true) - $started) * 1000, 2);
};

echo "=== UX Pacific Shop — perf check ===\n";
echo 'Generated: ' . date('c') . "\n\n";

// ── PHP ─────────────────────────────────────────────────────────────────────
echo "--- PHP ---\n";
$line('PHP version', PHP_VERSION, version_compare(PHP_VERSION, '8.1', '<') ? 'upgrade recommended' : null);
$line('Server', $_SERVER['SERVER_SOFTWARE'] ?? 'unknown');
$line('memory_limit', ini_get('memory_limit'));
$line('max_execution_time', ini_get('max_execution_time'));
$line('post_max_size', ini_get('post_max_size'));
$line('upload_max_filesize', ini_get('upload_max_filesize'));
$line('display_errors', ini_get('display_errors') ?: 'off', ini_get('display_errors') === '1' ? 'should be off in prod' : null);

// OPcache
$opc = function_exists('opcache_get_status') ? @opcache_get_status(false) : null;
$line('OPcache available', function_exists('opcache_get_status') ? 'yes' : 'no');
$line('OPcache enabled', is_array($opc) && !empty($opc['opcache_enabled']) ? 'yes' : 'no',
      is_array($opc) && empty($opc['opcache_enabled']) ? 'ENABLE for 2-5x speedup' : null);
if (is_array($opc) && !empty($opc['memory_usage'])) {
    $used = $opc['memory_usage']['used_memory'] ?? 0;
    $free = $opc['memory_usage']['free_memory'] ?? 0;
    $line('OPcache used / free', sprintf('%.1f MB / %.1f MB', $used / 1048576, $free / 1048576));
    if (!empty($opc['opcache_statistics'])) {
        $hit = $opc['opcache_statistics']['opcache_hit_rate'] ?? 0;
        $line('OPcache hit rate', sprintf('%.2f%%', (float) $hit));
    }
}

// Compression
$line('zlib.output_compression', ini_get('zlib.output_compression') ?: '0');
$line('gzip via mod_deflate', function_exists('apache_get_modules') && in_array('mod_deflate', apache_get_modules(), true) ? 'yes' : 'check Apache config');

// Sessions
echo "\n--- Sessions ---\n";
$line('session.save_handler', ini_get('session.save_handler'));
$line('session.save_path', ini_get('session.save_path'));
$sessStart = microtime(true);
@session_start();
$line('session_start()', $ms($sessStart) . ' ms', $ms($sessStart) > 50 ? 'slow' : null);

// ── DB ─────────────────────────────────────────────────────────────────────
echo "\n--- Database ---\n";
$bootStart = microtime(true);
// $conn is already created by config.php above
$line('DB connect (via config.php)', $ms($bootStart) . ' ms (this script lifetime so far)');

$q = static function (mysqli $conn, string $sql): float {
    $t = microtime(true);
    $r = $conn->query($sql);
    if ($r instanceof mysqli_result) $r->free();
    return round((microtime(true) - $t) * 1000, 2);
};

$line('SELECT 1',                          $q($conn, 'SELECT 1') . ' ms');
$line('cart count',                        $q($conn, 'SELECT COUNT(*) FROM cart') . ' ms');
$line('products WHERE is_active=1',        $q($conn, 'SELECT id FROM products WHERE is_active = 1') . ' ms');
$line('orders user_id index probe',        $q($conn, 'SELECT id FROM orders WHERE user_id = 1 ORDER BY id DESC LIMIT 10') . ' ms');
$line('order_items by product_id+price=0', $q($conn, 'SELECT id FROM order_items WHERE product_id = 1 AND price = 0 LIMIT 1') . ' ms');

// Missing-index quick check
echo "\n--- Index presence (key tables) ---\n";
$idx = function (string $table) use ($conn): array {
    $names = [];
    $r = $conn->query("SHOW INDEX FROM `{$table}`");
    if ($r instanceof mysqli_result) {
        while ($row = $r->fetch_assoc()) $names[] = $row['Key_name'];
        $r->free();
    }
    return array_values(array_unique($names));
};
foreach (['cart','products','orders','order_items','digital_downloads','digital_resources'] as $t) {
    $line($t, implode(', ', $idx($t)) ?: '(none)');
}

// ── Filesystem & writability ───────────────────────────────────────────────
echo "\n--- Filesystem ---\n";
foreach ([
    '.env'                       => __DIR__ . '/../.env',
    'logs/'                      => __DIR__ . '/../logs',
    'storage/sessions/'          => __DIR__ . '/../storage/sessions',
    'storage/private/'           => __DIR__ . '/../storage/private',
    'cache/'                     => __DIR__ . '/../cache',
    'img/products/'              => __DIR__ . '/../img/products',
] as $label => $path) {
    $exists = file_exists($path);
    $writable = $exists && is_writable($path);
    $line($label, ($exists ? 'exists' : 'MISSING') . ($exists && !$writable ? ' / not writable' : ''));
}

// ── Self-time ───────────────────────────────────────────────────────────────
echo "\nTotal script time: " . $ms($bootStart) . " ms\n";
echo "\n=== DONE — keep behind /admin, delete when not needed ===\n";
