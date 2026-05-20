<?php
// api/cart/add.php
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

$user_id = (int) $_SESSION['user_id'];
$product_id = intval($data['product_id']);
$quantity = intval($data['quantity']);
$size = isset($data['size']) ? substr($data['size'], 0, 20) : '';
$available_type = isset($data['available_type']) ? substr($data['available_type'], 0, 20) : 'physical';

if ($product_id <= 0) {
    sendResponse('error', 'Invalid product_id', null, 400);
}

if ($quantity <= 0) {
    sendResponse('error', 'Quantity must be positive', null, 400);
}

// Validate available_type
$allowed_types = ['physical', 'digital', 'both'];
if (!in_array($available_type, $allowed_types)) {
    $available_type = 'physical';
}

// Validate product exists before touching cart
$productStmt = $conn->prepare("SELECT id FROM products WHERE id = ? LIMIT 1");
$productStmt->bind_param("i", $product_id);
$productStmt->execute();
$product = $productStmt->get_result()->fetch_assoc();
$productStmt->close();
if (!$product) {
    sendResponse('error', 'Product not found', null, 404);
}

// Check if item exists in cart using prepared statement
$check = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND size = ? AND available_type = ?");
$check->bind_param("iiss", $user_id, $product_id, $size, $available_type);
$check->execute();
$result = $check->get_result();

$max_per_product = 10;

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $new_quantity = $row['quantity'] + $quantity;
    if ($new_quantity > $max_per_product) {
        $new_quantity = $max_per_product;
        if ($row['quantity'] >= $max_per_product) {
            $check->close();
            $conn->close();
            sendResponse('error', "Maximum $max_per_product items per product allowed", null, 400);
        }
    }
    $update = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
    $update->bind_param("ii", $new_quantity, $row['id']);
    $update->execute();
    $update->close();
    sendResponse('success', 'Cart updated', ['quantity' => $new_quantity]);
} else {
    if ($quantity > $max_per_product) {
        $quantity = $max_per_product;
    }
    $insert = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity, size, available_type) VALUES (?, ?, ?, ?, ?)");
    $insert->bind_param("iiiss", $user_id, $product_id, $quantity, $size, $available_type);
    if ($insert->execute()) {
        $insert->close();
        $check->close();
        $conn->close();
        sendResponse('success', 'Item added to cart');
    } else {
        $insert->close();
        $check->close();
        $conn->close();
        sendResponse('error', 'Failed to add item to cart', null, 500);
    }
}
$check->close();
$conn->close();
sendResponse('success', 'Cart updated');
