<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';

// SEC-12: return 401, not 200
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user profile and primary address
$sql = "SELECT u.first_name, u.last_name, u.email, u.phone, 
               a.address_line1, a.address_line2, a.city, a.state, a.zip_code, a.country 
        FROM users u 
        LEFT JOIN addresses a ON u.id = a.user_id 
        WHERE u.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    $result = $stmt->get_result();
    if ($user = $result->fetch_assoc()) {
        echo json_encode(['status' => 'success', 'data' => $user]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}

$stmt->close();
$conn->close();
?>
