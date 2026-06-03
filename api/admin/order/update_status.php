<?php
require_once __DIR__ . '/../_admin.php';
require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../../../includes/OrderFulfillmentService.php';
require_once __DIR__ . '/../../../includes/EmailService.php';
require_once __DIR__ . '/../../../includes/InventoryReservationService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', 'Method not allowed.', null, 405);
}
validateCsrf();
$input       = adminInput();
$id          = (int) ($input['id'] ?? 0);
$orderNumber = trim((string) ($input['order_number'] ?? ''));
$status      = adminCanonicalOrderStatus((string) ($input['status'] ?? ''));

if (!in_array($status, adminAllowedOrderStatuses(), true)) {
    sendResponse('error', 'Invalid status.', null, 422);
}

$paidStatuses = ['paid', 'processing', 'shipped', 'delivered'];

if ($id > 0) {
    $chk = $conn->prepare('SELECT id, status FROM orders WHERE id = ? LIMIT 1');
    $chk->bind_param('i', $id);
    $chk->execute();
    $row = $chk->get_result()->fetch_assoc();
    if (!$row) {
        sendResponse('error', 'Order not found.', null, 404);
    }
    if (adminCanonicalOrderStatus((string) $row['status']) === $status) {
        sendResponse('success', 'Order status is already ' . $status . '.');
    }
    if (in_array($status, $paidStatuses, true)) {
        $stmt = $conn->prepare("UPDATE orders SET status = ?, payment_status = 'paid', paid_at = COALESCE(paid_at, NOW()), status_updated_at = NOW() WHERE id = ?");
    } elseif ($status === 'failed') {
        $stmt = $conn->prepare("UPDATE orders SET status = ?, payment_status = 'failed', status_updated_at = NOW() WHERE id = ?");
    } else {
        $stmt = $conn->prepare('UPDATE orders SET status = ?, status_updated_at = NOW() WHERE id = ?');
    }
    $stmt->bind_param('si', $status, $id);
} elseif ($orderNumber !== '') {
    $chk = $conn->prepare('SELECT id, status FROM orders WHERE order_number = ? LIMIT 1');
    $chk->bind_param('s', $orderNumber);
    $chk->execute();
    $row = $chk->get_result()->fetch_assoc();
    if (!$row) {
        sendResponse('error', 'Order not found.', null, 404);
    }
    if (adminCanonicalOrderStatus((string) $row['status']) === $status) {
        sendResponse('success', 'Order status is already ' . $status . '.');
    }
    if (in_array($status, $paidStatuses, true)) {
        $stmt = $conn->prepare("UPDATE orders SET status = ?, payment_status = 'paid', paid_at = COALESCE(paid_at, NOW()), status_updated_at = NOW() WHERE order_number = ?");
    } elseif ($status === 'failed') {
        $stmt = $conn->prepare("UPDATE orders SET status = ?, payment_status = 'failed', status_updated_at = NOW() WHERE order_number = ?");
    } else {
        $stmt = $conn->prepare('UPDATE orders SET status = ?, status_updated_at = NOW() WHERE order_number = ?');
    }
    $stmt->bind_param('ss', $status, $orderNumber);
} else {
    sendResponse('error', 'Order ID or order number is required.', null, 422);
}

if (!$stmt->execute()) {
    sendResponse('error', 'Could not update order status.', null, 500);
}

$orderId = (int) $row['id'];

if (in_array($status, $paidStatuses, true)) {
    try {
        OrderFulfillmentService::fulfillPaidOrder($orderId, $conn);
    } catch (Throwable $e) {
        error_log('update_status.php: fulfillment failed for order ' . $orderId . ': ' . $e->getMessage());
    }
} elseif (in_array($status, ['failed', 'cancelled'], true)) {
    try {
        InventoryReservationService::releaseOrder($conn, $orderId);
    } catch (Throwable $e) {
        error_log('update_status.php: reservation release failed for order ' . $orderId . ': ' . $e->getMessage());
    }
}

if ($status === 'shipped') {
    try {
        EmailService::sendOrderShipped($orderId, $conn);
    } catch (Throwable $e) {
        error_log('update_status.php: shipped email failed: ' . $e->getMessage());
    }
} elseif ($status === 'delivered') {
    try {
        EmailService::sendOrderDelivered($orderId, $conn);
    } catch (Throwable $e) {
        error_log('update_status.php: delivered email failed: ' . $e->getMessage());
    }
} elseif ($status === 'cancelled') {
    try {
        EmailService::sendOrderCancelled($orderId, $conn);
    } catch (Throwable $e) {
        error_log('update_status.php: cancelled email failed: ' . $e->getMessage());
    }
}

sendResponse('success', 'Order status updated.');
