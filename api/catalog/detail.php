<?php
require_once __DIR__ . '/../_bootstrap.php';

$rawType = strtolower((string) ($_GET['type'] ?? 'product'));
$type = apiNormalizeCartItemType($rawType);
$id = max(0, (int) ($_GET['id'] ?? 0));
if ($id <= 0) {
    sendResponse('error', 'Missing item id.', null, 400);
}

if ($type === 'freebie') {
    $stmt = $conn->prepare('SELECT * FROM freebies WHERE id = ? AND is_active = 1 LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) {
        sendResponse('error', 'Item not found.', null, 404);
    }

    $item = apiFreebiePayload($row);
    $item['tags'] = '';
    $item['whats_included'] = '';
    $item['file_specification'] = '';
    $item['included_items_parsed'] = [];
    $item['view_count'] = 0;
    $item['sales_count'] = (int) ($row['download_count'] ?? 0);
    $item['slug'] = $row['slug'] ?? '';
    $item['review_count'] = 0;
    $item['discount_percent'] = 0;
    $item['additional_images'] = [];

    $related = [];
    $category = $row['category'] ?? '';
    $relSql = 'SELECT * FROM freebies WHERE id <> ? AND is_active = 1';
    $params = [$id];
    $types = 'i';
    if ($category !== '') {
        $relSql .= ' AND category = ?';
        $params[] = $category;
        $types .= 's';
    }
    $relSql .= ' ORDER BY is_featured DESC, download_count DESC, sort_order ASC LIMIT 4';
    $relStmt = $conn->prepare($relSql);
    $relStmt->bind_param($types, ...$params);
    $relStmt->execute();
    $relRes = $relStmt->get_result();
    while ($rel = $relRes->fetch_assoc()) {
        $related[] = apiFreebiePayload($rel);
    }

    sendResponse('success', 'Item loaded.', ['item' => $item, 'related' => $related, 'tag_tokens' => []]);
}

$table = $type === 'bundle' ? 'bundles' : 'products';
$stmt = $conn->prepare("SELECT * FROM `$table` WHERE id = ? AND is_active = 1 LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row) {
    sendResponse('error', 'Item not found.', null, 404);
}

$item = apiProductPayload($row);
$item['type'] = $type;
if ($type === 'bundle') {
    $item['available_type'] = 'digital';
}
$item['tags'] = $row['tags'] ?? '';
$item['whats_included'] = $row['whats_included'] ?? '';
$item['file_specification'] = $row['file_specification'] ?? '';
$rawIncludedItems = $row['included_items'] ?? '[]';
$parsedIncluded = json_decode((string) $rawIncludedItems, true);
$item['included_items_parsed'] = is_array($parsedIncluded) ? $parsedIncluded : [];
$item['view_count'] = (int) ($row['view_count'] ?? 0);
$item['sales_count'] = (int) ($row['sales_count'] ?? 0);
$item['slug'] = $row['slug'] ?? '';
$item['is_featured'] = !empty($row['is_featured']);
$item['additional_images'] = marketplaceParseAdditionalImages($row['additional_images'] ?? null);
if ($type === 'product') {
    $item = array_merge($item, marketplaceProductSpecs($row));
} else {
    $updatedAt = $row['updated_at'] ?? $row['created_at'] ?? null;
    $bundleSpecs = [
        'last_update' => $updatedAt ? date('M j, Y', strtotime((string) $updatedAt)) : date('M j, Y'),
        'high_resolution' => 'Yes',
        'compatible_software' => 'All UX Pacific resources',
        'software_version' => 'Latest',
        'files_included' => 'Bundle download',
        'grid_columns' => '—',
        'layout_type' => 'Curated pack',
        'license_type' => 'Premium',
    ];
    $item = array_merge($item, shopSettingsApplyProductInfo($bundleSpecs, $conn));
}
$item['discount_percent'] = (!empty($row['old_price']) && (float) $row['old_price'] > 0)
    ? max(0, round((1 - ((float) $row['price'] / (float) $row['old_price'])) * 100))
    : 0;

$reviewCount = 0;
$reviewCol = $type === 'bundle' ? 'bundle_id' : 'product_id';
$reviewStmt = $conn->prepare("SELECT COUNT(*) AS c FROM reviews WHERE `$reviewCol` = ? AND is_approved = 1");
if ($reviewStmt) {
    $reviewStmt->bind_param('i', $id);
    $reviewStmt->execute();
    $reviewRow = $reviewStmt->get_result()->fetch_assoc();
    $reviewCount = (int) ($reviewRow['c'] ?? 0);
}
$item['review_count'] = $reviewCount;

$related = [];
$relatedTable = $type === 'bundle' ? 'bundles' : 'products';
$category = $row['category'] ?? '';
$tags = array_filter(array_map('trim', explode(',', (string) ($row['tags'] ?? ''))));
$where = ['id <> ?', 'is_active = 1'];
$params = [$id];
$types = 'i';
if ($category !== '') {
    $where[] = 'category = ?';
    $params[] = $category;
    $types .= 's';
}
$sql = "SELECT * FROM `$relatedTable` WHERE " . implode(' AND ', $where) . ' ORDER BY is_featured DESC, sales_count DESC, rating DESC LIMIT 4';
$relStmt = $conn->prepare($sql);
$relStmt->bind_param($types, ...$params);
$relStmt->execute();
$relRes = $relStmt->get_result();
while ($rel = $relRes->fetch_assoc()) {
    $payload = apiProductPayload($rel);
    $payload['type'] = $type;
    if ($type === 'bundle') {
        $payload['available_type'] = 'digital';
    }
    $related[] = $payload;
}

sendResponse('success', 'Item loaded.', ['item' => $item, 'related' => $related, 'tag_tokens' => $tags]);
