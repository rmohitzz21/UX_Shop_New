<?php
/**
 * api/address/add.php
 * Add a new address for the authenticated user
 *
 * Required: firstName, lastName, address, city, state, zip, country, phone
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
$required = ['firstName', 'lastName', 'address', 'city', 'state', 'zip', 'country', 'phone'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing field: ' . $field]);
        exit;
    }
}

$user_id = intval($_SESSION['user_id']);

// Check address limit (max 10 addresses per user)
$countStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM addresses WHERE user_id = ?");
$countStmt->bind_param("i", $user_id);
$countStmt->execute();
$count_row = $countStmt->get_result()->fetch_assoc();
$countStmt->close();

if ($count_row['cnt'] >= 10) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Maximum 10 addresses allowed. Please delete an existing address first.']);
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

// New optional fields: label and addressType
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

// If this is the first address, make it default
if ($is_default == 0 && $count_row['cnt'] == 0) {
    $is_default = 1;
}

// Start transaction
$conn->begin_transaction();

try {
    // If setting as default, unset previous default
    if ($is_default) {
        $unsetStmt = $conn->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
        $unsetStmt->bind_param("i", $user_id);
        $unsetStmt->execute();
        $unsetStmt->close();
    }

    // Try INSERT with new columns first, fallback to basic columns if they don't exist
    $stmt = $conn->prepare("INSERT INTO addresses
        (user_id, first_name, last_name, address_line1, address_line2, city, state, zip_code, country, phone, is_default)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssssssis",
        $user_id,
        $first_name,
        $last_name,
        $address_line1,
        $address_line2,
        $city,
        $state,
        $zip_code,
        $country,
        $phone,
        $is_default
    );

    if ($stmt->execute()) {
        $new_id = $conn->insert_id;
        $conn->commit();
        echo json_encode([
            'status' => 'success',
            'message' => 'Address added successfully',
            'data' => ['id' => $new_id]
        ]);
    } else {
        throw new Exception('Failed to add address');
    }
    $stmt->close();

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
?>
