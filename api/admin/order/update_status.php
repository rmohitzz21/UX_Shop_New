<?php
require_once __DIR__ . '/../_admin.php';
validateCsrf();
$input = adminInput();
$id = (int) ($input['id'] ?? 0);
$orderNumber = trim((string) ($input['order_number'] ?? ''));
$statusRaw = trim((string) ($input['status'] ?? ''));
$map = [
    'pending' => 'Pending',
    'processing' => 'Processing',
    'shipped' => 'Shipped',
    'delivered' => 'Delivered',
    'cancelled' => 'Cancelled',
    'failed' => 'Cancelled',
    'paid' => 'Processing',
    'awaiting_payment' => 'Pending',
];
$status = $map[strtolower($statusRaw)] ?? $statusRaw;
if (!in_array($status, ['Pending','Processing','Shipped','Delivered','Cancelled'], true)) {
    sendResponse('error', 'Invalid status.', null, 422);
}
if ($id > 0) {
    $stmt = $conn->prepare('UPDATE orders SET status = ?, status_updated_at = NOW() WHERE id = ?');
    $stmt->bind_param('si', $status, $id);
} elseif ($orderNumber !== '') {
    $stmt = $conn->prepare('UPDATE orders SET status = ?, status_updated_at = NOW() WHERE order_number = ?');
    $stmt->bind_param('ss', $status, $orderNumber);
} else {
    sendResponse('error', 'Order ID is required.', null, 422);
}
$stmt->execute();
sendResponse('success', 'Order status updated.');
