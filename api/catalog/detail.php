<?php
require_once __DIR__ . '/../_bootstrap.php';

$type = ($_GET['type'] ?? 'product') === 'bundle' ? 'bundle' : 'product';
$id = max(0, (int) ($_GET['id'] ?? 0));
if ($id <= 0) sendResponse('error', 'Missing item id.', null, 400);

$table = $type === 'bundle' ? 'bundles' : 'products';
$stmt = $conn->prepare("SELECT * FROM `$table` WHERE id = ? AND is_active = 1 LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row) sendResponse('error', 'Item not found.', null, 404);

$metric = $type === 'bundle' ? 'sales_count' : 'view_count';
$conn->query("UPDATE `$table` SET `$metric` = `$metric` + 1 WHERE id = " . (int) $id);

$item = apiProductPayload($row);
$item['type'] = $type;
if ($type === 'bundle') $item['available_type'] = 'digital';
$item['tags'] = $row['tags'] ?? '';
$item['discount_percent'] = (!empty($row['old_price']) && (float) $row['old_price'] > 0)
    ? max(0, round((1 - ((float) $row['price'] / (float) $row['old_price'])) * 100))
    : 0;

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
$sql = "SELECT * FROM `$relatedTable` WHERE " . implode(' AND ', $where) . " ORDER BY is_featured DESC, sales_count DESC, rating DESC LIMIT 4";
$relStmt = $conn->prepare($sql);
$relStmt->bind_param($types, ...$params);
$relStmt->execute();
$relRes = $relStmt->get_result();
while ($rel = $relRes->fetch_assoc()) {
    $payload = apiProductPayload($rel);
    $payload['type'] = $type;
    if ($type === 'bundle') $payload['available_type'] = 'digital';
    $related[] = $payload;
}

$reviews = [];
$col = $type === 'bundle' ? 'bundle_id' : 'product_id';
$reviewStmt = $conn->prepare("SELECT r.rating, r.comment, r.created_at, COALESCE(NULLIF(CONCAT(u.first_name, ' ', u.last_name), ' '), 'Customer') AS user_name FROM reviews r LEFT JOIN users u ON u.id = r.user_id WHERE r.$col = ? AND r.is_approved = 1 ORDER BY r.created_at DESC LIMIT 5");
$reviewStmt->bind_param('i', $id);
$reviewStmt->execute();
$reviewRes = $reviewStmt->get_result();
while ($review = $reviewRes->fetch_assoc()) $reviews[] = $review;

sendResponse('success', 'Item loaded.', ['item' => $item, 'related' => $related, 'reviews' => $reviews, 'tag_tokens' => $tags]);
