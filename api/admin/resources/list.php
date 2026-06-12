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



if (!tableExists($conn, 'digital_resources')) {

    sendResponse('error', 'Digital resources table is not set up yet.', null, 503);

}



$cols = drResourceListColumns($conn);



if ($productId > 0) {

    $stmt = $conn->prepare(

        "SELECT {$cols} FROM digital_resources WHERE product_id = ? ORDER BY sort_order ASC, id ASC"

    );

    $stmt->bind_param('i', $productId);

} elseif ($bundleId > 0) {

    $stmt = $conn->prepare(

        "SELECT {$cols} FROM digital_resources WHERE bundle_id = ? ORDER BY sort_order ASC, id ASC"

    );

    $stmt->bind_param('i', $bundleId);

} else {

    if (!columnExists($conn, 'digital_resources', 'freebie_id')) {

        sendResponse('error', 'Freebie resources are not available on this database yet.', null, 503);

    }

    $stmt = $conn->prepare(

        "SELECT {$cols} FROM digital_resources WHERE freebie_id = ? ORDER BY sort_order ASC, id ASC"

    );

    $stmt->bind_param('i', $freebieId);

}



if ($stmt === false) {

    error_log('digital_resources list prepare failed: ' . $conn->error);

    sendResponse('error', 'Could not load resources.', null, 500);

}



if (!$stmt->execute()) {

    error_log('digital_resources list execute failed: ' . $stmt->error);

    sendResponse('error', 'Could not load resources.', null, 500);

}

$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);



sendResponse('success', 'Resources loaded.', array_map('drPublicResourceRow', $rows));

