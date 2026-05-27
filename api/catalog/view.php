<?php
require_once __DIR__ . '/../_bootstrap.php';

$type = ($_GET['type'] ?? 'product') === 'bundle' ? 'bundle' : 'product';
$id = max(0, (int) ($_GET['id'] ?? 0));
if ($id <= 0) {
    sendResponse('error', 'Missing item id.', null, 400);
}

$table = $type === 'bundle' ? 'bundles' : 'products';
$metric = $type === 'bundle' ? 'sales_count' : 'view_count';
$conn->query("UPDATE `$table` SET `$metric` = `$metric` + 1 WHERE id = " . $id);

sendResponse('success', 'View recorded.');
