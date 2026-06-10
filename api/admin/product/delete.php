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
    sendResponse('error', 'Product ID is required.', null, 422);
}

$exists = $conn->prepare('SELECT id FROM products WHERE id = ? LIMIT 1');
$exists->bind_param('i', $id);
$exists->execute();
if (!$exists->get_result()->fetch_assoc()) {
    sendResponse('error', 'Product not found.', null, 404);
}

// Products with orders are soft-deleted (archived) to preserve order history
$chk = $conn->prepare('SELECT COUNT(*) AS c FROM order_items WHERE product_id = ? LIMIT 1');
$chk->bind_param('i', $id);
$chk->execute();
$orderCount = (int) ($chk->get_result()->fetch_assoc()['c'] ?? 0);

if ($orderCount > 0) {
    $stmt = $conn->prepare('UPDATE products SET is_active = 0 WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    sendResponse('success', 'Product archived (has existing orders).', ['action' => 'archived']);
}

// No orders — cascade-delete digital resources then hard-delete
$conn->begin_transaction();
try {
    // Remove stored files and resource records
    $res = $conn->prepare('SELECT storage_key FROM digital_resources WHERE product_id = ? AND storage_key IS NOT NULL');
    $res->bind_param('i', $id);
    $res->execute();
    $storageRows = $res->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($storageRows as $sr) {
        if (!empty($sr['storage_key'])) {
            DigitalStorageService::delete((string) $sr['storage_key']);
        }
    }

    $delRes = $conn->prepare('DELETE FROM digital_resources WHERE product_id = ?');
    $delRes->bind_param('i', $id);
    $delRes->execute();

    $delProd = $conn->prepare('DELETE FROM products WHERE id = ?');
    $delProd->bind_param('i', $id);
    $delProd->execute();

    if ($delProd->affected_rows < 1) {
        throw new RuntimeException('Product row not deleted.');
    }
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    sendResponse('error', 'Could not delete product: ' . $e->getMessage(), null, 500);
}

sendResponse('success', 'Product deleted.', ['action' => 'deleted']);
