<?php
// api/address/delete.php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/helpers.php';

requireUserAuth();
validateCsrf();

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing ID']);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$address_id = intval($data['id']);

// Delete address only if it belongs to user (IDOR protection)
$stmt = $conn->prepare("DELETE FROM addresses WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $address_id, $user_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['status' => 'success', 'message' => 'Address deleted']);
} else {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Address not found']);
}
$stmt->close();
$conn->close();
?>
