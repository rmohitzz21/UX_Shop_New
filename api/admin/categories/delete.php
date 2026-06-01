<?php
require_once __DIR__ . '/../_admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', 'Method not allowed.', null, 405);
}
validateCsrf();
$input = adminInput();
$id = (int) ($input['id'] ?? 0);
if ($id <= 0) {
    sendResponse('error', 'Category ID is required.', null, 422);
}

$stmt = $conn->prepare('SELECT name FROM categories WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$category = $stmt->get_result()->fetch_assoc();
if (!$category) {
    sendResponse('error', 'Category not found.', null, 404);
}

$name = (string) $category['name'];
$productCount = 0;
$bundleCount = 0;
$freebieCount = 0;

$count = $conn->prepare('SELECT COUNT(*) AS total FROM products WHERE category = ?');
$count->bind_param('s', $name);
$count->execute();
$productCount = (int) ($count->get_result()->fetch_assoc()['total'] ?? 0);

if (tableExists($conn, 'bundles')) {
    $bCount = $conn->prepare('SELECT COUNT(*) AS total FROM bundles WHERE category = ?');
    $bCount->bind_param('s', $name);
    $bCount->execute();
    $bundleCount = (int) ($bCount->get_result()->fetch_assoc()['total'] ?? 0);
}

if (tableExists($conn, 'freebies')) {
    $fCount = $conn->prepare('SELECT COUNT(*) AS total FROM freebies WHERE category = ?');
    $fCount->bind_param('s', $name);
    $fCount->execute();
    $freebieCount = (int) ($fCount->get_result()->fetch_assoc()['total'] ?? 0);
}

$inUse = $productCount + $bundleCount + $freebieCount;
if ($inUse > 0) {
    $parts = [];
    if ($productCount > 0) $parts[] = "{$productCount} product(s)";
    if ($bundleCount > 0) $parts[] = "{$bundleCount} bundle(s)";
    if ($freebieCount > 0) $parts[] = "{$freebieCount} freebie(s)";
    sendResponse(
        'error',
        'This category is in use (' . implode(', ', $parts) . '). Reassign or remove those items before deleting.',
        null,
        409
    );
}

$delete = $conn->prepare('DELETE FROM categories WHERE id = ?');
$delete->bind_param('i', $id);
$delete->execute();
if ($delete->affected_rows < 1) {
    sendResponse('error', 'Category not found.', null, 404);
}
sendResponse('success', 'Category deleted.');
