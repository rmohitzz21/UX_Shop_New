<?php
require_once __DIR__ . '/../_admin.php';
require_once __DIR__ . '/../../../includes/DigitalStorageService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', 'Method not allowed.', null, 405);
}
validateCsrf();

$input = adminInput();
$id    = (int) ($input['id'] ?? 0);
if ($id <= 0) {
    sendResponse('error', 'Resource id is required.', null, 422);
}

$chk = $conn->prepare('SELECT id, storage_key FROM digital_resources WHERE id = ? LIMIT 1');
$chk->bind_param('i', $id);
$chk->execute();
$row = $chk->get_result()->fetch_assoc();
if (!$row) {
    sendResponse('error', 'Resource not found.', null, 404);
}

if (!empty($row['storage_key'])) {
    DigitalStorageService::delete((string) $row['storage_key']);
}

$stmt = $conn->prepare('UPDATE digital_resources SET is_active = 0, storage_key = NULL WHERE id = ?');
$stmt->bind_param('i', $id);
if (!$stmt->execute()) {
    sendResponse('error', 'Could not remove resource.', null, 500);
}

sendResponse('success', 'Resource removed.');
