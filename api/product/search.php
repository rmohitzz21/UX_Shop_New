<?php
require_once __DIR__ . '/../_bootstrap.php';

$query = trim((string) ($_GET['q'] ?? ''));
$limit = max(1, min(20, (int) ($_GET['limit'] ?? 8)));
$items = [];

foreach ([['products', 'product'], ['bundles', 'bundle']] as [$table, $type]) {
    $perType = max(1, (int) ceil($limit / 2));
    if ($query === '') {
        $stmt = $conn->prepare("SELECT * FROM `$table` WHERE is_active = 1 ORDER BY is_featured DESC, created_at DESC, id DESC LIMIT ?");
        $stmt->bind_param('i', $perType);
    } else {
        $like = '%' . $query . '%';
        $stmt = $conn->prepare("SELECT * FROM `$table` WHERE is_active = 1 AND (name LIKE ? OR category LIKE ? OR description LIKE ? OR tags LIKE ?) ORDER BY is_featured DESC, name ASC LIMIT ?");
        $stmt->bind_param('ssssi', $like, $like, $like, $like, $perType);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $payload = apiProductPayload($row);
        $payload['type'] = $type;
        if ($type === 'bundle') $payload['available_type'] = 'digital';
        $items[] = $payload;
    }
}

usort($items, fn($a, $b) => strcmp($a['name'], $b['name']));
$items = array_slice($items, 0, $limit);

sendResponse('success', 'Search complete.', ['items' => $items, 'query' => $query]);
