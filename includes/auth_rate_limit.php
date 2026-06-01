<?php
/**
 * Rate limiting for auth endpoints (session-based + IP-based via file cache).
 */
declare(strict_types=1);

/**
 * Session-based rate limiting (per browser session + key).
 */
function authRateLimitAllow(string $key, int $maxAttempts, int $windowSeconds = 3600): bool
{
    $sessionKey = 'auth_rl_' . $key;
    $now        = time();

    if (
        !isset($_SESSION[$sessionKey]['count'], $_SESSION[$sessionKey]['window_start'])
        || ($now - (int) $_SESSION[$sessionKey]['window_start']) > $windowSeconds
    ) {
        $_SESSION[$sessionKey] = ['count' => 0, 'window_start' => $now];
    }

    $_SESSION[$sessionKey]['count']++;

    return $_SESSION[$sessionKey]['count'] <= $maxAttempts;
}

/**
 * IP-based rate limiting using file cache (fallback when session is bypassed).
 * Stores rate limit data in a cache directory.
 */
function authRateLimitByIp(string $key, int $maxAttempts, int $windowSeconds = 3600): bool
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $ip = filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '127.0.0.1';
    
    $cacheDir = dirname(__DIR__) . '/cache/rate_limit';
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0755, true);
    }
    
    $filename = $cacheDir . '/' . md5($key . '_' . $ip) . '.json';
    $now = time();
    $data = ['count' => 0, 'window_start' => $now];
    
    if (file_exists($filename)) {
        $content = @file_get_contents($filename);
        if ($content) {
            $stored = json_decode($content, true);
            if (is_array($stored) && isset($stored['count'], $stored['window_start'])) {
                if (($now - (int) $stored['window_start']) <= $windowSeconds) {
                    $data = $stored;
                }
            }
        }
    }
    
    $data['count']++;
    @file_put_contents($filename, json_encode($data), LOCK_EX);
    
    return $data['count'] <= $maxAttempts;
}

/**
 * Combined rate limiting: checks both session AND IP.
 * Returns false (blocked) if EITHER limit is exceeded.
 */
function authRateLimitCheck(string $key, int $maxPerSession = 5, int $maxPerIp = 10, int $windowSeconds = 3600): bool
{
    $sessionOk = authRateLimitAllow($key, $maxPerSession, $windowSeconds);
    $ipOk      = authRateLimitByIp($key, $maxPerIp, $windowSeconds);
    
    return $sessionOk && $ipOk;
}
