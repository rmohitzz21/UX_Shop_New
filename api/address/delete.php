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

$stmt = $conn->prepare('DELETE FROM addresses WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $addressId, $user['id']);
$stmt->execute();
if ($stmt->affected_rows < 1) {
    sendResponse('error', 'Address not found.', null, 404);
}

sendResponse('success', 'Address deleted successfully.');
