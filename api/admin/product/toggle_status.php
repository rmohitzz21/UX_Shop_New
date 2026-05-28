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
$stmt   = $conn->prepare('UPDATE products SET is_active = ? WHERE id = ?');
$stmt->bind_param('ii', $active, $id);
$stmt->execute();
if ($stmt->affected_rows < 1) {
    sendResponse('error', 'Product not found.', null, 404);
}
sendResponse('success', $active ? 'Product activated.' : 'Product archived.');
