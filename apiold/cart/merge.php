<?php
// api/cart/merge.php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/helpers.php';

requireUserAuth();

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
    sendResponse('error', 'Invalid JSON data', null, 400);
}
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($data['csrf_token'] ?? '');
validateCsrfFromToken((string) $csrfToken);
if (!isset($data['cart']) || !is_array($data['cart'])) {
    sendResponse('success', 'No local cart to merge');
}

$local_cart = $data['cart'];

// Start transaction to ensure atomicity
$conn->begin_transaction();

try {
    foreach ($local_cart as $item) {
        $product_id = intval($item['id']);
        $quantity = intval($item['quantity']);
        $size = isset($item['size']) ? $item['size'] : ''; // Raw size
        $available_type = isset($item['available_type']) ? $item['available_type'] : 'physical';
        
        // Validation
        if ($product_id <= 0 || $quantity <= 0) continue;
        if (!in_array($available_type, ['physical', 'digital', 'both'], true)) {
            $available_type = 'physical';
        }

        $productStmt = $conn->prepare("SELECT id FROM products WHERE id = ? LIMIT 1");
        $productStmt->bind_param("i", $product_id);
        $productStmt->execute();
        $product = $productStmt->get_result()->fetch_assoc();
        $productStmt->close();
        if (!$product) continue;
        
        // Use prepared statements to check if item exists in DB cart for this user
        $checkStmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND (size = ? OR size IS NULL OR size = '') AND available_type = ? FOR UPDATE");
        // Bind params: iiss 
        $checkStmt->bind_param("iiss", $user_id, $product_id, $size, $available_type);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        $max_per_product = 10;

        if ($row = $result->fetch_assoc()) {
            $new_quantity = min($row['quantity'] + $quantity, $max_per_product);
            $updateStmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            $updateStmt->bind_param("ii", $new_quantity, $row['id']);
            $updateStmt->execute();
            $updateStmt->close();
        } else {
            if ($quantity > $max_per_product) $quantity = $max_per_product;
            $insertStmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity, size, available_type) VALUES (?, ?, ?, ?, ?)");
            $insertStmt->bind_param("iiiss", $user_id, $product_id, $quantity, $size, $available_type);
            $insertStmt->execute();
            $insertStmt->close();
        }
        $checkStmt->close();
    }
    
    $conn->commit();
    sendResponse('success', 'Cart merged successfully');
    
} catch (Exception $e) {
    $conn->rollback();
    sendResponse('error', 'Merge failed', null, 500);
}
$conn->close();
