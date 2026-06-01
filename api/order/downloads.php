<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../includes/DigitalDownloadService.php';

$user    = apiRequireUser();
$orderId = (int) ($_GET['order_id'] ?? 0);

if ($orderId <= 0) {
    sendResponse('error', 'order_id is required.', null, 400);
}

// Verify order belongs to this user
$stmt = $conn->prepare('SELECT id FROM orders WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->bind_param('ii', $orderId, $user['id']);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) {
    sendResponse('error', 'Order not found.', null, 404);
}

$downloads = DigitalDownloadService::getDownloadsForOrder($orderId, $user['id'], $conn);
sendResponse('success', 'Downloads loaded.', $downloads);
