<?php
require_once __DIR__ . '/../_admin.php';

$q = trim((string) ($_GET['q'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$where = [];
$params = [];
$types = '';
if ($q !== '') {
    $like = '%' . $q . '%';
    $where[] = '(b.name LIKE ? OR b.slug LIKE ? OR b.description LIKE ? OR b.tags LIKE ?)';
    $params = array_merge($params, [$like, $like, $like, $like]);
    $types .= 'ssss';
}
if ($status !== '' && in_array($status, ['0', '1'], true)) {
    $where[] = 'b.is_active = ?';
    $params[] = (int) $status;
    $types .= 'i';
}

$sql = 'SELECT b.*, COUNT(bi.id) AS product_count,
        GROUP_CONCAT(DISTINCT bi.product_id ORDER BY bi.product_id) AS product_ids_csv
        FROM bundles b
        LEFT JOIN bundle_items bi ON bi.bundle_id = b.id';
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' GROUP BY b.id ORDER BY b.updated_at DESC, b.id DESC';

$stmt = $conn->prepare($sql);
if (!$stmt) sendResponse('error', 'Could not load bundles.', null, 500);
if ($params) $stmt->bind_param($types, ...$params);
if (!$stmt->execute()) sendResponse('error', 'Could not load bundles.', null, 500);
$rows = [];
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $decoded = json_decode((string) ($row['included_items'] ?? ''), true);
    $row['included_items_list'] = is_array($decoded) ? $decoded : [];
    $gallery = json_decode((string) ($row['additional_images'] ?? ''), true);
    $row['additional_images_list'] = is_array($gallery) ? array_values(array_filter($gallery, 'is_string')) : [];
    $csv = trim((string) ($row['product_ids_csv'] ?? ''));
    $row['product_ids'] = $csv !== ''
        ? array_values(array_filter(array_map('intval', explode(',', $csv)), static fn ($id) => $id > 0))
        : [];
    unset($row['product_ids_csv']);
    $rows[] = $row;
}
sendResponse('success', 'Bundles loaded.', $rows);
