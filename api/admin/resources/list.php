<?php
require_once __DIR__ . '/../_admin.php';
require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../../../includes/DigitalStorageService.php';

$productId = (int) ($_GET['product_id'] ?? 0);
$bundleId  = (int) ($_GET['bundle_id'] ?? 0);

if (($productId > 0) === ($bundleId > 0)) {
    sendResponse('error', 'Provide exactly one of product_id or bundle_id.', null, 422);
}

if ($productId > 0) {
    $stmt = $conn->prepare(
        'SELECT id, product_id, bundle_id, title, resource_type, delivery_mode,
                storage_key, external_url, instructions, download_limit, expiry_days,
                sort_order, is_active
         FROM digital_resources
         WHERE product_id = ?
         ORDER BY sort_order ASC, id ASC'
    );
    $stmt->bind_param('i', $productId);
} else {
    $stmt = $conn->prepare(
        'SELECT id, product_id, bundle_id, title, resource_type, delivery_mode,
                storage_key, external_url, instructions, download_limit, expiry_days,
                sort_order, is_active
         FROM digital_resources
         WHERE bundle_id = ?
         ORDER BY sort_order ASC, id ASC'
    );
    $stmt->bind_param('i', $bundleId);
}

if ($stmt === false) {
    sendResponse('error', 'Digital resources are not available.', null, 503);
}

$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

sendResponse('success', 'Resources loaded.', array_map('drPublicResourceRow', $rows));
