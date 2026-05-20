<?php
header('Content-Type: application/json');

require_once '../../../includes/config.php';
require_once '../../../includes/helpers.php';

// Enforce admin access
requireAdmin();
validateCsrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse("error", "Method not allowed", null, 405);
}

$data = json_decode(file_get_contents("php://input"), true);

// Support both JSON input and form data
if (!$data && !empty($_POST)) {
    $data = $_POST;
}

if (!isset($data['id']) || !isset($data['action'])) {
    sendResponse("error", "Missing required parameters", null, 400);
}

$id = intval($data['id']);
$action = $data['action']; // 'block' or 'unblock'
if ($id <= 0) {
    sendResponse("error", "Invalid user id", null, 400);
}

if ($action !== 'block' && $action !== 'unblock') {
    sendResponse("error", "Invalid action", null, 400);
}

$is_blocked = ($action === 'block') ? 1 : 0;

$stmt = $conn->prepare("UPDATE users SET is_blocked = ? WHERE id = ?");
$stmt->bind_param("ii", $is_blocked, $id);

if ($stmt->execute()) {
    if ($stmt->affected_rows === 0) {
        $stmt->close();
        $conn->close();
        sendResponse("error", "User not found or already in requested state", null, 404);
    }
    $stmt->close();
    $conn->close();
    sendResponse("success", "User " . ($action === 'block' ? "blocked" : "unblocked") . " successfully");
} else {
    $stmt->close();
    $conn->close();
    sendResponse("error", "Failed to update user status", null, 500);
}
