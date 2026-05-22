<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/_helpers.php';

$user = apiRequireUser();
$input = addressInput();
validateCsrf();

$addressId = (int) ($input['id'] ?? 0);
if ($addressId <= 0) {
    sendResponse('error', 'Address ID is required.', null, 422);
}
ensureAddressOwner($conn, $addressId, $user['id']);
$address = normalizeAddressPayload($input);

$conn->begin_transaction();
try {
    if ($address['is_default']) {
        $unset = $conn->prepare('UPDATE addresses SET is_default = 0 WHERE user_id = ? AND id <> ?');
        $unset->bind_param('ii', $user['id'], $addressId);
        $unset->execute();
    }

    $stmt = $conn->prepare('UPDATE addresses SET first_name = ?, last_name = ?, address_line1 = ?, address_line2 = ?, city = ?, state = ?, zip_code = ?, country = ?, phone = ?, is_default = ?, updated_at = NOW() WHERE id = ? AND user_id = ?');
    $stmt->bind_param('sssssssssiii', $address['first_name'], $address['last_name'], $address['address_line1'], $address['address_line2'], $address['city'], $address['state'], $address['zip_code'], $address['country'], $address['phone'], $address['is_default'], $addressId, $user['id']);
    if (!$stmt->execute()) {
        throw new RuntimeException($stmt->error);
    }
    $conn->commit();
    sendResponse('success', 'Address updated successfully.');
} catch (Throwable $e) {
    $conn->rollback();
    error_log('api/address/update.php: ' . $e->getMessage());
    sendResponse('error', 'Could not update address.', null, 500);
}
