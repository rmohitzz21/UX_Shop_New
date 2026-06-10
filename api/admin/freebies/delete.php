<?php
require_once __DIR__ . '/../_admin.php';
require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../resources/_helpers.php';
require_once __DIR__ . '/../../../includes/DigitalStorageService.php';

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

drEnsureFreebieResourcesColumn($conn);

$conn->begin_transaction();
try {
    $res = $conn->prepare('SELECT storage_key FROM digital_resources WHERE freebie_id = ? AND storage_key IS NOT NULL');
    $res->bind_param('i', $id);
    $res->execute();
    foreach ($res->get_result()->fetch_all(MYSQLI_ASSOC) as $sr) {
        if (!empty($sr['storage_key'])) {
            DigitalStorageService::delete((string) $sr['storage_key']);
        }
    }

    $delRes = $conn->prepare('DELETE FROM digital_resources WHERE freebie_id = ?');
    $delRes->bind_param('i', $id);
    $delRes->execute();

    $delete = $conn->prepare('DELETE FROM freebies WHERE id = ?');
    $delete->bind_param('i', $id);
    $delete->execute();
    if ($delete->affected_rows < 1) {
        throw new RuntimeException('Freebie not found.');
    }

    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    sendResponse('error', 'Could not delete freebie.', null, 500);
}

sendResponse('success', 'Freebie deleted.');
