<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../includes/DigitalDownloadService.php';

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
$stmt = $conn->prepare('SELECT id, user_id, status, order_number FROM orders WHERE id = ? LIMIT 1');
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

    $upd = $conn->prepare("UPDATE orders SET status = 'paid', payment_method = 'test', status_updated_at = NOW() WHERE id = ?");
    $upd->bind_param('i', $orderId);
    if (!$upd->execute()) {
        sendResponse('error', 'Failed to update order status.', null, 500);
    }
}

// Generate download tokens (idempotent)
try {
    DigitalDownloadService::generateDownloadsForOrder($orderId, $conn);
} catch (Throwable $e) {
    error_log('test-pay.php: download generation failed for order ' . $orderId . ': ' . $e->getMessage());
}

// Send confirmation email (non-fatal, only on first payment)
if (!$alreadyPaid) {
    try {
        $emailStmt = $conn->prepare('
            SELECT u.email, u.first_name, u.last_name, o.order_number, o.total
            FROM orders o JOIN users u ON u.id = o.user_id
            WHERE o.id = ? LIMIT 1
        ');
        $emailStmt->bind_param('i', $orderId);
        $emailStmt->execute();
        $emailRow = $emailStmt->get_result()->fetch_assoc();
        if ($emailRow) {
            $itemStmt = $conn->prepare('SELECT product_name AS name, quantity, price FROM order_items WHERE order_id = ?');
            $itemStmt->bind_param('i', $orderId);
            $itemStmt->execute();
            $emailItems = $itemStmt->get_result()->fetch_all(MYSQLI_ASSOC);

            $appUrl = rtrim((string) (getenv('APP_URL') ?: ''), '/');
            sendOrderConfirmationEmail(
                $emailRow['email'],
                trim(($emailRow['first_name'] ?? '') . ' ' . ($emailRow['last_name'] ?? '')),
                [
                    'order_number' => $emailRow['order_number'],
                    'date'         => date('Y-m-d'),
                    'items'        => $emailItems,
                    'total'        => (float) $emailRow['total'],
                    'orders_url'   => $appUrl . '/orders.php',
                ]
            );
        }
    } catch (Throwable $ignored) {
        error_log('test-pay.php: email failed for order ' . $orderId);
    }
}

sendResponse('success', 'Test payment accepted.', [
    'order_id' => $orderId,
    'status'   => 'paid',
]);
