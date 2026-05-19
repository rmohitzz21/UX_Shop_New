<?php
// includes/config.php

// ── 0. Load environment variables ────────────────────────────────────────────
require_once __DIR__ . '/env.php';

// ── 1. Error reporting (never display in production) ─────────────────────────
$app_debug = (getenv('APP_DEBUG') === 'true');
error_reporting(E_ALL);
ini_set('display_errors',  $app_debug ? '1' : '0');
ini_set('display_startup_errors', $app_debug ? '1' : '0');
ini_set('log_errors', '1');

// Ensure logs directory exists
$logDir = dirname(__DIR__) . '/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
ini_set('error_log', $logDir . '/app_errors.log');

mysqli_report(MYSQLI_REPORT_OFF);

// ── 2. Security headers (sent before any output) ──────────────────────────────
if (!headers_sent()) {
    // Prevent clickjacking
    header('X-Frame-Options: SAMEORIGIN');
    // Prevent MIME sniffing
    header('X-Content-Type-Options: nosniff');
    // XSS protection (legacy browsers)
    header('X-XSS-Protection: 1; mode=block');
    // Referrer policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // Permissions policy
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    // Content Security Policy (includes Razorpay for payments)
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://checkout.razorpay.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data: blob: https://lh3.googleusercontent.com; connect-src 'self' https://api.razorpay.com https://lumberjack.razorpay.com; frame-src https://api.razorpay.com https://checkout.razorpay.com; frame-ancestors 'self';");
    // HSTS (only meaningful on HTTPS — harmless on HTTP dev)
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// ── 3. Session configuration ──────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400,     // 24 hours
        'path'     => '/',
        'httponly' => true,       // Block JS access to cookie
        'samesite' => 'Lax',      // CSRF mitigation
    ]);
    session_start();
}

// ── 4. Database connection ────────────────────────────────────────────────────
$host     = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';
$database = getenv('DB_NAME') ?: 'uxmerchandise';

try {
    $conn = new mysqli($host, $username, $password, $database);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    $conn->set_charset("utf8mb4");

} catch (Exception $e) {
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    http_response_code(500);
    echo json_encode([
        "status"  => "error",
        "message" => $app_debug ? $e->getMessage() : "Database connection error"
    ]);
    exit;
}

// ── 5. CSRF token ─────────────────────────────────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ── 6. Global helpers ─────────────────────────────────────────────────────────
require_once __DIR__ . '/helpers.php';
