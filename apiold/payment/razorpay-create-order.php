<?php
/**
 * Creates a Razorpay Order using ONLY the server-side order total.
 * Client sends internal order_id — never amount or currency from the browser.
 *
 * POST JSON: { "order_id": 123 }
 * Requires: logged-in user, CSRF, order.status = awaiting_payment, order.user_id = session
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/RazorpayClient.php';

requireUserAuth();
validateCsrf();

$input = json_decode(file_get_contents('php://input'), true) ?: [];

// Reject any client-supplied amount/currency (do not trust)
if (isset($input['amount']) || isset($input['currency']) || isset($input['total'])) {
    error_log('razorpay-create-order: rejected request containing amount/currency/total');
    sendResponse('error', 'Invalid request', null, 400);
}

$internalOrderId = isset($input['order_id']) ? (int) $input['order_id'] : 0;
if ($internalOrderId <= 0) {
    sendResponse('error', 'order_id is required', null, 400);
}

$userId = (int) $_SESSION['user_id'];

$stmt = $conn->prepare(
    'SELECT id, total, status, user_id, order_number FROM orders WHERE id = ? LIMIT 1'
);
$stmt->bind_param('i', $internalOrderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    sendResponse('error', 'Order not found', null, 404);
}

if ((int) $order['user_id'] !== $userId) {
    sendResponse('error', 'Access denied', null, 403);
}

if ($order['status'] !== 'awaiting_payment') {
    sendResponse('error', 'Order is not awaiting payment', null, 400);
}

$total = (float) $order['total'];
if ($total <= 0) {
    sendResponse('error', 'Invalid order total', null, 400);
}

$currency        = 'INR';
$amountInPaise = rzp_order_total_to_paise($total);
$receipt         = 'uxp_' . $internalOrderId . '_' . bin2hex(random_bytes(4));

$postPayload = [
    'amount'   => $amountInPaise,
    'currency' => $currency,
    'receipt'  => $receipt,
    'notes'    => [
        'internal_order_id' => (string) $internalOrderId,
        'user_id'           => (string) $userId,
        'source'            => 'uxpacific_shop',
    ],
];

$rz = rzp_api_post('/orders', $postPayload);
if (!$rz['ok']) {
    sendResponse('error', $rz['error'] ?? 'Gateway error', null, $rz['http'] ?? 502);
}

$rzpResponse = $rz['data'];
$rzpOrderId  = $rzpResponse['id'];

$upd = $conn->prepare(
    'UPDATE orders SET razorpay_order_id = ? WHERE id = ? AND user_id = ? AND status = ?'
);
$await = 'awaiting_payment';
$upd->bind_param('siis', $rzpOrderId, $internalOrderId, $userId, $await);
$upd->execute();
if ($upd->affected_rows === 0) {
    $upd->close();
    error_log("razorpay-create-order: failed to store razorpay_order_id for order {$internalOrderId}");
    sendResponse('error', 'Could not link payment session', null, 409);
}
$upd->close();

$keyId = getenv('RAZORPAY_KEY_ID') ?: '';
if ($keyId === '' || str_contains($keyId, 'REPLACE_ME')) {
    sendResponse('error', 'Payment system not configured', null, 503);
}

$conn->close();

sendResponse('success', 'Payment order created', [
    'razorpay_order_id' => $rzpOrderId,
    'amount_in_paise'   => $amountInPaise,
    'currency'          => $currency,
    'key_id'            => $keyId,
    'order_id'          => $internalOrderId,
]);
