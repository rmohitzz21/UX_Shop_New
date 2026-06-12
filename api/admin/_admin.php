<?php
require_once __DIR__ . '/../_bootstrap.php';
requireAdmin();

function adminInput(): array {
    if (!empty($_POST)) {
        return $_POST;
    }
    return apiReadJsonBody();
}

function adminBool($value, int $default = 0): int {
    if ($value === null || $value === '') return $default;
    return in_array((string) $value, ['1', 'true', 'on', 'yes'], true) ? 1 : 0;
}

function adminNullableFloat($value): ?float {
    if ($value === null || $value === '') return null;
    return (float) $value;
}

function adminUploadImages(string $field = 'image'): array {
    if (empty($_FILES[$field])) return [];
    $files = $_FILES[$field];
    $items = [];

    if (is_array($files['name'])) {
        foreach ($files['name'] as $i => $name) {
            $items[] = [
                'name' => $name,
                'tmp_name' => $files['tmp_name'][$i] ?? '',
                'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size' => $files['size'][$i] ?? 0,
            ];
        }
    } else {
        $items[] = $files;
    }

    $uploadDir = dirname(__DIR__, 2) . '/img/products';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }

    $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif'];
    $saved = [];
    foreach ($items as $file) {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) sendResponse('error', 'Image upload failed.', null, 400);
        if (($file['size'] ?? 0) > 5 * 1024 * 1024) sendResponse('error', 'Images must be 5MB or smaller.', null, 422);

        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!isset($allowed[$ext])) sendResponse('error', 'Unsupported image type.', null, 422);

        $info = @getimagesize($file['tmp_name']);
        if (!$info || !in_array($info['mime'] ?? '', $allowed, true)) sendResponse('error', 'Invalid image file.', null, 422);

        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $target = $uploadDir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $target)) sendResponse('error', 'Could not store image.', null, 500);
        $saved[] = 'img/products/' . $filename;
    }
    return $saved;
}

function adminRecordInventory(mysqli $conn, string $type, int $id, int $before, int $after, string $reason): void {
    if ($before === $after) return;
    $adminId = isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null;
    $change = $after - $before;
    $stmt = $conn->prepare('INSERT INTO inventory_movements (item_type, item_id, admin_id, change_qty, stock_before, stock_after, reason) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('siiiiis', $type, $id, $adminId, $change, $before, $after, $reason);
    $stmt->execute();
}
