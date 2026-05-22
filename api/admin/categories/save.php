<?php
require_once __DIR__ . '/../_admin.php';

validateCsrf();
$input = adminInput();
$id = (int) ($input['id'] ?? 0);
$name = trim((string) ($input['name'] ?? ''));
if ($name === '') sendResponse('error', 'Category name is required.', null, 422);

$description = trim((string) ($input['description'] ?? ''));
$accent = trim((string) ($input['accent'] ?? 'purple'));
$active = adminBool($input['is_active'] ?? 1, 1);
$sortOrder = (int) ($input['sort_order'] ?? 0);
$slug = slugify($input['slug'] ?? $name);
$image = trim((string) ($input['existing_icon'] ?? $input['icon'] ?? ''));
$uploaded = adminUploadImages('image');
if ($uploaded) $image = $uploaded[0];

if ($id > 0) {
    $existing = $conn->prepare('SELECT icon FROM categories WHERE id = ? LIMIT 1');
    $existing->bind_param('i', $id);
    $existing->execute();
    $row = $existing->get_result()->fetch_assoc();
    if (!$row) sendResponse('error', 'Category not found.', null, 404);
    if ($image === '') $image = $row['icon'] ?? '';

    $stmt = $conn->prepare('UPDATE categories SET name=?, slug=?, description=?, icon=?, accent=?, is_active=?, sort_order=? WHERE id=?');
    $stmt->bind_param('sssssiii', $name, $slug, $description, $image, $accent, $active, $sortOrder, $id);
    if (!$stmt->execute()) sendResponse('error', 'Could not update category: ' . $stmt->error, null, 500);
    sendResponse('success', 'Category updated.', ['id' => $id, 'icon' => $image]);
}

$stmt = $conn->prepare('INSERT INTO categories (name, slug, description, icon, accent, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)');
$stmt->bind_param('sssssii', $name, $slug, $description, $image, $accent, $active, $sortOrder);
if (!$stmt->execute()) sendResponse('error', 'Could not create category: ' . $stmt->error, null, 500);
sendResponse('success', 'Category created.', ['id' => (int) $conn->insert_id, 'icon' => $image]);
