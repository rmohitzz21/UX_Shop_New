<?php
require_once __DIR__ . '/../_admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', 'Method not allowed.', null, 405);
}
validateCsrf();
$input = adminInput();
$id    = (int) ($input['id'] ?? 0);
if ($id <= 0) {
    sendResponse('error', 'Order ID is required.', null, 422);
}

// Verify order exists first
$chk = $conn->prepare('SELECT id, status FROM orders WHERE id = ? LIMIT 1');
$chk->bind_param('i', $id);
$chk->execute();
$order = $chk->get_result()->fetch_assoc();
if (!$order) {
    sendResponse('error', 'Order not found.', null, 404);
}

$protected = ['paid', 'processing', 'shipped', 'delivered'];
$status = strtolower(trim((string) ($order['status'] ?? '')));
if (in_array($status, $protected, true)) {
    sendResponse('error', 'Paid or fulfilled orders cannot be deleted. Cancel the order instead.', null, 422);
}

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
    error_log('admin/order/delete.php: ' . $e->getMessage());
    sendResponse('error', 'Could not delete order.', null, 500);
}
