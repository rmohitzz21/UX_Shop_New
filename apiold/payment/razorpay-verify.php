<?php
/**
 * Verifies Razorpay checkout signature, re-fetches payment from Razorpay API,
 * compares paid amount/currency/order_id to our order, then marks paid (idempotent).
 *
 * POST JSON:
 * {
 *   "razorpay_order_id": "order_xxx",
 *   "razorpay_payment_id": "pay_xxx",
 *   "razorpay_signature": "xxx",
 *   "order_id": 123
 * }
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/RazorpayClient.php';
require_once __DIR__ . '/../../includes/OrderPaymentService.php';

requireUserAuth();
validateCsrf();

$input = json_decode(file_get_contents('php://input'), true) ?: [];

$rzpOrderId   = trim((string) ($input['razorpay_order_id'] ?? ''));
$rzpPaymentId = trim((string) ($input['razorpay_payment_id'] ?? ''));
$rzpSignature = trim((string) ($input['razorpay_signature'] ?? ''));
$internalId   = (int) ($input['order_id'] ?? 0);

if ($rzpOrderId === '' || $rzpPaymentId === '' || $rzpSignature === '' || $internalId <= 0) {
    sendResponse('error', 'Missing required payment fields', null, 400);
}

$keySecret = getenv('RAZORPAY_KEY_SECRET') ?: '';
if ($keySecret === '' || str_contains($keySecret, 'REPLACE_ME')) {
    sendResponse('error', 'Payment system not configured', null, 503);
}

if (!rzp_verify_checkout_signature($rzpOrderId, $rzpPaymentId, $rzpSignature, $keySecret)) {
    error_log("Razorpay signature mismatch. order_id={$internalId} payment={$rzpPaymentId}");
    sendResponse('error', 'Payment verification failed: invalid signature', null, 400);
}

$payRes = rzp_fetch_payment($rzpPaymentId);
if (!$payRes['ok']) {
    error_log('Razorpay verify: could not fetch payment ' . $rzpPaymentId);
    sendResponse('error', $payRes['error'] ?? 'Could not verify payment', null, $payRes['http'] ?? 502);
}

$payment = $payRes['data'];
$pStatus = strtolower((string) ($payment['status'] ?? ''));
if (!in_array($pStatus, ['captured', 'authorized'], true)) {
    error_log("Razorpay verify: payment not successful status={$pStatus} pay={$rzpPaymentId}");
    sendResponse('error', 'Payment not completed', null, 400);
}

$payOrderId = (string) ($payment['order_id'] ?? '');
if ($payOrderId === '' || !hash_equals($payOrderId, $rzpOrderId)) {
    error_log("Razorpay verify: order_id mismatch pay={$rzpPaymentId}");
    sendResponse('error', 'Payment order mismatch', null, 400);
}

$amountPaise = (int) ($payment['amount'] ?? 0);
$currency    = strtolower((string) ($payment['currency'] ?? ''));

$userId = (int) $_SESSION['user_id'];

$result = order_capture_razorpay_payment(
    $conn,
    $internalId,
    $userId,
    $rzpPaymentId,
    $rzpOrderId,
    $amountPaise,
    $currency
);

if (!$result['ok']) {
    sendResponse('error', $result['message'] ?? 'Payment failed', null, $result['http'] ?? 400);
}

$duplicate = !empty($result['duplicate']);
$conn->close();

sendResponse('success', $duplicate ? 'Payment already confirmed' : 'Payment verified', [
    'order_id'   => $internalId,
    'payment_id' => $rzpPaymentId,
    'status'     => 'paid',
    'duplicate'  => $duplicate,
]);
