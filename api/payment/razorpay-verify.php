<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../includes/RazorpayClient.php';
require_once __DIR__ . '/../../includes/OrderPaymentService.php';
require_once __DIR__ . '/../../includes/OrderFulfillmentService.php';

apiRequirePost();
$user  = apiRequireUser();
$input = apiInput();
validateCsrf();

$rzpOrderId   = trim((string) ($input['razorpay_order_id'] ?? ''));
$rzpPaymentId = trim((string) ($input['razorpay_payment_id'] ?? ''));
$rzpSignature = trim((string) ($input['razorpay_signature'] ?? ''));
$internalOrderId = (int) ($input['order_id'] ?? 0);

if ($rzpOrderId === '' || $rzpPaymentId === '' || $rzpSignature === '' || $internalOrderId <= 0) {
    sendResponse('error', 'Payment verification fields are required.', null, 422);
}

// Validate field formats to prevent injection
if (!preg_match('/^[a-zA-Z0-9_]+$/', $rzpOrderId) ||
    !preg_match('/^[a-zA-Z0-9_]+$/', $rzpPaymentId) ||
    !preg_match('/^[a-f0-9]+$/', $rzpSignature)) {
    sendResponse('error', 'Invalid payment data format.', null, 400);
}

$keySecret = getenv('RAZORPAY_KEY_SECRET') ?: '';
if ($keySecret === '' || str_contains($keySecret, 'REPLACE_ME')) {
    error_log('razorpay-verify.php: RAZORPAY_KEY_SECRET not configured');
    sendResponse('error', 'Payment system not configured.', null, 503);
}

// Verify checkout signature server-side
if (!rzp_verify_checkout_signature($rzpOrderId, $rzpPaymentId, $rzpSignature, $keySecret)) {
    error_log("razorpay-verify.php: signature mismatch order_id={$internalOrderId}");
    sendResponse('error', 'Payment signature verification failed.', null, 400);
}

// Fetch actual amount from Razorpay to prevent client-side tampering
$paymentResult = rzp_fetch_payment($rzpPaymentId);
if (!$paymentResult['ok']) {
    error_log('razorpay-verify.php: fetch payment failed for ' . $rzpPaymentId);
    sendResponse('error', 'Could not verify payment with gateway.', null, 502);
}

$paymentData     = $paymentResult['data'] ?? [];
$amountFromGw    = (int) ($paymentData['amount'] ?? 0);
$currencyFromGw  = strtolower((string) ($paymentData['currency'] ?? ''));

$result = order_capture_razorpay_payment(
    $conn,
    $internalOrderId,
    $user['id'],
    $rzpPaymentId,
    $rzpOrderId,
    $amountFromGw,
    $currencyFromGw
);

if (!$result['ok']) {
    $code = $result['http'] ?? 400;
    sendResponse('error', $result['message'] ?? 'Payment capture failed.', null, $code);
}

// Fulfill downloads/inventory first (fast); emails run after response is flushed.
try {
    OrderFulfillmentService::fulfillPaidOrder($internalOrderId, $conn, true);
} catch (Throwable $e) {
    error_log('razorpay-verify.php: fulfillment failed for order ' . $internalOrderId . ': ' . $e->getMessage());
}

flushJsonResponse('success', 'Payment verified successfully.', [
    'order_id'   => $internalOrderId,
    'duplicate'  => (bool) ($result['duplicate'] ?? false),
]);

try {
    OrderFulfillmentService::sendFulfillmentEmails($internalOrderId, $conn);
} catch (Throwable $e) {
    error_log('razorpay-verify.php: post-response emails failed for order ' . $internalOrderId . ': ' . $e->getMessage());
}
exit;
