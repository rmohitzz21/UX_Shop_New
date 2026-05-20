<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireUserAuth();
validateCsrf();

$user_id = (int) $_SESSION['user_id'];

$conn->begin_transaction();

try {
    // Delete user's addresses
    $stmt = $conn->prepare("DELETE FROM addresses WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    // Delete user's cart items
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    // Anonymize orders (keep for records, but remove user link)
    $stmt = $conn->prepare("UPDATE orders SET user_id = NULL WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    // Delete password reset tokens
    $stmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    // Finally delete the user
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    // Destroy session
    session_destroy();

    sendResponse("success", "Account deleted successfully");

} catch (Exception $e) {
    $conn->rollback();
    sendResponse("error", "Failed to delete account: " . $e->getMessage(), null, 500);
}

$conn->close();
?>
