<?php
require_once __DIR__ . '/../_admin.php';
validateCsrf();
$input = adminInput();
$id = (int) ($input['id'] ?? 0);
if ($id <= 0) sendResponse('error', 'Order ID is required.', null, 422);
$conn->begin_transaction();
try {
    $stmt = $conn->prepare('DELETE FROM order_items WHERE order_id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt = $conn->prepare('DELETE FROM orders WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $conn->commit();
    sendResponse('success', 'Order deleted.');
} catch (Throwable $e) {
    $conn->rollback();
    sendResponse('error', 'Could not delete order.', null, 500);
}
