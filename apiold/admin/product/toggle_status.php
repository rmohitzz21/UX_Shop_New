<?php
header('Content-Type: application/json');

require_once '../../../includes/config.php';
require_once '../../../includes/helpers.php';

// Enforce admin access
requireAdmin();
validateCsrf();

$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data)) {
    sendResponse("error", "Invalid JSON input", null, 400);
}

if (!isset($data['id']) || !isset($data['is_active'])) {
    sendResponse("error", "Missing parameters", null, 400);
}

$id = intval($data['id']);
$is_active = intval($data['is_active']);
if ($id <= 0 || !in_array($is_active, [0, 1], true)) {
    sendResponse("error", "Invalid parameters", null, 400);
}

$stmt = $conn->prepare("UPDATE products SET is_active = ? WHERE id = ?");
$stmt->bind_param("ii", $is_active, $id);

if ($stmt->execute()) {
    if ($stmt->affected_rows === 0) {
        $stmt->close();
        $conn->close();
        sendResponse("error", "Product not found or status unchanged", null, 404);
    }
    $stmt->close();
    $conn->close();
    sendResponse("success", "Product status updated");
} else {
    $stmt->close();
    $conn->close();
    sendResponse("error", "Failed to update status", null, 500);
}
