<?php
header('Content-Type: application/json');
require_once '../../../includes/config.php';
require_once '../../../includes/helpers.php';

// Authentication Check — use canonical requireAdmin() which checks admin_id
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', 'Invalid request method', null, 405);
}
validateCsrf();

$input = json_decode(file_get_contents('php://input'), true);
$order_id = $input['id'] ?? null;

if (!$order_id) {
    // Try POST form data as fallback
    $order_id = $_POST['id'] ?? null;
}

if (!$order_id) {
    sendResponse('error', 'Order ID is required', null, 400);
}

// Start Transaction
$conn->begin_transaction();

try {
    // Delete order items first
    $stmtItems = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
    $stmtItems->bind_param("i", $order_id);
    if (!$stmtItems->execute()) {
        throw new Exception('Failed to delete order items');
    }
    $stmtItems->close();

    // Delete order
    $stmtOrder = $conn->prepare("DELETE FROM orders WHERE id = ?");
    $stmtOrder->bind_param("i", $order_id);
    if (!$stmtOrder->execute()) {
        throw new Exception('Failed to delete order');
    }
    
    if ($stmtOrder->affected_rows === 0) {
        throw new RuntimeException('Order not found or already deleted');
    }
    $stmtOrder->close();

    $conn->commit();
    sendResponse('success', 'Order deleted successfully');

} catch (RuntimeException $e) {
    $conn->rollback();
    sendResponse('error', $e->getMessage(), null, 404);
} catch (Exception $e) {
    $conn->rollback();
    error_log('admin/order/delete failed: ' . $e->getMessage());
    sendResponse('error', 'Failed to delete order', null, 500);
}

$conn->close();
