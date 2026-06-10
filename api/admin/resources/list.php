<?php
require_once __DIR__ . '/../_admin.php';
require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../../../includes/DigitalStorageService.php';

drEnsureFreebieResourcesColumn($conn);

$productId = (int) ($_GET['product_id'] ?? 0);
$bundleId  = (int) ($_GET['bundle_id'] ?? 0);
$freebieId = (int) ($_GET['freebie_id'] ?? 0);

$ownerCount = ($productId > 0 ? 1 : 0) + ($bundleId > 0 ? 1 : 0) + ($freebieId > 0 ? 1 : 0);
if ($ownerCount !== 1) {
    sendResponse('error', 'Provide exactly one of product_id, bundle_id, or freebie_id.', null, 422);
}

if ($productId > 0) {
    $stmt = $conn->prepare(
        'SELECT id, product_id, bundle_id, freebie_id, title, resource_type, delivery_mode,
                storage_key, external_url, instructions, download_limit, expiry_days,
                sort_order, is_active
         FROM digital_resources
         WHERE product_id = ?
         ORDER BY sort_order ASC, id ASC'
    );
    $stmt->bind_param('i', $productId);
} elseif ($bundleId > 0) {
    $stmt = $conn->prepare(
        'SELECT id, product_id, bundle_id, freebie_id, title, resource_type, delivery_mode,
                storage_key, external_url, instructions, download_limit, expiry_days,
                sort_order, is_active
         FROM digital_resources
         WHERE bundle_id = ?
         ORDER BY sort_order ASC, id ASC'
    );
    $stmt->bind_param('i', $bundleId);
} else {
    $stmt = $conn->prepare(
        'SELECT id, product_id, bundle_id, freebie_id, title, resource_type, delivery_mode,
                storage_key, external_url, instructions, download_limit, expiry_days,
                sort_order, is_active
         FROM digital_resources
         WHERE freebie_id = ?
         ORDER BY sort_order ASC, id ASC'
    );
    $stmt->bind_param('i', $freebieId);
}

if ($stmt === false) {
    sendResponse('error', 'Digital resources are not available.', null, 503);
}

$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

sendResponse('success', 'Resources loaded.', array_map('drPublicResourceRow', $rows));
