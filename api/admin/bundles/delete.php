<?php
require_once __DIR__ . '/../_admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { sendResponse('error', 'Method not allowed.', null, 405); }
validateCsrf();
$input = adminInput();
$id = (int) ($input['id'] ?? 0);
if ($id <= 0) sendResponse('error', 'Bundle ID is required.', null, 422);

$conn->begin_transaction();
try {
    $items = $conn->prepare('DELETE FROM bundle_items WHERE bundle_id = ?');
    $items->bind_param('i', $id);
    $items->execute();
    $bundle = $conn->prepare('DELETE FROM bundles WHERE id = ?');
    $bundle->bind_param('i', $id);
    $bundle->execute();
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    sendResponse('error', 'Could not delete bundle.', null, 500);
}

sendResponse('success', 'Bundle deleted.');
