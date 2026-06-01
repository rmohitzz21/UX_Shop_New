<?php
require_once __DIR__ . '/../_admin.php';
require_once __DIR__ . '/_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', 'Method not allowed.', null, 405);
}
validateCsrf();
adminEnsureFreebiesTable($conn);

$input = adminInput();
$id    = (int) ($input['id'] ?? 0);
$name  = trim((string) ($input['name'] ?? ''));
if ($name === '') {
    sendResponse('error', 'Freebie name is required.', null, 422);
}

$description = trim((string) ($input['description'] ?? ''));
$category    = trim((string) ($input['category'] ?? 'General'));
if ($category === '') {
    $category = 'General';
}
$fileUrl = trim((string) ($input['file_url'] ?? ''));
if (!adminValidateFreebieFileUrl($fileUrl)) {
    sendResponse('error', 'A valid resource link (https://…) is required.', null, 422);
}

$active    = adminBool($input['is_active'] ?? 1, 1);
$featured  = adminBool($input['is_featured'] ?? 0, 0);
$sortOrder = max(0, (int) ($input['sort_order'] ?? 0));
$slug      = slugify($name);
$image     = trim((string) ($input['existing_image'] ?? ''));
$uploaded  = adminUploadImages('image');
if ($uploaded) {
    $image = $uploaded[0];
}

if ($id > 0) {
    $existing = $conn->prepare('SELECT image FROM freebies WHERE id = ? LIMIT 1');
    $existing->bind_param('i', $id);
    $existing->execute();
    $row = $existing->get_result()->fetch_assoc();
    if (!$row) {
        sendResponse('error', 'Freebie not found.', null, 404);
    }
    if ($image === '') {
        $image = $row['image'] ?? '';
    }

    $stmt = $conn->prepare(
        'UPDATE freebies SET name=?, slug=?, description=?, category=?, image=?, file_url=?, is_active=?, is_featured=?, sort_order=? WHERE id=?'
    );
    $stmt->bind_param('ssssssiiii', $name, $slug, $description, $category, $image, $fileUrl, $active, $featured, $sortOrder, $id);
    if (!$stmt->execute()) {
        adminFreebieSaveError($conn, $stmt, 'update');
    }
    sendResponse('success', 'Freebie updated.', ['id' => $id, 'image' => $image]);
}

$stmt = $conn->prepare(
    'INSERT INTO freebies (name, slug, description, category, image, file_url, is_active, is_featured, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
$stmt->bind_param('ssssssiii', $name, $slug, $description, $category, $image, $fileUrl, $active, $featured, $sortOrder);
if (!$stmt->execute()) {
    adminFreebieSaveError($conn, $stmt, 'create');
}
sendResponse('success', 'Freebie created.', ['id' => (int) $conn->insert_id, 'image' => $image]);
