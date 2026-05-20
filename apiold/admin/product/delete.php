<?php

require_once '../../../includes/config.php';
require_once '../../../includes/helpers.php';

header('Content-Type: application/json');


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', 'Invalid request method', null, 405);
}

// Authentication Check — use canonical requireAdmin() which checks admin_id
requireAdmin();
$bodyToken = $_POST['csrf_token'] ?? '';
$headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
$csrfToken = $headerToken !== '' ? $headerToken : $bodyToken;
validateCsrfFromToken((string) $csrfToken);


$id = $_POST['id'] ?? null;
if(!$id)
{
    sendResponse('error', 'Product ID is required', null, 400);
}

// Check if product exists and get image path
$stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    sendResponse('error', 'Product not found', null, 404);
}

// Check for dependencies in orders
$checkStmt = $conn->prepare("SELECT id FROM order_items WHERE product_id = ? LIMIT 1");
$checkStmt->bind_param("i", $id);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows > 0) {
    $checkStmt->close();
    sendResponse('error', 'Cannot delete product because it has been ordered. Please deactivate it instead.', null, 409);
}
$checkStmt->close();

// Delete from cart first (safe to remove)
$cartStmt = $conn->prepare("DELETE FROM cart WHERE product_id = ?");
$cartStmt->bind_param("i", $id);
$cartStmt->execute();
$cartStmt->close();

$stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
$stmt->bind_param("i", $id);

if($stmt->execute())
{
    // Delete image file if it exists
    if (!empty($product['image'])) {
        $imagePath = '../../../' . $product['image'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }

    sendResponse('success', 'Product deleted successfully');
}
else
{
    sendResponse('error', 'Failed to delete product', null, 500);
}

$stmt->close();
$conn->close();