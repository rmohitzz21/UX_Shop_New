<?php
require_once __DIR__ . '/../_bootstrap.php';

$query = trim((string) ($_GET['q'] ?? ''));
$limit = max(1, min(20, (int) ($_GET['limit'] ?? 8)));

if ($query === '') {
    $stmt = $conn->prepare('SELECT * FROM products ORDER BY created_at DESC, id DESC LIMIT ?');
    $stmt->bind_param('i', $limit);
} else {
    $like = '%' . $query . '%';
    $stmt = $conn->prepare('SELECT * FROM products WHERE name LIKE ? OR category LIKE ? OR description LIKE ? ORDER BY name ASC LIMIT ?');
    $stmt->bind_param('sssi', $like, $like, $like, $limit);
}

$stmt->execute();
$result = $stmt->get_result();
$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = apiProductPayload($row);
}

sendResponse('success', 'Search complete.', ['items' => $products, 'query' => $query]);
