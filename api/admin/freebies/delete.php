<?php
require_once __DIR__ . '/../_admin.php';
require_once __DIR__ . '/_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', 'Method not allowed.', null, 405);
}
validateCsrf();
adminEnsureFreebiesTable($conn);

$input = adminInput();
$id    = (int) ($input['id'] ?? 0);
if ($id <= 0) {
    sendResponse('error', 'Freebie ID is required.', null, 422);
}

$stmt = $conn->prepare('SELECT id FROM freebies WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) {
    sendResponse('error', 'Freebie not found.', null, 404);
}

$delete = $conn->prepare('DELETE FROM freebies WHERE id = ?');
$delete->bind_param('i', $id);
$delete->execute();
if ($delete->affected_rows < 1) {
    sendResponse('error', 'Freebie not found.', null, 404);
}
sendResponse('success', 'Freebie deleted.');
