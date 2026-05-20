<?php
// api/address/get.php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/helpers.php';

requireUserAuth();

$user_id = (int) $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$addresses = [];
while ($row = $result->fetch_assoc()) {
    $addresses[] = $row;
}
$stmt->close();
$conn->close();

sendResponse('success', 'Addresses loaded', $addresses);
?>
