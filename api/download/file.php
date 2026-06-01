<?php
require_once __DIR__ . '/../../includes/config.php';
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

DigitalDownloadService::validateAndServe($token, (int) $_SESSION['user_id'], $conn);
