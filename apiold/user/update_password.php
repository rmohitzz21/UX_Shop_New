<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireUserAuth();
validateCsrf();

$user_id = (int) $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

$currentPassword = $input['currentPassword'] ?? '';
$newPassword = $input['newPassword'] ?? '';

if (empty($currentPassword) || empty($newPassword)) {
    sendResponse("error", "Current and new passwords are required", null, 400);
}

if (strlen($newPassword) < 8) {
    sendResponse("error", "New password must be at least 8 characters long", null, 400);
}

// Verify current password
$stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    if (password_verify($currentPassword, $user['password_hash'])) {
        // Hash new password
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $updateStmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $updateStmt->bind_param("si", $newHash, $user_id);
        
        if ($updateStmt->execute()) {
            sendResponse("success", "Password updated successfully");
        } else {
            sendResponse("error", "Failed to update password", null, 500);
        }
        $updateStmt->close();
    } else {
        sendResponse("error", "Incorrect current password", null, 401);
    }
} else {
    sendResponse("error", "User not found", null, 404);
}

$stmt->close();
$conn->close();
?>
