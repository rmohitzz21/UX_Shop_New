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
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit;
}

// Basic validation: name cannot be empty
$firstName = trim($input['firstName'] ?? '');
$lastName = trim($input['lastName'] ?? '');
$phone = trim($input['phone'] ?? '');

if (empty($firstName) || empty($lastName)) {
    echo json_encode(['status' => 'error', 'message' => 'First and Last name are required']);
    exit;
}

// Prepare update query
$stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ? WHERE id = ?");
$stmt->bind_param("sssi", $firstName, $lastName, $phone, $user_id);

if ($stmt->execute()) {
    // Update session data as well
    $_SESSION['username'] = $firstName . ' ' . $lastName;
    $_SESSION['first_name'] = $firstName;
    $_SESSION['last_name'] = $lastName;
    
    echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update profile']);
}

$stmt->close();
$conn->close();
?>
