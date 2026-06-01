<?php
require_once __DIR__ . '/../_admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', 'Method not allowed.', null, 405);
}
validateCsrf();
$input = adminInput();
$id = (int) ($input['id'] ?? 0);
$name = trim((string) ($input['name'] ?? ''));
if ($name === '') sendResponse('error', 'Category name is required.', null, 422);

$description = trim((string) ($input['description'] ?? ''));
$accent = trim((string) ($input['accent'] ?? 'purple'));
$allowedAccents = ['purple', 'green', 'pink', 'orange', 'blue'];
if (!in_array($accent, $allowedAccents, true)) {
    $accent = 'purple';
}
$active = adminBool($input['is_active'] ?? 1, 1);
$sortOrder = max(0, (int) ($input['sort_order'] ?? 0));
$slug = slugify($name);
$image = trim((string) ($input['existing_icon'] ?? $input['icon'] ?? ''));
$uploaded = adminUploadImages('image');
if ($uploaded) {
    $image = $uploaded[0];
}

function adminCategorySaveError(mysqli $conn, mysqli_stmt $stmt, string $action): void {
    if ($conn->errno === 1062) {
        sendResponse('error', 'A category with this name or slug already exists.', null, 409);
    }
    sendResponse('error', "Could not {$action} category: " . $stmt->error, null, 500);
}

function adminSyncCategoryName(mysqli $conn, string $oldName, string $newName): void {
    if ($oldName === '' || $newName === '' || $oldName === $newName) {
        return;
    }
    $tables = ['products', 'bundles', 'freebies'];
    foreach ($tables as $table) {
        if (!tableExists($conn, $table)) {
            continue;
        }
        $stmt = $conn->prepare("UPDATE `{$table}` SET category = ? WHERE category = ?");
        $stmt->bind_param('ss', $newName, $oldName);
        $stmt->execute();
    }
}

if ($id > 0) {
    $existing = $conn->prepare('SELECT name, icon FROM categories WHERE id = ? LIMIT 1');
    $existing->bind_param('i', $id);
    $existing->execute();
    $row = $existing->get_result()->fetch_assoc();
    if (!$row) sendResponse('error', 'Category not found.', null, 404);
    if ($image === '') {
        $image = $row['icon'] ?? '';
    }

    $oldName = (string) ($row['name'] ?? '');
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare('UPDATE categories SET name=?, slug=?, description=?, icon=?, accent=?, is_active=?, sort_order=? WHERE id=?');
        $stmt->bind_param('sssssiii', $name, $slug, $description, $image, $accent, $active, $sortOrder, $id);
        if (!$stmt->execute()) {
            adminCategorySaveError($conn, $stmt, 'update');
        }
        adminSyncCategoryName($conn, $oldName, $name);
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        error_log('admin/categories/save.php update: ' . $e->getMessage());
        sendResponse('error', 'Could not update category.', null, 500);
    }
    sendResponse('success', 'Category updated.', ['id' => $id, 'icon' => $image]);
}

$stmt = $conn->prepare('INSERT INTO categories (name, slug, description, icon, accent, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)');
$stmt->bind_param('sssssii', $name, $slug, $description, $image, $accent, $active, $sortOrder);
if (!$stmt->execute()) {
    adminCategorySaveError($conn, $stmt, 'create');
}
sendResponse('success', 'Category created.', ['id' => (int) $conn->insert_id, 'icon' => $image]);
