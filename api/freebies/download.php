<?php
require_once __DIR__ . '/../../includes/config.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    exit('Invalid request.');
}

$stmt = $conn->prepare('SELECT name, file_url FROM freebies WHERE id = ? AND is_active = 1 LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$freebie = $stmt->get_result()->fetch_assoc();

if (!$freebie || empty($freebie['file_url'])) {
    http_response_code(404);
    exit('File not found.');
}

$fileUrl = (string) $freebie['file_url'];

// Validate URL — only allow http/https absolute URLs or relative paths
if (!preg_match('#^(https?://|/)#i', $fileUrl)) {
    http_response_code(400);
    error_log('freebies/download.php: invalid file_url for freebie id=' . $id);
    exit('Invalid file URL.');
}

// Increment download counter using prepared statement
$update = $conn->prepare('UPDATE freebies SET download_count = download_count + 1 WHERE id = ?');
$update->bind_param('i', $id);
$update->execute();

header('Location: ' . $fileUrl);
exit;
