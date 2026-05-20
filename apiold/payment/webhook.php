<?php
/**
 * Razorpay webhooks — verifies X-Razorpay-Signature, re-fetches payment from API,
 * idempotent order updates. No session / no CSRF (signature only).
 *
 * Configure in Razorpay Dashboard: https://your-domain/api/payment/webhook.php
 * Env: RAZORPAY_WEBHOOK_SECRET (from dashboard webhook secret)
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/RazorpayClient.php';
require_once __DIR__ . '/../../includes/OrderPaymentService.php';

$webhookSecret = getenv('RAZORPAY_WEBHOOK_SECRET') ?: '';
if ($webhookSecret === '' || str_contains($webhookSecret, 'REPLACE_ME')) {
    http_response_code(503);
    echo json_encode(['status' => 'error', 'message' => 'Payment system not configured']);
    exit;
}

$rawBody = file_get_contents('php://input') ?: '';
$sig     = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';

if ($rawBody === '' || !rzp_verify_webhook_signature($rawBody, $sig, $webhookSecret)) {
    error_log('Razorpay webhook: invalid signature');
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid signature']);
    exit;
}

$payload = json_decode($rawBody, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}

$event = (string) ($payload['event'] ?? '');

try {
    if ($event === 'payment.captured') {
        $entity = $payload['payload']['payment']['entity'] ?? null;
        if (!is_array($entity)) {
            http_response_code(200);
            echo json_encode(['status' => 'ok', 'ignored' => true]);
            exit;
        }
        $rzpPaymentId = (string) ($entity['id'] ?? '');
        if ($rzpPaymentId === '') {
            http_response_code(200);
            echo json_encode(['status' => 'ok', 'ignored' => true]);
            exit;
        }

        $payRes = rzp_fetch_payment($rzpPaymentId);
        if (!$payRes['ok']) {
            error_log('Razorpay webhook: fetch payment failed ' . $rzpPaymentId);
            http_response_code(500);
            echo json_encode(['status' => 'error']);
            exit;
        }

        $payment     = $payRes['data'];
        $pStatus     = strtolower((string) ($payment['status'] ?? ''));
        $rzpOrderId  = (string) ($payment['order_id'] ?? '');
        $amountPaise = (int) ($payment['amount'] ?? 0);
        $currency    = strtolower((string) ($payment['currency'] ?? ''));

        if (!in_array($pStatus, ['captured', 'authorized'], true) || $rzpOrderId === '') {
            http_response_code(200);
            echo json_encode(['status' => 'ok', 'ignored' => true]);
            exit;
        }

        $stmt = $conn->prepare('SELECT id, user_id FROM orders WHERE razorpay_order_id = ? LIMIT 1');
        $stmt->bind_param('s', $rzpOrderId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            error_log("Razorpay webhook: no local order for razorpay_order_id={$rzpOrderId}");
            http_response_code(200);
            echo json_encode(['status' => 'ok', 'ignored' => true]);
            exit;
        }

        $internalId = (int) $row['id'];
        $userId     = (int) $row['user_id'];

        $result = order_capture_razorpay_payment(
            $conn,
            $internalId,
            null,
            $rzpPaymentId,
            $rzpOrderId,
            $amountPaise,
            $currency
        );

        if (!$result['ok']) {
            error_log('Razorpay webhook capture failed: ' . ($result['message'] ?? 'unknown'));
            // Return 200 if already terminal state to stop Razorpay retries storm
            $http = (int) ($result['http'] ?? 500);
            if (in_array($http, [400, 403, 404, 409], true)) {
                http_response_code(200);
                echo json_encode(['status' => 'ok', 'note' => 'not_applied']);
                exit;
            }
            http_response_code(500);
            echo json_encode(['status' => 'error']);
            exit;
        }

        $conn->close();
        http_response_code(200);
        echo json_encode(['status' => 'ok', 'duplicate' => !empty($result['duplicate'])]);
        exit;
    }

    if ($event === 'payment.failed') {
        $entity = $payload['payload']['payment']['entity'] ?? null;
        if (!is_array($entity)) {
            http_response_code(200);
            echo json_encode(['status' => 'ok', 'ignored' => true]);
            exit;
        }
        $rzpPaymentId = (string) ($entity['id'] ?? '');
        $rzpOrderId   = (string) ($entity['order_id'] ?? '');
        if ($rzpOrderId === '') {
            http_response_code(200);
            echo json_encode(['status' => 'ok', 'ignored' => true]);
            exit;
        }

        order_mark_payment_failed($conn, $rzpOrderId, $rzpPaymentId);
        $conn->close();
        http_response_code(200);
        echo json_encode(['status' => 'ok']);
        exit;
    }

    // Other events: acknowledge
    http_response_code(200);
    echo json_encode(['status' => 'ok', 'ignored' => true]);
} catch (Throwable $e) {
    error_log('Razorpay webhook exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error']);
}
