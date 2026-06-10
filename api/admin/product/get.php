<?php
require_once __DIR__ . '/../_admin.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) sendResponse('error', 'Product ID is required.', null, 422);

$stmt = $conn->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row) sendResponse('error', 'Product not found.', null, 404);
$row['additional_images_list'] = json_decode((string) ($row['additional_images'] ?? '[]'), true) ?: [];
$row['custom_fields_parsed'] = json_decode((string) ($row['custom_fields'] ?? '[]'), true) ?: [];
sendResponse('success', 'Product loaded.', $row);
