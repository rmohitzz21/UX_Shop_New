<?php
require_once __DIR__ . '/../_bootstrap.php';

$input = apiInput();
$ids = $input['ids'] ?? [];
if (!is_array($ids)) {
    $ids = [];
}

$ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($id) => $id > 0)));
if (!$ids) {
    sendResponse('success', 'No products requested.', []);
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$types = str_repeat('i', count($ids));
$stmt = $conn->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
$stmt->bind_param($types, ...$ids);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[(string) $row['id']] = apiProductPayload($row);
}

sendResponse('success', 'Products loaded.', $products);
