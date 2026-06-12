<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../includes/OrderFulfillmentService.php';

// Guard: only available in non-production environments with the flag set
if (getenv('ENABLE_TEST_PAYMENT') !== 'true' || getenv('APP_ENV') === 'production') {
    sendResponse('error', 'Not available.', null, 404);
}

apiRequirePost();
$user  = apiRequireUser();
$input = apiInput();
validateCsrf();

$orderId = (int) ($input['order_id'] ?? 0);
if ($orderId <= 0) {
    sendResponse('error', 'order_id is required.', null, 400);
}

// Fetch order and verify ownership
$stmt = $conn->prepare('SELECT id, user_id, status FROM orders WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    sendResponse('error', 'Order not found.', null, 404);
}
if ((int) $order['user_id'] !== $user['id']) {
    sendResponse('error', 'Access denied.', null, 403);
}

$currentStatus = strtolower((string) $order['status']);
$alreadyPaid   = in_array($currentStatus, ['paid', 'processing', 'shipped', 'delivered'], true);

if (!$alreadyPaid) {
    if (!in_array($currentStatus, ['pending', 'awaiting_payment'], true)) {
        sendResponse('error', 'Order cannot be paid in its current state.', null, 409);
    }

    $upd = $conn->prepare("UPDATE orders SET status = 'paid', payment_status = 'paid', paid_at = NOW(), payment_method = 'test', status_updated_at = NOW() WHERE id = ?");
    $upd->bind_param('i', $orderId);
    if (!$upd->execute()) {
        sendResponse('error', 'Failed to update order status.', null, 500);
    }

    // Clear cart after test payment (mirrors what OrderPaymentService does for Razorpay)
    $clr = $conn->prepare('DELETE FROM cart WHERE user_id = ?');
    $clr->bind_param('i', $user['id']);
    $clr->execute();
}

// Fulfill downloads/inventory first; emails after response is flushed.
try {
    OrderFulfillmentService::fulfillPaidOrder($orderId, $conn, true);
} catch (Throwable $e) {
    error_log('test-pay.php: fulfillment failed for order ' . $orderId . ': ' . $e->getMessage());
}

flushJsonResponse('success', 'Test payment accepted.', [
    'order_id' => $orderId,
    'status'   => 'paid',
]);

try {
    OrderFulfillmentService::sendFulfillmentEmails($orderId, $conn);
} catch (Throwable $e) {
    error_log('test-pay.php: post-response emails failed for order ' . $orderId . ': ' . $e->getMessage());
}
exit;
