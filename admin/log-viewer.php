<?php
/**
 * log-viewer.php — tail the live app_errors.log in the browser.
 *
 * Admin-only. Delete after debugging.
 *
 * Usage:
 *   /admin/log-viewer.php             (last 80 lines, full)
 *   /admin/log-viewer.php?lines=200   (last N lines)
 *   /admin/log-viewer.php?q=CSRF      (filter by substring)
 */

require_once __DIR__ . '/../includes/config.php';

if (empty($_SESSION['admin_id'])) {
    http_response_code(403);
    exit('Forbidden — log in as admin first.');
}

header('Content-Type: text/plain; charset=utf-8');

$logFile = dirname(__DIR__) . '/logs/app_errors.log';
$lines   = max(10, min(500, (int) ($_GET['lines'] ?? 80)));
$filter  = trim((string) ($_GET['q'] ?? ''));

if (!is_file($logFile)) {
    echo "Log file not found: {$logFile}\n";
    exit;
}

echo "=== Live app_errors.log ===\n";
echo "Path: {$logFile}\n";
echo "Size: " . number_format(filesize($logFile)) . " bytes\n";
echo "Modified: " . date('c', filemtime($logFile)) . "\n";
if ($filter !== '') echo "Filter: \"{$filter}\"\n";
echo "Showing last {$lines} lines (after filter)\n\n";

$fh = fopen($logFile, 'r');
if (!$fh) { echo "Can't open log."; exit; }

// Simple ring buffer to grab the last N matching lines without loading whole file.
$ring = [];
while (($line = fgets($fh)) !== false) {
    if ($filter !== '' && stripos($line, $filter) === false) continue;
    $ring[] = rtrim($line, "\r\n");
    if (count($ring) > $lines) array_shift($ring);
}
fclose($fh);

echo implode("\n", $ring) . "\n";
echo "\n=== end ===\n";
