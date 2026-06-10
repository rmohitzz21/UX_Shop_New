<?php
require_once __DIR__ . '/../_admin.php';
require_once __DIR__ . '/../../../includes/DigitalStorageService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { sendResponse('error', 'Method not allowed.', null, 405); }
validateCsrf();
$input = adminInput();
$id = (int) ($input['id'] ?? 0);
if ($id <= 0) sendResponse('error', 'Bundle ID is required.', null, 422);

$exists = $conn->prepare('SELECT id FROM bundles WHERE id = ? LIMIT 1');
$exists->bind_param('i', $id);
$exists->execute();
if (!$exists->get_result()->fetch_assoc()) {
    sendResponse('error', 'Bundle not found.', null, 404);
}

// Bundles with orders are soft-deleted (archived) to preserve order history
$chk = $conn->prepare('SELECT COUNT(*) AS c FROM order_items WHERE bundle_id = ? LIMIT 1');
$chk->bind_param('i', $id);
$chk->execute();
$orderCount = (int) ($chk->get_result()->fetch_assoc()['c'] ?? 0);

if ($orderCount > 0) {
    $stmt = $conn->prepare('UPDATE bundles SET is_active = 0 WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    sendResponse('success', 'Bundle archived (has existing orders).', ['action' => 'archived']);
}

// No orders — cascade-delete everything then hard-delete
$conn->begin_transaction();
try {
    // Remove stored files and resource records
    $res = $conn->prepare('SELECT storage_key FROM digital_resources WHERE bundle_id = ? AND storage_key IS NOT NULL');
    $res->bind_param('i', $id);
    $res->execute();
    $storageRows = $res->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($storageRows as $sr) {
        if (!empty($sr['storage_key'])) {
            DigitalStorageService::delete((string) $sr['storage_key']);
        }
    }

    $delRes = $conn->prepare('DELETE FROM digital_resources WHERE bundle_id = ?');
    $delRes->bind_param('i', $id);
    $delRes->execute();

    $delItems = $conn->prepare('DELETE FROM bundle_items WHERE bundle_id = ?');
    $delItems->bind_param('i', $id);
    $delItems->execute();

    $delBundle = $conn->prepare('DELETE FROM bundles WHERE id = ?');
    $delBundle->bind_param('i', $id);
    $delBundle->execute();

    if ($delBundle->affected_rows < 1) {
        throw new RuntimeException('Bundle row not deleted.');
    }
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    sendResponse('error', 'Could not delete bundle: ' . $e->getMessage(), null, 500);
}

sendResponse('success', 'Bundle deleted.', ['action' => 'deleted']);
