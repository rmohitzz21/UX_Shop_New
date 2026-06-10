<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/DigitalStorageService.php';
require_once __DIR__ . '/../../includes/DigitalDownloadService.php';

// Auth required
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Please sign in to download files.']);
    exit;
}

$token = trim((string) ($_GET['token'] ?? ''));
if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid download token.']);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$exp    = (int) ($_GET['exp'] ?? 0);
$sig    = trim((string) ($_GET['sig'] ?? ''));

if ($exp > 0 || $sig !== '') {
    if (!DigitalDownloadService::validateDownloadAccess($token, $userId, $exp, $sig)) {
        http_response_code(410);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Download link expired. Open your order and click Download again.']);
        exit;
    }
} elseif (DigitalStorageService::getDriver() === 'local') {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Signed download link required. Open your order and click Download again.']);
    exit;
}

DigitalDownloadService::validateAndServe($token, $userId, $conn);
