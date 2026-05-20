<?php
// api/cart/clear.php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/helpers.php';

requireUserAuth();
validateCsrf();

$user_id = intval($_SESSION['user_id']);

$stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    sendResponse('success', 'Cart cleared');
} else {
    $stmt->close();
    $conn->close();
    sendResponse('error', 'Failed to clear cart', null, 500);
}
