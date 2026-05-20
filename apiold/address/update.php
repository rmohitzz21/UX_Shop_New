<?php
/**
 * api/address/update.php
 * Update an existing address
 *
 * Required: id, firstName, lastName, address, city, state, zip, country, phone
 * Optional: address2, label, addressType, isDefault
 */
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/helpers.php';

requireUserAuth();
validateCsrf();

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON payload']);
    exit;
}

// Validate required fields
if (empty($data['id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Address ID is required']);
    exit;
}

$required = ['firstName', 'lastName', 'address', 'city', 'state', 'zip', 'country', 'phone'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing field: ' . $field]);
        exit;
    }
}

$user_id = intval($_SESSION['user_id']);
$address_id = intval($data['id']);

// IDOR Prevention: Verify ownership before update
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

// Sanitize inputs
$first_name = htmlspecialchars(trim($data['firstName']), ENT_QUOTES, 'UTF-8');
$last_name = htmlspecialchars(trim($data['lastName']), ENT_QUOTES, 'UTF-8');
$address_line1 = htmlspecialchars(trim($data['address']), ENT_QUOTES, 'UTF-8');
$address_line2 = isset($data['address2']) ? htmlspecialchars(trim($data['address2']), ENT_QUOTES, 'UTF-8') : '';
$city = htmlspecialchars(trim($data['city']), ENT_QUOTES, 'UTF-8');
$state = htmlspecialchars(trim($data['state']), ENT_QUOTES, 'UTF-8');
$zip_code = htmlspecialchars(trim($data['zip']), ENT_QUOTES, 'UTF-8');
$country = htmlspecialchars(trim($data['country']), ENT_QUOTES, 'UTF-8');
$phone = preg_replace('/[^\d+\-\s()]/', '', $data['phone']);
$label = isset($data['label']) ? htmlspecialchars(trim($data['label']), ENT_QUOTES, 'UTF-8') : null;
$address_type = isset($data['addressType']) && in_array($data['addressType'], ['shipping', 'billing', 'both'])
    ? $data['addressType']
    : 'both';
$is_default = isset($data['isDefault']) && $data['isDefault'] ? 1 : 0;

// Input length validation
if (strlen($first_name) < 2 || strlen($first_name) > 50) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'First name must be 2-50 characters']);
    exit;
}

if (strlen($address_line1) < 5 || strlen($address_line1) > 255) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Address must be 5-255 characters']);
    exit;
}

if (strlen($zip_code) < 4 || strlen($zip_code) > 20) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ZIP code must be 4-20 characters']);
    exit;
}

// Start transaction for default handling
$conn->begin_transaction();

try {
    // If setting as default, unset previous default
    if ($is_default) {
        $unsetStmt = $conn->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ? AND id != ?");
        $unsetStmt->bind_param("ii", $user_id, $address_id);
        $unsetStmt->execute();
        $unsetStmt->close();
    }

    // Build update query - basic columns only (no label/address_type to ensure compatibility)
    $sql = "UPDATE addresses SET
        first_name = ?,
        last_name = ?,
        address_line1 = ?,
        address_line2 = ?,
        city = ?,
        state = ?,
        zip_code = ?,
        country = ?,
        phone = ?,
        is_default = ?,
        updated_at = NOW()
        WHERE id = ? AND user_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssiii",
        $first_name,
        $last_name,
        $address_line1,
        $address_line2,
        $city,
        $state,
        $zip_code,
        $country,
        $phone,
        $is_default,
        $address_id,
        $user_id
    );

    if ($stmt->execute()) {
        $conn->commit();
        echo json_encode([
            'status' => 'success',
            'message' => 'Address updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update address');
    }
    $stmt->close();

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
?>
