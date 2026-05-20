<?php
header('Content-Type: application/json');

// Include database configuration
require_once '../../../includes/config.php';

// Enforce Admin Access
requireAdmin();

// Check database connection
if ($conn->connect_error) {
    sendResponse("error", "Database connection failed", null, 500);
}

// Fetch products
$sql = "SELECT * FROM products ORDER BY created_at DESC";
$result = $conn->query($sql);

$products = [];

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

sendResponse("success", "Products fetched successfully", $products);

$conn->close();
?>
