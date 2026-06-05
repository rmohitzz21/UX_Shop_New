<?php
declare(strict_types=1);

/**
 * Private digital file storage (local dev path or S3-compatible cloud).
 * storage_key values must never be exposed as public URLs.
 */
class DigitalStorageService
{
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
            if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
                return false;
            }
            return copy($localTmpPath, $dest);
        }

        if ($driver === 'r2' || $driver === 's3') {
            return self::s3Upload($localTmpPath, $storageKey);
        }

        return false;
    }

    public static function getSignedUrl(string $storageKey): ?string
    {
        $storageKey = self::sanitizeStorageKey($storageKey);
        if ($storageKey === '') {
            return null;
        }

        $driver = self::getDriver();
        if ($driver === 'local') {
            return null;
        }

        if ($driver === 'r2' || $driver === 's3') {
            return self::s3PresignGet($storageKey);
        }

        return null;
    }

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

        $mime = mime_content_type($path) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . rawurlencode(basename($storageKey)) . '"');
        header('Content-Length: ' . (string) filesize($path));
        header('Cache-Control: private, no-store');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

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

        $now = time();
        $amzDate = gmdate('Ymd\THis\Z', $now);
        $date    = gmdate('Ymd', $now);
        $expires = (string) $ttl;
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

        $canonical = "GET\n{$path}\n{$canonicalQuery}\nhost:{$host}\n\nhost\nUNSIGNED-PAYLOAD";
        $stringToSign = "AWS4-HMAC-SHA256\n{$amzDate}\n{$date}/{$cfg['region']}/s3/aws4_request\n" . hash('sha256', $canonical);
        $signingKey = self::awsSigningKey($cfg['secret'], $date, $cfg['region'], 's3');
        $signature  = hash_hmac('sha256', $stringToSign, $signingKey);

        return $url . '?' . $canonicalQuery . '&X-Amz-Signature=' . $signature;
    }

    private static function encodePath(string $key): string
    {
        return implode('/', array_map('rawurlencode', explode('/', $key)));
    }

    /** @return list<string> */
    private static function signS3Request(string $method, string $url, array $cfg, string $key, int $contentLength, string $contentType): array
    {
        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        $amzDate = gmdate('Ymd\THis\Z');
        $date    = gmdate('Ymd');
        $payload = hash('sha256', '');
        $canonical = "{$method}\n{$path}\n\nhost:{$host}\nx-amz-content-sha256:{$payload}\nx-amz-date:{$amzDate}\n\nhost;x-amz-content-sha256;x-amz-date\n{$payload}";
        $scope = "{$date}/{$cfg['region']}/s3/aws4_request";
        $stringToSign = "AWS4-HMAC-SHA256\n{$amzDate}\n{$scope}\n" . hash('sha256', $canonical);
        $signingKey = self::awsSigningKey($cfg['secret'], $date, $cfg['region'], 's3');
        $signature  = hash_hmac('sha256', $stringToSign, $signingKey);
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
        $kDate    = hash_hmac('sha256', $date, 'AWS4' . $secret, true);
        $kRegion  = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        return hash_hmac('sha256', 'aws4_request', $kService, true);
    }
}
