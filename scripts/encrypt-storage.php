<?php
/**
 * One-time migration: encrypt all existing plaintext files in private storage.
 *
 * Run from project root (CLI only):
 *   php scripts/encrypt-storage.php
 *
 * Prerequisites:
 *   1. Set DIGITAL_STORAGE_ENCRYPT_KEY in .env (64 hex chars).
 *   2. Back up storage/private/ before running.
 *
 * The script is idempotent — already-encrypted files are skipped.
 * Remove this file from the server after running.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

define('PROJECT_ROOT', dirname(__DIR__));
require_once PROJECT_ROOT . '/includes/config.php';
require_once PROJECT_ROOT . '/includes/DigitalStorageService.php';

// ── Read key ─────────────────────────────────────────────────────────────────
$hex = trim((string) (getenv('DIGITAL_STORAGE_ENCRYPT_KEY') ?: ''));
if ($hex === '' || strlen($hex) !== 64 || !ctype_xdigit($hex)) {
    fwrite(STDERR, "ERROR: DIGITAL_STORAGE_ENCRYPT_KEY must be a 64-char hex string in .env\n");
    fwrite(STDERR, "Generate one: php -r \"echo bin2hex(random_bytes(32)) . PHP_EOL;\"\n");
    exit(1);
}
$key = hex2bin($hex);

// ── Locate storage dir ───────────────────────────────────────────────────────
$baseDir = PROJECT_ROOT . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'private';
$envDir  = trim((string) (getenv('DIGITAL_STORAGE_LOCAL_DIR') ?: ''));
if ($envDir !== '') {
    $baseDir = rtrim($envDir, '/\\');
}
if (!is_dir($baseDir)) {
    fwrite(STDERR, "ERROR: Storage directory not found: {$baseDir}\n");
    exit(1);
}

// Magic header used by DigitalStorageService
const MAGIC    = "\x00\x00UXPE\x01\x00";
const MAGIC_LEN   = 8;
const IV_LEN      = 12;
const TAG_LEN     = 16;
const HEADER_LEN  = 36;

// ── Iterate files ─────────────────────────────────────────────────────────────
$it      = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS));
$total   = 0;
$skipped = 0;
$done    = 0;
$errors  = 0;

foreach ($it as $file) {
    if (!$file->isFile()) {
        continue;
    }
    $path = $file->getPathname();
    if (strtolower(basename($path)) === '.htaccess') {
        echo "  [SKIP] Config file: " . basename(dirname($path)) . "/.htaccess\n";
        $skipped++;
        continue;
    }
    $total++;

    $raw = file_get_contents($path);
    if ($raw === false) {
        fwrite(STDERR, "  [SKIP-ERROR] Cannot read: {$path}\n");
        $errors++;
        continue;
    }

    // Already encrypted?
    if (strlen($raw) >= HEADER_LEN && substr($raw, 0, MAGIC_LEN) === MAGIC) {
        echo "  [SKIP] Already encrypted: " . basename($path) . "\n";
        $skipped++;
        continue;
    }

    // Encrypt
    $iv  = random_bytes(IV_LEN);
    $tag = '';
    $ciphertext = openssl_encrypt($raw, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', TAG_LEN);
    if ($ciphertext === false) {
        fwrite(STDERR, "  [ERROR] openssl_encrypt failed: {$path}\n");
        $errors++;
        continue;
    }
    $blob = MAGIC . $iv . $tag . $ciphertext;

    // Write atomically (temp file + rename)
    $tmp = $path . '.enc.tmp';
    if (file_put_contents($tmp, $blob, LOCK_EX) === false) {
        fwrite(STDERR, "  [ERROR] Cannot write temp file: {$tmp}\n");
        $errors++;
        @unlink($tmp);
        continue;
    }
    if (!rename($tmp, $path)) {
        fwrite(STDERR, "  [ERROR] Cannot rename to: {$path}\n");
        $errors++;
        @unlink($tmp);
        continue;
    }

    echo "  [OK] Encrypted: " . basename($path) . " (" . strlen($raw) . " → " . strlen($blob) . " bytes)\n";
    $done++;
}

echo "\n";
echo "Done. Total: {$total}  Encrypted: {$done}  Skipped (already encrypted): {$skipped}  Errors: {$errors}\n";
if ($errors > 0) {
    exit(1);
}
