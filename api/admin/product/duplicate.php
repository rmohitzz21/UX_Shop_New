<?php
require_once __DIR__ . '/../_admin.php';
validateCsrf();
$input = adminInput();
$id = (int) ($input['id'] ?? 0);
if ($id <= 0) sendResponse('error', 'Product ID is required.', null, 422);

$stmt = $conn->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row) sendResponse('error', 'Product not found.', null, 404);

$name = $row['name'] . ' Copy';
$slug = slugify($name . '-' . bin2hex(random_bytes(2)));
$sku = !empty($row['sku']) ? $row['sku'] . '-COPY' : null;
$copy = $conn->prepare('INSERT INTO products (name, slug, sku, description, whats_included, file_specification, category, tags, related_products, price, old_price, commercial_price, image, additional_images, stock, rating, available_type, is_active, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0)');
$copy->bind_param('sssssssssdddssids', $name, $slug, $sku, $row['description'], $row['whats_included'], $row['file_specification'], $row['category'], $row['tags'], $row['related_products'], $row['price'], $row['old_price'], $row['commercial_price'], $row['image'], $row['additional_images'], $row['stock'], $row['rating'], $row['available_type']);
$copy->execute();
sendResponse('success', 'Product duplicated.', ['id' => (int) $conn->insert_id]);
