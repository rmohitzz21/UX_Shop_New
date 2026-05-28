<?php
require_once __DIR__ . '/../_admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', 'Method not allowed.', null, 405);
}
validateCsrf();
$input = adminInput();
$id    = (int) ($input['id'] ?? 0);
if ($id <= 0) {
    sendResponse('error', 'Product ID is required.', null, 422);
}

// Check for orders referencing this product before archiving
$chk = $conn->prepare('SELECT COUNT(*) AS c FROM order_items WHERE product_id = ? LIMIT 1');
$chk->bind_param('i', $id);
$chk->execute();
$orderCount = (int) ($chk->get_result()->fetch_assoc()['c'] ?? 0);

if ($orderCount > 0) {
    // Soft-delete only — cannot hard-delete products tied to orders
    $stmt = $conn->prepare('UPDATE products SET is_active = 0 WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    sendResponse('success', 'Product archived (has existing orders, cannot be permanently deleted).');
}

// No orders — safe to hard-delete
$stmt = $conn->prepare('DELETE FROM products WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
if ($stmt->affected_rows < 1) {
    sendResponse('error', 'Product not found.', null, 404);
}
sendResponse('success', 'Product deleted.');
