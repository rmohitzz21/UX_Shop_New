<?php
header('Content-Type: application/json');
require_once '../../../includes/config.php';
require_once '../../../includes/helpers.php';

// Enforce Admin Access
requireAdmin();
validateCsrf();

if ($conn->connect_error) {
    sendResponse("error", "Database connection failed", null, 500);
}

// Get Input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    sendResponse("error", "Invalid input", null, 400);
}

$orderNumber = $input['order_number'] ?? '';
$status = $input['status'] ?? '';

if (empty($orderNumber) || empty($status)) {
    sendResponse("error", "Order number and status are required", null, 400);
}

// Validate status (payment + fulfillment lifecycle)
$validStatuses = [
    'pending', 'awaiting_payment', 'paid', 'processing', 'shipped', 'delivered', 'failed', 'cancelled',
];
if (!in_array($status, $validStatuses, true)) {
    sendResponse("error", "Invalid status value", null, 400);
}

// Prevent unsafe status jumps in lifecycle.
$allowedTransitions = [
    'pending' => ['awaiting_payment', 'paid', 'processing', 'cancelled', 'failed'],
    'awaiting_payment' => ['paid', 'failed', 'cancelled'],
    'paid' => ['processing', 'cancelled'],
    'processing' => ['shipped', 'cancelled'],
    'shipped' => ['delivered'],
    'delivered' => [],
    'failed' => [],
    'cancelled' => [],
];

$currentStmt = $conn->prepare("SELECT status FROM orders WHERE order_number = ? LIMIT 1");
$currentStmt->bind_param("s", $orderNumber);
$currentStmt->execute();
$currentRow = $currentStmt->get_result()->fetch_assoc();
$currentStmt->close();
if (!$currentRow) {
    sendResponse("error", "Order not found", null, 404);
}
$currentStatus = strtolower((string) ($currentRow['status'] ?? ''));
if ($currentStatus === $status) {
    sendResponse("success", "Order status updated (no change)");
}
if (!isset($allowedTransitions[$currentStatus]) || !in_array($status, $allowedTransitions[$currentStatus], true)) {
    sendResponse("error", "Invalid status transition: {$currentStatus} -> {$status}", null, 400);
}

$stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_number = ?");
$stmt->bind_param("ss", $status, $orderNumber);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        sendResponse("success", "Order status updated successfully");
    } else {
        sendResponse("error", "Order status update failed", null, 500);
    }
} else {
    sendResponse("error", "Failed to update order status", null, 500);
}

$stmt->close();
$conn->close();
?>
