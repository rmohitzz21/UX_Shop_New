<?php
require_once __DIR__ . '/../_admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', 'Method not allowed.', null, 405);
}
validateCsrf();
$input = adminInput();
$id = (int) ($input['id'] ?? 0);
$name = trim((string) ($input['name'] ?? ''));
if ($name === '') sendResponse('error', 'Product name is required.', null, 422);

$category = trim((string) ($input['category'] ?? 'Uncategorized'));
$description = trim((string) ($input['description'] ?? ''));
$whats = trim((string) ($input['whats_included'] ?? ''));
$specs = trim((string) ($input['file_specification'] ?? ''));
$sku = trim((string) ($input['sku'] ?? ''));
$tags = trim((string) ($input['tags'] ?? ''));
$price = (float) ($input['price'] ?? 0);
$oldPrice = adminNullableFloat($input['old_price'] ?? null);
$commercialPrice = adminNullableFloat($input['commercial_price'] ?? null);
$stock = max(0, (int) ($input['stock'] ?? 0));
$rating = max(0, min(5, (float) ($input['rating'] ?? 4.5)));
$availableType = trim((string) ($input['available_type'] ?? 'digital'));
if (!in_array($availableType, ['physical', 'digital', 'both'], true)) $availableType = 'digital';
$active = adminBool($input['is_active'] ?? 1, 1);
$featured = adminBool($input['is_featured'] ?? 0, 0);
$isFree = adminBool($input['is_free'] ?? 0, 0);
if ($isFree) {
    $price = 0.0;
    $oldPrice = null;
    $commercialPrice = null;
    if ($availableType === 'physical') {
        $availableType = 'digital';
    }
}
$slug = slugify($input['slug'] ?? $name);

$imageUploads = adminUploadImages('image');
$mediaUploads = adminUploadImages('media');
$mainImage = trim((string) ($input['existing_image'] ?? $input['image_path'] ?? ''));
$additionalImages = [];
if (!empty($input['additional_images'])) {
    $raw = trim((string) $input['additional_images']);
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $additionalImages = array_values(array_filter($decoded, 'is_string'));
    } elseif ($raw !== '') {
        foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
            $line = trim($line);
            if ($line !== '') $additionalImages[] = $line;
        }
    }
}
if ($mainImage === '' && !empty($imageUploads)) {
    $mainImage = $imageUploads[0];
}
if (!empty($mediaUploads)) {
    $additionalImages = array_values(array_unique(array_merge($additionalImages, $mediaUploads)));
}
if ($mainImage === '') $mainImage = 'img/poster.webp';
$additionalImages = array_values(array_unique(array_filter(
    $additionalImages,
    static function ($path) use ($mainImage): bool {
        return is_string($path) && $path !== '' && $path !== $mainImage;
    }
)));
$additionalJson = json_encode($additionalImages, JSON_UNESCAPED_SLASHES);

if ($id > 0) {
    $beforeStmt = $conn->prepare('SELECT stock, image, additional_images FROM products WHERE id = ? LIMIT 1');
    $beforeStmt->bind_param('i', $id);
    $beforeStmt->execute();
    $before = $beforeStmt->get_result()->fetch_assoc();
    if (!$before) sendResponse('error', 'Product not found.', null, 404);
    if (empty($imageUploads) && $mainImage === '') $mainImage = $before['image'] ?: 'img/poster.webp';
    if (empty($input['additional_images']) && empty($additionalImages) && !empty($before['additional_images'])) {
        $decoded = json_decode((string) $before['additional_images'], true);
        if (is_array($decoded)) {
            $additionalImages = array_values(array_filter($decoded, 'is_string'));
        }
    }

    $stmt = $conn->prepare('UPDATE products SET name=?, slug=?, sku=?, description=?, whats_included=?, file_specification=?, category=?, tags=?, price=?, old_price=?, commercial_price=?, image=?, additional_images=?, stock=?, rating=?, available_type=?, is_active=?, is_featured=?, is_free=? WHERE id=?');
    $stmt->bind_param('ssssssssdddssidsiiii', $name, $slug, $sku, $description, $whats, $specs, $category, $tags, $price, $oldPrice, $commercialPrice, $mainImage, $additionalJson, $stock, $rating, $availableType, $active, $featured, $isFree, $id);
    if (!$stmt->execute()) sendResponse('error', 'Could not update product: ' . $stmt->error, null, 500);
    adminRecordInventory($conn, 'product', $id, (int) $before['stock'], $stock, 'Admin product update');
    sendResponse('success', 'Product updated.', ['id' => $id, 'image' => $mainImage]);
}

$stmt = $conn->prepare('INSERT INTO products (name, slug, sku, description, whats_included, file_specification, category, tags, price, old_price, commercial_price, image, additional_images, stock, rating, available_type, is_active, is_featured, is_free) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
$stmt->bind_param('ssssssssdddssidsiii', $name, $slug, $sku, $description, $whats, $specs, $category, $tags, $price, $oldPrice, $commercialPrice, $mainImage, $additionalJson, $stock, $rating, $availableType, $active, $featured, $isFree);
if (!$stmt->execute()) sendResponse('error', 'Could not create product: ' . $stmt->error, null, 500);
$newId = (int) $conn->insert_id;
adminRecordInventory($conn, 'product', $newId, 0, $stock, 'Admin product create');
sendResponse('success', 'Product created.', ['id' => $newId, 'image' => $mainImage]);
