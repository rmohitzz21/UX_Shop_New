<?php
require_once __DIR__ . '/../_admin.php';
require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../../../includes/DigitalStorageService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', 'Method not allowed.', null, 405);
}
validateCsrf();

$resourceId = (int) ($_POST['resource_id'] ?? 0);
if ($resourceId <= 0) {
    sendResponse('error', 'resource_id is required.', null, 422);
}

$chk = $conn->prepare(
    'SELECT id, product_id, bundle_id, delivery_mode FROM digital_resources WHERE id = ? LIMIT 1'
);
$chk->bind_param('i', $resourceId);
$chk->execute();
$row = $chk->get_result()->fetch_assoc();
if (!$row) {
    sendResponse('error', 'Resource not found.', null, 404);
}
if ($row['delivery_mode'] !== 'download') {
    sendResponse('error', 'This resource type does not accept file uploads.', null, 422);
}

if (empty($_FILES['file']) || ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    sendResponse('error', 'File upload failed.', null, 400);
}

$file = $_FILES['file'];
$maxBytes = (int) (getenv('DIGITAL_UPLOAD_MAX_BYTES') ?: (50 * 1024 * 1024));
if (($file['size'] ?? 0) > $maxBytes) {
    sendResponse('error', 'File exceeds maximum allowed size.', null, 422);
}

$ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
$allowed = drAllowedUploadMimes();
if (!isset($allowed[$ext])) {
    sendResponse('error', 'File type not allowed. Allowed: ' . implode(', ', array_keys($allowed)) . '.', null, 422);
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($file['tmp_name']) ?: '';
if ($mime !== $allowed[$ext] && !in_array($mime, ['application/octet-stream', 'application/x-zip-compressed'], true)) {
    sendResponse('error', 'File content does not match extension.', null, 422);
}

$owner = $row['product_id'] ? 'products/' . (int) $row['product_id'] : 'bundles/' . (int) $row['bundle_id'];
$storageKey = $owner . '/' . $resourceId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;

if (!DigitalStorageService::upload((string) $file['tmp_name'], $storageKey)) {
    sendResponse('error', 'Could not store file in private storage.', null, 500);
}

$old = $conn->prepare('SELECT storage_key FROM digital_resources WHERE id = ? LIMIT 1');
$old->bind_param('i', $resourceId);
$old->execute();
$prev = $old->get_result()->fetch_assoc();
if (!empty($prev['storage_key'])) {
    DigitalStorageService::delete((string) $prev['storage_key']);
}

$upd = $conn->prepare(
    "UPDATE digital_resources SET storage_key = ?, storage_provider = ? WHERE id = ?"
);
$provider = DigitalStorageService::getDriver();
$upd->bind_param('ssi', $storageKey, $provider, $resourceId);
if (!$upd->execute()) {
    sendResponse('error', 'Could not save storage key.', null, 500);
}

sendResponse('success', 'File uploaded.', ['id' => $resourceId, 'has_file' => true]);
