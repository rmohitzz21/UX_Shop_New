<?php
/**
 * bom-scan.php — find PHP files with UTF-8 BOMs and whitespace before <?php.
 *
 * Admin-only. Delete after use.
 *
 * Usage:
 *   /admin/bom-scan.php           (scan + report)
 *   /admin/bom-scan.php?fix=1     (strip BOM/leading-whitespace from offending files)
 */

require_once __DIR__ . '/../includes/config.php';

if (empty($_SESSION['admin_id'])) {
    http_response_code(403);
    exit('Forbidden — log in as admin first.');
}

header('Content-Type: text/plain; charset=utf-8');

$fix    = isset($_GET['fix']) && $_GET['fix'] === '1';
$root   = dirname(__DIR__);
$skip   = ['/storage', '/logs', '/cache', '/.claude', '/.git', '/node_modules'];
$dirty  = [];

$it = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
        function ($current, $key, $iterator) use ($skip, $root): bool {
            $rel = '/' . ltrim(str_replace($root, '', $current->getPathname()), '/\\');
            $rel = str_replace('\\', '/', $rel);
            foreach ($skip as $s) {
                if (strpos($rel, $s) === 0) return false;
            }
            return true;
        }
    )
);

foreach ($it as $file) {
    if (!$file->isFile()) continue;
    if (strtolower($file->getExtension()) !== 'php') continue;

    $fp = @fopen($file->getPathname(), 'rb');
    if (!$fp) continue;
    $head = (string) fread($fp, 64);
    fclose($fp);

    $bytes = bin2hex(substr($head, 0, 8));
    $hasBom = strncmp($head, "\xEF\xBB\xBF", 3) === 0;

    // Detect leading whitespace before <?php (newline, space, tab) but allow files
    // that legitimately start with HTML (no <?php at top — e.g. partials). Only
    // flag when <?php appears in the head but isn't at byte 0 (after any BOM).
    $offset = $hasBom ? 3 : 0;
    $afterBom = substr($head, $offset);
    $hasLeadWs = false;
    if (strpos($afterBom, '<?php') !== false) {
        $hasLeadWs = !str_starts_with($afterBom, '<?php') && !str_starts_with($afterBom, '<?');
    }

    if ($hasBom || $hasLeadWs) {
        $dirty[] = [
            'path'      => str_replace($root, '', $file->getPathname()),
            'bom'       => $hasBom,
            'leadWs'    => $hasLeadWs,
            'firstBytes'=> $bytes,
        ];
    }
}

echo "=== UTF-8 BOM / leading-whitespace scan ===\n";
echo 'Root: ' . $root . "\n";
echo 'Mode: ' . ($fix ? 'FIX (strip BOM + leading whitespace)' : 'REPORT only') . "\n";
echo 'Hits: ' . count($dirty) . "\n\n";

if (empty($dirty)) {
    echo "✅ No PHP files with BOMs or leading whitespace.\n";
    echo "If checkout.php is still white-screen, something else is producing output —\n";
    echo "check the Apache error_log (cPanel → Logs → Errors) for fatal-level entries.\n";
    exit;
}

foreach ($dirty as $row) {
    $flags = trim(($row['bom'] ? 'BOM ' : '') . ($row['leadWs'] ? 'leading-ws' : ''));
    printf("[%s] %-12s  first8=%s\n", $flags, $row['path'], $row['firstBytes']);
}

if (!$fix) {
    echo "\nRun this URL to strip BOM + leading whitespace from every file above:\n";
    echo "  /admin/bom-scan.php?fix=1\n";
    echo "Recommended: back up first via cPanel → File Manager → Compress.\n";
    exit;
}

// Fix mode — strip BOM and leading whitespace before the first <?php tag.
$fixed = 0;
foreach ($dirty as $row) {
    $abs = $root . $row['path'];
    $contents = @file_get_contents($abs);
    if ($contents === false) {
        echo "skip (unreadable): {$row['path']}\n";
        continue;
    }
    $original = $contents;
    // Strip BOM
    if (strncmp($contents, "\xEF\xBB\xBF", 3) === 0) {
        $contents = substr($contents, 3);
    }
    // Strip leading whitespace before <?php
    $contents = preg_replace('/\A\s+(?=<\?(php|=)?)/i', '', $contents);

    if ($contents !== $original && @file_put_contents($abs, $contents) !== false) {
        $fixed++;
        echo "fixed: {$row['path']}\n";
    } else {
        echo "no-op: {$row['path']}\n";
    }
}
echo "\n✅ Fixed {$fixed} files. Now clear OPcache and retry.\n";
