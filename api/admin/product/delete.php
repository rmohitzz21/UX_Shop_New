<?php
require_once __DIR__ . '/../_admin.php';
validateCsrf();
$input = adminInput();
$id = (int) ($input['id'] ?? 0);
if ($id <= 0) sendResponse('error', 'Product ID is required.', null, 422);
$stmt = $conn->prepare('UPDATE products SET is_active = 0 WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
sendResponse('success', 'Product archived.');
