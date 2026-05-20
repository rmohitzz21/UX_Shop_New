<?php
// api/cart/remove.php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/helpers.php';

requireUserAuth();

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    sendResponse('error', 'Invalid JSON data', null, 400);
}
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($data['csrf_token'] ?? '');
validateCsrfFromToken((string) $csrfToken);

if (!isset($data['product_id'])) {
    sendResponse('error', 'Missing required field: product_id', null, 400);
}

$user_id = intval($_SESSION['user_id']);
$product_id = intval($data['product_id']);
$size = isset($data['size']) ? substr($data['size'], 0, 20) : '';
$available_type = isset($data['available_type']) ? substr($data['available_type'], 0, 20) : 'physical';

if ($product_id <= 0) {
    sendResponse('error', 'Invalid product_id', null, 400);
}

$stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ? AND size = ? AND available_type = ?");
$stmt->bind_param("iiss", $user_id, $product_id, $size, $available_type);

if ($stmt->execute()) {
    if ($stmt->affected_rows === 0) {
        $stmt->close();
        $conn->close();
        sendResponse('error', 'Cart item not found', null, 404);
    }
    $stmt->close();
    $conn->close();
    sendResponse('success', 'Item removed from cart');
} else {
    $stmt->close();
    $conn->close();
    sendResponse('error', 'Failed to remove item', null, 500);
}
