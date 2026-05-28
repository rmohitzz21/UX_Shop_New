<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../includes/RazorpayClient.php';

apiRequirePost();
$user  = apiRequireUser();
$input = apiInput();
validateCsrf();

$orderId = (int) ($input['order_id'] ?? 0);
if ($orderId <= 0) {
    sendResponse('error', 'Order ID is required.', null, 422);
}

// Load the order — must belong to this user and be awaiting payment
$stmt = $conn->prepare('SELECT id, total, status, razorpay_order_id FROM orders WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->bind_param('ii', $orderId, $user['id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    sendResponse('error', 'Order not found.', null, 404);
}
if (!in_array($order['status'], ['Pending', 'pending', 'awaiting_payment'], true)) {
    sendResponse('error', 'This order is not awaiting payment.', null, 409);
}

// Reuse existing Razorpay order if already created (idempotent)
if (!empty($order['razorpay_order_id'])) {
    $keyId = getenv('RAZORPAY_KEY_ID') ?: '';
    sendResponse('success', 'Razorpay order ready.', [
        'razorpay_order_id' => $order['razorpay_order_id'],
        'amount_paise'      => rzp_order_total_to_paise((float) $order['total']),
        'currency'          => 'INR',
        'key_id'            => $keyId,
    ]);
}

$amountPaise = rzp_order_total_to_paise((float) $order['total']);
$result = rzp_api_post('/orders', [
    'amount'          => $amountPaise,
    'currency'        => 'INR',
    'receipt'         => (string) $orderId,
    'payment_capture' => 1,
]);

if (!$result['ok']) {
    error_log('razorpay-create-order.php: ' . ($result['error'] ?? 'unknown error'));
    sendResponse('error', 'Could not initiate payment. Please try again.', null, 502);
}

$rzpOrderId = (string) $result['data']['id'];

// Persist Razorpay order ID and mark awaiting_payment atomically
$upd = $conn->prepare('UPDATE orders SET razorpay_order_id = ?, status = "awaiting_payment", status_updated_at = NOW() WHERE id = ? AND user_id = ?');
$upd->bind_param('sii', $rzpOrderId, $orderId, $user['id']);
if (!$upd->execute() || $upd->affected_rows < 1) {
    sendResponse('error', 'Could not update order. Please try again.', null, 500);
}

$keyId = getenv('RAZORPAY_KEY_ID') ?: '';
sendResponse('success', 'Razorpay order created.', [
    'razorpay_order_id' => $rzpOrderId,
    'amount_paise'      => $amountPaise,
    'currency'          => 'INR',
    'key_id'            => $keyId,
]);
