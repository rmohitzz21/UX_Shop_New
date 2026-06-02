<?php
require_once __DIR__ . '/../_admin.php';
require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../../../includes/DigitalDownloadService.php';

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
    $stmt = $conn->prepare('UPDATE orders SET status = ?, status_updated_at = NOW() WHERE id = ?');
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
    $stmt = $conn->prepare('UPDATE orders SET status = ?, status_updated_at = NOW() WHERE order_number = ?');
    $stmt->bind_param('ss', $status, $orderNumber);
} else {
    sendResponse('error', 'Order ID or order number is required.', null, 422);
}

if (!$stmt->execute()) {
    sendResponse('error', 'Could not update order status.', null, 500);
}

// Generate digital download tokens whenever an order becomes fulfillable.
// INSERT IGNORE on order_item_id ensures this is idempotent.
$fulfillableStatuses = ['paid', 'processing', 'shipped', 'delivered'];
if (in_array($status, $fulfillableStatuses, true)) {
    try {
        DigitalDownloadService::generateDownloadsForOrder((int) $row['id'], $conn);
    } catch (Throwable $e) {
        error_log('update_status.php: download generation failed for order ' . $row['id'] . ': ' . $e->getMessage());
    }
}

sendResponse('success', 'Order status updated.');
