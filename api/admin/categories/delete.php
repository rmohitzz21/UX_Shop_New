<?php
require_once __DIR__ . '/../_admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { sendResponse('error', 'Method not allowed.', null, 405); }
validateCsrf();
$input = adminInput();
$id = (int) ($input['id'] ?? 0);
if ($id <= 0) sendResponse('error', 'Category ID is required.', null, 422);

$stmt = $conn->prepare('SELECT name FROM categories WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$category = $stmt->get_result()->fetch_assoc();
if (!$category) sendResponse('error', 'Category not found.', null, 404);

$count = $conn->prepare('SELECT COUNT(*) AS total FROM products WHERE category = ?');
$count->bind_param('s', $category['name']);
$count->execute();
$total = (int) ($count->get_result()->fetch_assoc()['total'] ?? 0);
if ($total > 0) {
    sendResponse('error', 'This category has products. Move or archive those products before deleting it.', null, 409);
}

$delete = $conn->prepare('DELETE FROM categories WHERE id = ?');
$delete->bind_param('i', $id);
$delete->execute();
sendResponse('success', 'Category deleted.');
