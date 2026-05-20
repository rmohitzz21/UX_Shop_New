<?php
// api/cart/update.php
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

if (!isset($data['product_id']) || !isset($data['quantity'])) {
    sendResponse('error', 'Missing required fields: product_id and quantity', null, 400);
}

$user_id = intval($_SESSION['user_id']);
$product_id = intval($data['product_id']);
$quantity = intval($data['quantity']);
$size = isset($data['size']) ? substr($data['size'], 0, 20) : '';
$available_type = isset($data['available_type']) ? substr($data['available_type'], 0, 20) : 'physical';

$max_per_product = 10;

if ($product_id <= 0) {
    sendResponse('error', 'Invalid product_id', null, 400);
}

if ($quantity <= 0) {
    sendResponse('error', 'Quantity must be positive', null, 400);
}

if ($quantity > $max_per_product) {
    sendResponse('error', "Maximum $max_per_product items per product allowed", null, 400);
}

$productStmt = $conn->prepare("SELECT id FROM products WHERE id = ? LIMIT 1");
$productStmt->bind_param("i", $product_id);
$productStmt->execute();
$product = $productStmt->get_result()->fetch_assoc();
$productStmt->close();
if (!$product) {
    sendResponse('error', 'Product not found', null, 404);
}

$stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ? AND size = ? AND available_type = ?");
$stmt->bind_param("iiiss", $quantity, $user_id, $product_id, $size, $available_type);

if ($stmt->execute()) {
    if ($stmt->affected_rows === 0) {
        $stmt->close();
        $conn->close();
        sendResponse('error', 'Cart item not found', null, 404);
    }
    $stmt->close();
    $conn->close();
    sendResponse('success', 'Quantity updated');
} else {
    $stmt->close();
    $conn->close();
    sendResponse('error', 'Failed to update quantity', null, 500);
}
