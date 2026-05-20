<?php
/**
 * api/address/set-default.php
 * Set an address as the default shipping/billing address
 *
 * Required: id (address ID)
 */
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/helpers.php';

requireUserAuth();
validateCsrf();

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Address ID is required']);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$address_id = intval($data['id']);

// IDOR Prevention: Verify ownership
$verifyStmt = $conn->prepare("SELECT id FROM addresses WHERE id = ? AND user_id = ?");
$verifyStmt->bind_param("ii", $address_id, $user_id);
$verifyStmt->execute();
$existing = $verifyStmt->get_result()->fetch_assoc();
$verifyStmt->close();

if (!$existing) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Address not found or access denied']);
    exit;
}

// Start transaction for atomic default update
$conn->begin_transaction();

try {
    // Step 1: Unset all addresses as default for this user
    $unsetStmt = $conn->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
    $unsetStmt->bind_param("i", $user_id);
    if (!$unsetStmt->execute()) {
        throw new Exception('Failed to unset previous default');
    }
    $unsetStmt->close();

    // Step 2: Set the specified address as default
    $setStmt = $conn->prepare("UPDATE addresses SET is_default = 1, updated_at = NOW() WHERE id = ? AND user_id = ?");
    $setStmt->bind_param("ii", $address_id, $user_id);
    if (!$setStmt->execute()) {
        throw new Exception('Failed to set new default address');
    }
    $setStmt->close();

    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Default address updated successfully'
    ]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
?>
