<?php
require_once __DIR__ . '/../_bootstrap.php';

$user = apiRequireUser();
$stmt = $conn->prepare('SELECT id, first_name, last_name, address_line1, address_line2, city, state, zip_code, country, phone, is_default, created_at, updated_at FROM addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC');
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$result = $stmt->get_result();

$addresses = [];
while ($row = $result->fetch_assoc()) {
    $row['id'] = (int) $row['id'];
    $row['is_default'] = (int) $row['is_default'];
    $addresses[] = $row;
}

sendResponse('success', 'Addresses loaded.', $addresses);
