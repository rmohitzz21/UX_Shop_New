<?php
declare(strict_types=1);

/**
 * Private digital file storage with AES-256-GCM at-rest encryption.
 *
 * Encrypted file format (local storage):
 *   [8 bytes magic] [12 bytes IV/nonce] [16 bytes auth-tag] [ciphertext]
 *
 * Magic: "\x00\x00UXPE\x01\x00" — chosen so it cannot appear at the start
 * of any standard file format (ZIP/PDF/PNG all have different signatures).
 *
 * Set DIGITAL_STORAGE_ENCRYPT_KEY in .env to a 64-char hex string (32 bytes).
 * Leave it unset to disable encryption (legacy plaintext mode).
 * Existing plaintext files are served as-is; new uploads are encrypted when
 * a key is configured. Never expose storage_key values as public URLs.
 */
class DigitalStorageService
{
    private const MAGIC   = "\x00\x00UXPE\x01\x00";
    private const MAGIC_LEN  = 8;
    private const IV_LEN     = 12;
    private const TAG_LEN    = 16;
    private const HEADER_LEN = 36; // MAGIC + IV + TAG

    // ──────────────────────────────────────────────────────────────────────────
    // Encryption helpers
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Returns the raw 32-byte encryption key, or empty string if not configured.
     */
    private static function encryptionKey(): string
    {
        $hex = trim((string) (getenv('DIGITAL_STORAGE_ENCRYPT_KEY') ?: ''));
        if ($hex === '') {
            return '';
        }
        if (strlen($hex) !== 64 || !ctype_xdigit($hex)) {
            error_log('[DigitalStorage] DIGITAL_STORAGE_ENCRYPT_KEY must be 64 hex chars.');
            return '';
        }
        return (string) hex2bin($hex);
    }

    public static function encryptionRequired(): bool
    {
        if (self::getDriver() !== 'local') {
            return false;
        }
        $flag = strtolower(trim((string) (getenv('DIGITAL_STORAGE_REQUIRE_ENCRYPTION') ?: '')));
        return in_array($flag, ['1', 'true', 'yes'], true);
    }

    public static function hasEncryptionKey(): bool
    {
        return self::encryptionKey() !== '';
    }

    /**
     * Returns true when a file blob is encrypted by this service.
     */
    private static function isEncrypted(string $data): bool
    {
        return strlen($data) >= self::HEADER_LEN
            && substr($data, 0, self::MAGIC_LEN) === self::MAGIC;
    }

    /**
     * Encrypts raw bytes with AES-256-GCM.
     * Returns the encrypted blob with prepended header, or false on failure.
     *
     * @return string|false
     */
    private static function encrypt(string $plaintext, string $key)
    {
        $iv  = random_bytes(self::IV_LEN);
        $tag = '';
        $ciphertext = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_LEN
        );
        if ($ciphertext === false) {
            return false;
        }
        return self::MAGIC . $iv . $tag . $ciphertext;
    }

    /**
     * Decrypts a blob produced by encrypt().
     * If the blob has no magic header it is returned unchanged (legacy file).
     *
     * @return string|false  false on authentication failure
     */
    private static function decrypt(string $data, string $key)
    {
        if (!self::isEncrypted($data)) {
            return $data; // legacy plaintext — serve as-is
        }
        $iv         = substr($data, self::MAGIC_LEN, self::IV_LEN);
        $tag        = substr($data, self::MAGIC_LEN + self::IV_LEN, self::TAG_LEN);
        $ciphertext = substr($data, self::HEADER_LEN);

        $plain = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        return $plain; // false if auth tag mismatch (tampered file)
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Path / driver helpers
    // ──────────────────────────────────────────────────────────────────────────

    private static function localBaseDir(): string
    {
        $configured = trim((string) (getenv('DIGITAL_STORAGE_LOCAL_DIR') ?: ''));
        if ($configured !== '') {
            $configured = rtrim($configured, '/\\');
            if (self::isAbsolutePath($configured)) {
                return $configured;
            }
            return dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $configured);
        }
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'private';
    }

    private static function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }

    public static function getDriver(): string
    {
        return strtolower(trim((string) (getenv('DIGITAL_STORAGE_DRIVER') ?: 'local')));
    }

    public static function sanitizeStorageKey(string $key): string
    {
        $key = str_replace('\\', '/', trim($key));
        $key = preg_replace('#/+#', '/', $key) ?? $key;
        if ($key === '' || str_contains($key, '..')) {
            return '';
        }
        return ltrim($key, '/');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Public API
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Store a file in private storage, encrypting it if a key is configured.
     */
    public static function upload(string $localTmpPath, string $storageKey): bool
    {
        $storageKey = self::sanitizeStorageKey($storageKey);
        if ($storageKey === '' || !is_readable($localTmpPath)) {
            return false;
        }

        $driver = self::getDriver();

        if ($driver === 'local') {
            $dest = self::localBaseDir() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $storageKey);
            $dir  = dirname($dest);
            if (!is_dir($dir) && !@mkdir($dir, 0750, true)) {
                return false;
            }

            $raw = file_get_contents($localTmpPath);
            if ($raw === false) {
                return false;
            }

            $key = self::encryptionKey();
            if ($key === '' && self::encryptionRequired()) {
                error_log('[DigitalStorage] Upload rejected: DIGITAL_STORAGE_ENCRYPT_KEY is required for local storage.');
                return false;
            }
            if ($key !== '') {
                $blob = self::encrypt($raw, $key);
                if ($blob === false) {
                    error_log('[DigitalStorage] Encryption failed for key: ' . $storageKey);
                    return false;
                }
            } else {
                $blob = $raw;
            }

            return file_put_contents($dest, $blob, LOCK_EX) !== false;
        }

        if ($driver === 'r2' || $driver === 's3') {
            // R2/S3 provides server-side encryption — upload plaintext
            return self::s3Upload($localTmpPath, $storageKey);
        }

        return false;
    }

    /**
     * Get a presigned URL for R2/S3 (local driver returns null).
     */
    public static function getSignedUrl(string $storageKey): ?string
    {
        $storageKey = self::sanitizeStorageKey($storageKey);
        if ($storageKey === '') {
            return null;
        }
        $driver = self::getDriver();
        if ($driver === 'r2' || $driver === 's3') {
            return self::s3PresignGet($storageKey);
        }
        return null;
    }

    /**
     * Stream a locally-stored file to the HTTP response, decrypting if needed.
     */
    public static function streamLocal(string $storageKey): void
    {
        $storageKey = self::sanitizeStorageKey($storageKey);
        $path       = self::localBaseDir() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $storageKey);

        if ($storageKey === '' || !is_file($path)) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'File not found.']);
            exit;
        }

        $blob = file_get_contents($path);
        if ($blob === false) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Could not read file.']);
            exit;
        }

        // Decrypt if the blob carries our encryption header
        if (self::isEncrypted($blob)) {
            $key = self::encryptionKey();
            if ($key === '') {
                // Key was removed from env but file is encrypted — cannot serve
                error_log('[DigitalStorage] Encrypted file found but DIGITAL_STORAGE_ENCRYPT_KEY is not set: ' . $storageKey);
                http_response_code(503);
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'File unavailable — server configuration error.']);
                exit;
            }
            $plain = self::decrypt($blob, $key);
            if ($plain === false) {
                error_log('[DigitalStorage] Auth-tag mismatch (tampered file?): ' . $storageKey);
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'File integrity check failed.']);
                exit;
            }
            $blob = $plain;
        }

        // Derive original file name — strip the random suffix we added on upload
        $storedName = basename($storageKey);
        // Pattern: <resourceId>_<randomhex>.<ext>  →  deliver as original ext
        $ext      = strtolower(pathinfo($storedName, PATHINFO_EXTENSION));
        $mime     = self::mimeForExt($ext);
        $filename = $storedName;

        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
        header('Content-Length: ' . (string) strlen($blob));
        header('Cache-Control: private, no-store');
        header('X-Content-Type-Options: nosniff');
        echo $blob;
        exit;
    }

    /**
     * Delete a stored file. Returns true if deleted or already absent.
     */
    public static function delete(string $storageKey): bool
    {
        $storageKey = self::sanitizeStorageKey($storageKey);
        if ($storageKey === '') {
            return false;
        }
        if (self::getDriver() === 'local') {
            $path = self::localBaseDir() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $storageKey);
            return is_file($path) ? @unlink($path) : true;
        }
        return false;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // MIME helper
    // ──────────────────────────────────────────────────────────────────────────

    private static function mimeForExt(string $ext): string
    {
        return match ($ext) {
            'pdf'          => 'application/pdf',
            'zip'          => 'application/zip',
            'sketch'       => 'application/zip',
            'fig', 'xd'    => 'application/octet-stream',
            'png'          => 'image/png',
            'jpg', 'jpeg'  => 'image/jpeg',
            'svg'          => 'image/svg+xml',
            'webp'         => 'image/webp',
            default        => 'application/octet-stream',
        };
    }

    // ──────────────────────────────────────────────────────────────────────────
    // S3 / R2 helpers (unchanged from original)
    // ──────────────────────────────────────────────────────────────────────────

    private static function s3Config(): ?array
    {
        $endpoint = rtrim((string) (getenv('DIGITAL_STORAGE_ENDPOINT') ?: ''), '/');
        $bucket   = trim((string) (getenv('DIGITAL_STORAGE_BUCKET') ?: ''));
        $access   = trim((string) (getenv('DIGITAL_STORAGE_ACCESS_KEY') ?: ''));
        $secret   = trim((string) (getenv('DIGITAL_STORAGE_SECRET_KEY') ?: ''));
        $region   = trim((string) (getenv('DIGITAL_STORAGE_REGION') ?: 'auto'));
        if ($endpoint === '' || $bucket === '' || $access === '' || $secret === '') {
            return null;
        }
        return compact('endpoint', 'bucket', 'access', 'secret', 'region');
    }

    private static function s3Upload(string $localPath, string $key): bool
    {
        $cfg = self::s3Config();
        if ($cfg === null) {
            return false;
        }
        $url  = $cfg['endpoint'] . '/' . rawurlencode($cfg['bucket']) . '/' . self::encodePath($key);
        $body = file_get_contents($localPath);
        if ($body === false) {
            return false;
        }
        $headers = self::signS3Request('PUT', $url, $cfg, $key, strlen($body), 'application/octet-stream');
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'PUT',
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 120,
        ]);
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code >= 200 && $code < 300;
    }

    private static function s3PresignGet(string $key): ?string
    {
        $cfg = self::s3Config();
        if ($cfg === null) {
            return null;
        }
        $ttl = max(60, min(3600, (int) (getenv('DIGITAL_STORAGE_PRESIGN_TTL_SECONDS') ?: 300)));
        $url = $cfg['endpoint'] . '/' . rawurlencode($cfg['bucket']) . '/' . self::encodePath($key);

        $now        = time();
        $amzDate    = gmdate('Ymd\THis\Z', $now);
        $date       = gmdate('Ymd', $now);
        $expires    = (string) $ttl;
        $credential = $cfg['access'] . '/' . $date . '/' . $cfg['region'] . '/s3/aws4_request';

        $query = [
            'X-Amz-Algorithm'     => 'AWS4-HMAC-SHA256',
            'X-Amz-Credential'    => $credential,
            'X-Amz-Date'          => $amzDate,
            'X-Amz-Expires'       => $expires,
            'X-Amz-SignedHeaders' => 'host',
        ];
        ksort($query);
        $canonicalQuery = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH) ?: '/';

        $canonical    = "GET\n{$path}\n{$canonicalQuery}\nhost:{$host}\n\nhost\nUNSIGNED-PAYLOAD";
        $stringToSign = "AWS4-HMAC-SHA256\n{$amzDate}\n{$date}/{$cfg['region']}/s3/aws4_request\n" . hash('sha256', $canonical);
        $signingKey   = self::awsSigningKey($cfg['secret'], $date, $cfg['region'], 's3');
        $signature    = hash_hmac('sha256', $stringToSign, $signingKey);

        return $url . '?' . $canonicalQuery . '&X-Amz-Signature=' . $signature;
    }

    private static function encodePath(string $key): string
    {
        return implode('/', array_map('rawurlencode', explode('/', $key)));
    }

    /** @return list<string> */
    private static function signS3Request(string $method, string $url, array $cfg, string $key, int $contentLength, string $contentType): array
    {
        $host      = parse_url($url, PHP_URL_HOST);
        $path      = parse_url($url, PHP_URL_PATH) ?: '/';
        $amzDate   = gmdate('Ymd\THis\Z');
        $date      = gmdate('Ymd');
        $payload   = hash('sha256', '');
        $canonical = "{$method}\n{$path}\n\nhost:{$host}\nx-amz-content-sha256:{$payload}\nx-amz-date:{$amzDate}\n\nhost;x-amz-content-sha256;x-amz-date\n{$payload}";
        $scope     = "{$date}/{$cfg['region']}/s3/aws4_request";
        $stringToSign = "AWS4-HMAC-SHA256\n{$amzDate}\n{$scope}\n" . hash('sha256', $canonical);
        $signingKey   = self::awsSigningKey($cfg['secret'], $date, $cfg['region'], 's3');
        $signature    = hash_hmac('sha256', $stringToSign, $signingKey);
        return [
            'Host: ' . $host,
            'x-amz-date: ' . $amzDate,
            'x-amz-content-sha256: ' . $payload,
            'Authorization: AWS4-HMAC-SHA256 Credential=' . $cfg['access'] . '/' . $scope . ', SignedHeaders=host;x-amz-content-sha256;x-amz-date, Signature=' . $signature,
            'Content-Type: ' . $contentType,
            'Content-Length: ' . $contentLength,
        ];
    }

    private static function awsSigningKey(string $secret, string $date, string $region, string $service): string
    {
        $kDate    = hash_hmac('sha256', $date,    'AWS4' . $secret, true);
        $kRegion  = hash_hmac('sha256', $region,  $kDate,           true);
        $kService = hash_hmac('sha256', $service, $kRegion,         true);
        return hash_hmac('sha256', 'aws4_request', $kService, true);
    }
}
