<?php
require_once __DIR__ . '/../_admin.php';
require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../../../includes/DigitalStorageService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', 'Method not allowed.', null, 405);
}
validateCsrf();

$input     = adminInput();
drEnsureFreebieResourcesColumn($conn);

$id        = (int) ($input['id'] ?? 0);
$productId = (int) ($input['product_id'] ?? 0);
$bundleId  = (int) ($input['bundle_id'] ?? 0);
$freebieId = (int) ($input['freebie_id'] ?? 0);
$title     = trim((string) ($input['title'] ?? ''));
$type      = strtolower(trim((string) ($input['resource_type'] ?? 'file')));

if ($title === '' || strlen($title) > 255) {
    sendResponse('error', 'Title is required (max 255 characters).', null, 422);
}
if (!in_array($type, drAllowedResourceTypes(), true)) {
    sendResponse('error', 'Invalid resource type.', null, 422);
}
$ownerCount = ($productId > 0 ? 1 : 0) + ($bundleId > 0 ? 1 : 0) + ($freebieId > 0 ? 1 : 0);
if ($ownerCount !== 1) {
    sendResponse('error', 'Link resource to exactly one of product, bundle, or freebie.', null, 422);
}

$deliveryMode = drDeliveryModeForType($type);
$provider     = drStorageProviderForType($type);
$externalUrl  = trim((string) ($input['external_url'] ?? ''));
$instructions = trim((string) ($input['instructions'] ?? ''));
$dlLimit      = max(1, min(100, (int) ($input['download_limit'] ?? 5)));
$expiryDays   = max(1, min(3650, (int) ($input['expiry_days'] ?? 30)));
$sortOrder    = (int) ($input['sort_order'] ?? 0);
$isActive     = adminBool($input['is_active'] ?? 1, 1);

if ($deliveryMode === 'open_link') {
    if (!drValidateExternalUrl($externalUrl)) {
        sendResponse('error', 'External URL must be a valid HTTPS link.', null, 422);
    }
} elseif ($deliveryMode === 'instructions') {
    if ($instructions === '') {
        sendResponse('error', 'Instructions text is required for this resource type.', null, 422);
    }
}

if ($id > 0) {
    $chk = $conn->prepare('SELECT id, storage_key FROM digital_resources WHERE id = ? LIMIT 1');
    $chk->bind_param('i', $id);
    $chk->execute();
    $existing = $chk->get_result()->fetch_assoc();
    if (!$existing) {
        sendResponse('error', 'Resource not found.', null, 404);
    }
    $storageKey = (string) ($existing['storage_key'] ?? '');
    $stmt = $conn->prepare(
        'UPDATE digital_resources SET title = ?, resource_type = ?, delivery_mode = ?,
         storage_provider = ?, external_url = ?, instructions = ?,
         download_limit = ?, expiry_days = ?, sort_order = ?, is_active = ?
         WHERE id = ?'
    );
    $stmt->bind_param(
        'ssssssiiiii',
        $title,
        $type,
        $deliveryMode,
        $provider,
        $externalUrl,
        $instructions,
        $dlLimit,
        $expiryDays,
        $sortOrder,
        $isActive,
        $id
    );
    if (!$stmt->execute()) {
        sendResponse('error', 'Could not update resource.', null, 500);
    }
    sendResponse('success', 'Resource updated.', ['id' => $id]);
}

if ($productId > 0) {
    $stmt = $conn->prepare(
        'INSERT INTO digital_resources
         (product_id, bundle_id, freebie_id, title, resource_type, delivery_mode, storage_provider,
          storage_key, external_url, instructions, download_limit, expiry_days, sort_order, is_active)
         VALUES (?, NULL, NULL, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param(
        'issssssiiii',
        $productId,
        $title,
        $type,
        $deliveryMode,
        $provider,
        $externalUrl,
        $instructions,
        $dlLimit,
        $expiryDays,
        $sortOrder,
        $isActive
    );
} elseif ($bundleId > 0) {
    $stmt = $conn->prepare(
        'INSERT INTO digital_resources
         (product_id, bundle_id, freebie_id, title, resource_type, delivery_mode, storage_provider,
          storage_key, external_url, instructions, download_limit, expiry_days, sort_order, is_active)
         VALUES (NULL, ?, NULL, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param(
        'issssssiiii',
        $bundleId,
        $title,
        $type,
        $deliveryMode,
        $provider,
        $externalUrl,
        $instructions,
        $dlLimit,
        $expiryDays,
        $sortOrder,
        $isActive
    );
} else {
    $stmt = $conn->prepare(
        'INSERT INTO digital_resources
         (product_id, bundle_id, freebie_id, title, resource_type, delivery_mode, storage_provider,
          storage_key, external_url, instructions, download_limit, expiry_days, sort_order, is_active)
         VALUES (NULL, NULL, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param(
        'issssssiiii',
        $freebieId,
        $title,
        $type,
        $deliveryMode,
        $provider,
        $externalUrl,
        $instructions,
        $dlLimit,
        $expiryDays,
        $sortOrder,
        $isActive
    );
}
if (!$stmt->execute()) {
    sendResponse('error', 'Could not create resource.', null, 500);
}

sendResponse('success', 'Resource created.', ['id' => (int) $conn->insert_id]);
