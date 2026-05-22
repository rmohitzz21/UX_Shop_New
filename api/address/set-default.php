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

$conn->begin_transaction();
try {
    $unset = $conn->prepare('UPDATE addresses SET is_default = 0 WHERE user_id = ?');
    $unset->bind_param('i', $user['id']);
    $unset->execute();

    $set = $conn->prepare('UPDATE addresses SET is_default = 1, updated_at = NOW() WHERE id = ? AND user_id = ?');
    $set->bind_param('ii', $addressId, $user['id']);
    $set->execute();

    $conn->commit();
    sendResponse('success', 'Default address updated successfully.');
} catch (Throwable $e) {
    $conn->rollback();
    error_log('api/address/set-default.php: ' . $e->getMessage());
    sendResponse('error', 'Could not update default address.', null, 500);
}
