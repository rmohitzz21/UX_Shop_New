<?php
header('Content-Type: application/json');

require_once '../../../includes/config.php';

// Enforce admin access
requireAdmin();

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Product ID is required"]);
    exit;
}

$id = intval($_GET['id']);

// Check database connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $product = $result->fetch_assoc();
    echo json_encode(["status" => "success", "data" => $product]);
} else {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "Product not found"]);
}

$stmt->close();
$conn->close();
?>
