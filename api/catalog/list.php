<?php
require_once __DIR__ . '/../_bootstrap.php';

$type = $_GET['type'] ?? 'product';
$category = trim((string) ($_GET['category'] ?? $_GET['cat'] ?? ''));
$q = trim((string) ($_GET['q'] ?? ''));
$sort = $_GET['sort'] ?? 'latest';
$min = isset($_GET['min']) && $_GET['min'] !== '' ? (float) $_GET['min'] : null;
$max = isset($_GET['max']) && $_GET['max'] !== '' ? (float) $_GET['max'] : null;
$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = max(1, min(36, (int) ($_GET['limit'] ?? 12)));
$offset = ($page - 1) * $limit;
$table = $type === 'bundle' ? 'bundles' : 'products';

$where = ['is_active = 1'];
$params = [];
$types = '';
if ($category !== '') {
    $where[] = '(category = ? OR LOWER(REPLACE(category, " ", "-")) = LOWER(?))';
    $params[] = $category;
    $params[] = $category;
    $types .= 'ss';
}
if ($q !== '') {
    $where[] = '(name LIKE ? OR description LIKE ? OR category LIKE ? OR tags LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like);
    $types .= 'ssss';
}
if ($min !== null) {
    $where[] = 'price >= ?';
    $params[] = $min;
    $types .= 'd';
}
if ($max !== null) {
    $where[] = 'price <= ?';
    $params[] = $max;
    $types .= 'd';
}

$order = match ($sort) {
    'price_low' => 'price ASC, id DESC',
    'price_high' => 'price DESC, id DESC',
    'popular' => 'sales_count DESC, rating DESC, id DESC',
    default => 'created_at DESC, id DESC',
};

$whereSql = implode(' AND ', $where);
$countStmt = $conn->prepare("SELECT COUNT(*) AS c FROM `$table` WHERE $whereSql");
if ($params) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$total = (int) ($countStmt->get_result()->fetch_assoc()['c'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM `$table` WHERE $whereSql ORDER BY $order LIMIT ? OFFSET ?");
$types2 = $types . 'ii';
$params2 = [...$params, $limit, $offset];
$stmt->bind_param($types2, ...$params2);
$stmt->execute();
$result = $stmt->get_result();
$items = [];
while ($row = $result->fetch_assoc()) {
    $payload = apiProductPayload($row);
    $payload['type'] = $type === 'bundle' ? 'bundle' : 'product';
    if ($payload['type'] === 'bundle') $payload['available_type'] = 'digital';
    $payload['tags'] = $row['tags'] ?? '';
    $payload['is_featured'] = (int) ($row['is_featured'] ?? 0);
    $items[] = $payload;
}

sendResponse('success', 'Catalog loaded.', [
    'items' => $items,
    'page' => $page,
    'limit' => $limit,
    'total' => $total,
    'has_more' => ($offset + $limit) < $total,
]);
