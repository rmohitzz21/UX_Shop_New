<?php
/**
 * csrf-check.php — diagnose why signin/signup throws CSRF errors on live.
 *
 * Visit twice in a row from the same browser. On the SECOND load,
 * the "Session ID" and "csrf_token in session" should be IDENTICAL to the first.
 * If they change every reload → your session isn't persisting → fix that first.
 *
 * Lives under /admin so only logged-in admins can see it. Delete when done.
 */

require_once __DIR__ . '/../includes/config.php';

if (empty($_SESSION['admin_id'])) {
    http_response_code(403);
    exit('Forbidden — log in to /admin first.');
}

header('Content-Type: text/plain; charset=utf-8');

echo "=== CSRF / session diagnostic ===\n";
echo 'Generated: ' . date('c') . "\n\n";

echo "--- Session ---\n";
printf("%-32s %s\n", 'Session ID',                session_id());
printf("%-32s %s\n", 'session_status()',         (string) session_status());
printf("%-32s %s\n", 'session.save_handler',     (string) ini_get('session.save_handler'));
printf("%-32s %s\n", 'session.save_path',        (string) ini_get('session.save_path'));
printf("%-32s %s\n", 'session_save_path() now',  (string) session_save_path());
printf("%-32s %s\n", 'session.cookie_secure',    ini_get('session.cookie_secure'));
printf("%-32s %s\n", 'session.cookie_samesite',  ini_get('session.cookie_samesite'));
printf("%-32s %s\n", 'session.cookie_httponly',  ini_get('session.cookie_httponly'));
printf("%-32s %s\n", 'session.cookie_path',      ini_get('session.cookie_path'));
printf("%-32s %s\n", 'session.cookie_domain',    ini_get('session.cookie_domain') ?: '(blank — host-only)');

echo "\n--- Save path writability ---\n";
$candidates = [
    'configured storage' => dirname(__DIR__) . '/storage/sessions',
    'php save_path'      => ini_get('session.save_path') ?: '/tmp',
    '/tmp'               => '/tmp',
];
foreach ($candidates as $label => $path) {
    $exists = is_dir($path);
    $writ   = $exists && is_writable($path);
    $owner  = $exists ? (function_exists('posix_getpwuid') ? @posix_getpwuid(@fileowner($path))['name'] ?? '?' : (string) @fileowner($path)) : '-';
    printf("%-22s %-60s exists=%s writable=%s owner=%s\n",
        $label, $path, $exists ? 'yes' : 'NO', $writ ? 'yes' : 'NO', $owner);
}

echo "\n--- CSRF token ---\n";
printf("%-32s %s\n", 'csrf_token in session',
    isset($_SESSION['csrf_token']) ? substr($_SESSION['csrf_token'], 0, 16) . '… (len ' . strlen($_SESSION['csrf_token']) . ')' : '(MISSING)');

echo "\n--- HTTPS / proxy detection ---\n";
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
           || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
           || (($_SERVER['SERVER_PORT'] ?? 80) == 443);
printf("%-32s %s\n", '$_SERVER[HTTPS]',                $_SERVER['HTTPS'] ?? '(unset)');
printf("%-32s %s\n", 'HTTP_X_FORWARDED_PROTO',         $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '(unset)');
printf("%-32s %s\n", 'SERVER_PORT',                    $_SERVER['SERVER_PORT'] ?? '?');
printf("%-32s %s\n", 'Detected $isHttps',              $isHttps ? 'TRUE → secure cookie' : 'false → non-secure cookie');
printf("%-32s %s\n", 'HTTP_HOST',                      $_SERVER['HTTP_HOST'] ?? '?');
printf("%-32s %s\n", 'REQUEST_URI',                    $_SERVER['REQUEST_URI'] ?? '?');

echo "\n--- Cookies the browser sent us ---\n";
if (empty($_COOKIE)) {
    echo "(no cookies received — session can't persist)\n";
} else {
    foreach ($_COOKIE as $k => $v) {
        printf("  %-25s = %s\n", $k, is_string($v) ? substr($v, 0, 32) . (strlen($v) > 32 ? '…' : '') : '[array]');
    }
}

echo "\n--- OPcache ---\n";
if (function_exists('opcache_get_status')) {
    $oc = @opcache_get_status(false);
    $on = is_array($oc) && !empty($oc['opcache_enabled']);
    printf("%-32s %s\n", 'opcache enabled', $on ? 'yes' : 'no');
    if ($on) {
        printf("%-32s %s\n", 'opcache.validate_timestamps', ini_get('opcache.validate_timestamps'));
        printf("%-32s %s\n", 'opcache.revalidate_freq',     ini_get('opcache.revalidate_freq'));
    }
} else {
    echo "opcache extension not loaded\n";
}

echo "\nReload this page once. If the Session ID changes between loads, fix the session save path before anything else.\n";
