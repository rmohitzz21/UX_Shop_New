<?php
/**
 * Razorpay Webhook Handler
 * Configure webhook URL in Razorpay dashboard: https://your-domain/api/payment/webhook.php
 * Events to subscribe: payment.captured, payment.failed
 * Set webhook secret in .env: RAZORPAY_WEBHOOK_SECRET=...
 */

// Webhooks bypass normal session/CSRF — use raw bootstrap without session overhead
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/RazorpayClient.php';
require_once __DIR__ . '/../../includes/OrderPaymentService.php';
require_once __DIR__ . '/../../includes/OrderFulfillmentService.php';
require_once __DIR__ . '/../../includes/EmailService.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    exit;
}

$rawBody   = (string) file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';

$webhookSecret = getenv('RAZORPAY_WEBHOOK_SECRET') ?: '';
if ($webhookSecret === '' || str_contains($webhookSecret, 'REPLACE_ME')) {
    if (getenv('APP_DEBUG') !== 'true') {
        error_log('webhook.php: RAZORPAY_WEBHOOK_SECRET not configured — refusing webhook in production');
        http_response_code(503);
        echo json_encode(['status' => 'error', 'message' => 'Webhook not configured.']);
        exit;
    }
    error_log('webhook.php: RAZORPAY_WEBHOOK_SECRET not configured — accepting without verification (debug only)');
} else {
    if (!rzp_verify_webhook_signature($rawBody, $signature, $webhookSecret)) {
        error_log('webhook.php: invalid signature');
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid signature.']);
        exit;
    }
}

$event = json_decode($rawBody, true);
if (!is_array($event) || empty($event['event'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid payload.']);
    exit;
}

$eventName   = (string) $event['event'];
$payloadData = $event['payload']['payment']['entity'] ?? [];
$rzpPaymentId = (string) ($payloadData['id'] ?? '');
$rzpOrderId   = (string) ($payloadData['order_id'] ?? '');
$amountPaise  = (int)    ($payloadData['amount'] ?? 0);
$currency     = strtolower((string) ($payloadData['currency'] ?? 'inr'));

// Look up internal order by Razorpay order ID
$stmt = $conn->prepare('SELECT id FROM orders WHERE razorpay_order_id = ? LIMIT 1');
$stmt->bind_param('s', $rzpOrderId);
$stmt->execute();
$orderRow = $stmt->get_result()->fetch_assoc();

if (!$orderRow) {
    // Not our order — return 200 to prevent Razorpay retrying
    echo json_encode(['status' => 'success', 'message' => 'Order not found — ignored.']);
    exit;
}

$internalOrderId = (int) $orderRow['id'];

switch ($eventName) {
    case 'payment.captured':
        $result = order_capture_razorpay_payment(
            $conn,
            $internalOrderId,
            null, // no session user check in webhook
            $rzpPaymentId,
            $rzpOrderId,
            $amountPaise,
            $currency
        );
        if (!$result['ok'] && empty($result['duplicate'])) {
            error_log("webhook.php: capture failed for order {$internalOrderId}: " . ($result['message'] ?? ''));
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Capture failed.']);
            exit;
        }
        // Fulfill: generate downloads + send confirmation email (idempotent)
        try {
            OrderFulfillmentService::fulfillPaidOrder($internalOrderId, $conn);
        } catch (Throwable $e) {
            error_log("webhook.php: fulfillment failed for order {$internalOrderId}: " . $e->getMessage());
        }
        break;

    case 'payment.failed':
        order_mark_payment_failed($conn, $rzpOrderId, $rzpPaymentId);
        try {
            EmailService::sendPaymentFailed($internalOrderId, $conn);
            EmailService::notifyAdminPaymentFailed($internalOrderId, $conn);
        } catch (Throwable $e) {
            error_log('webhook.php: payment failed emails: ' . $e->getMessage());
        }
        break;

    default:
        // Unhandled event — acknowledge silently
        break;
}

http_response_code(200);
echo json_encode(['status' => 'success']);
exit;
