<?php
require_once __DIR__ . '/../_admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', 'Method not allowed.', null, 405);
}
validateCsrf();
$input  = adminInput();
$id     = (int) ($input['id'] ?? 0);
if ($id <= 0) {
    sendResponse('error', 'Product ID is required.', null, 422);
}
$active = adminBool($input['is_active'] ?? null, 1);
$exists = $conn->prepare('SELECT id, is_active FROM products WHERE id = ? LIMIT 1');
$exists->bind_param('i', $id);
$exists->execute();
$row = $exists->get_result()->fetch_assoc();
if (!$row) {
    sendResponse('error', 'Product not found.', null, 404);
}
if ((int) $row['is_active'] === $active) {
    sendResponse('success', $active ? 'Product is already active.' : 'Product is already archived.');
}
$stmt = $conn->prepare('UPDATE products SET is_active = ? WHERE id = ?');
$stmt->bind_param('ii', $active, $id);
$stmt->execute();
sendResponse('success', $active ? 'Product activated.' : 'Product archived.');
