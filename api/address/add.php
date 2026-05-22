<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/_helpers.php';

$user = apiRequireUser();
$input = addressInput();
validateCsrf();
$address = normalizeAddressPayload($input);

$countStmt = $conn->prepare('SELECT COUNT(*) AS c FROM addresses WHERE user_id = ?');
$countStmt->bind_param('i', $user['id']);
$countStmt->execute();
$count = (int) ($countStmt->get_result()->fetch_assoc()['c'] ?? 0);
if ($count >= 10) {
    sendResponse('error', 'Maximum 10 addresses allowed.', null, 422);
}
if ($count === 0) {
    $address['is_default'] = 1;
}

$conn->begin_transaction();
try {
    if ($address['is_default']) {
        $unset = $conn->prepare('UPDATE addresses SET is_default = 0 WHERE user_id = ?');
        $unset->bind_param('i', $user['id']);
        $unset->execute();
    }

    $stmt = $conn->prepare('INSERT INTO addresses (user_id, first_name, last_name, address_line1, address_line2, city, state, zip_code, country, phone, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('isssssssssi', $user['id'], $address['first_name'], $address['last_name'], $address['address_line1'], $address['address_line2'], $address['city'], $address['state'], $address['zip_code'], $address['country'], $address['phone'], $address['is_default']);
    if (!$stmt->execute()) {
        throw new RuntimeException($stmt->error);
    }
    $id = (int) $conn->insert_id;
    $conn->commit();
    sendResponse('success', 'Address added successfully.', ['id' => $id]);
} catch (Throwable $e) {
    $conn->rollback();
    error_log('api/address/add.php: ' . $e->getMessage());
    sendResponse('error', 'Could not add address.', null, 500);
}
