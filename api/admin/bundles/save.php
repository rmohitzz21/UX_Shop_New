<?php
require_once __DIR__ . '/../_admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', 'Method not allowed.', null, 405);
}
validateCsrf();
$input = adminInput();
$id = (int) ($input['id'] ?? 0);
$name = trim((string) ($input['name'] ?? ''));
if ($name === '') sendResponse('error', 'Bundle name is required.', null, 422);

$description = trim((string) ($input['description'] ?? ''));
$category = trim((string) ($input['category'] ?? 'Bundles'));
$tags = trim((string) ($input['tags'] ?? ''));
$price = (float) ($input['price'] ?? 0);
$old = adminNullableFloat($input['old_price'] ?? null);
$stock = max(0, (int) ($input['stock'] ?? 999));
$rating = max(0, min(5, (float) ($input['rating'] ?? 4.7)));
$featured = adminBool($input['is_featured'] ?? 0, 0);
$active = adminBool($input['is_active'] ?? 1, 1);
$badgeText = trim((string) ($input['badge_text'] ?? ''));
if ($badgeText === '') {
    $badgeText = $featured ? 'Best Seller' : 'Most Popular';
}
$slug = slugify($input['slug'] ?? $name);
$image = trim((string) ($input['existing_image'] ?? $input['image'] ?? ''));
$uploaded = adminUploadImages('image');
if ($uploaded) $image = $uploaded[0];
if ($image === '') $image = 'img/poster.webp';

// Prefer whats_included textarea (one item per line) as the source of truth.
// Falls back to included_items JSON for legacy toggle calls.
$whatsIncluded = trim((string) ($input['whats_included'] ?? ''));
$fileSpec = trim((string) ($input['file_specification'] ?? ''));

// Additional images: accept newline-separated (admin form) OR JSON array (toggle payload)
$additionalImagesRaw = trim((string) ($input['additional_images'] ?? ''));
$additionalImagesArr = [];
if ($additionalImagesRaw !== '') {
    $decoded = json_decode($additionalImagesRaw, true);
    if (is_array($decoded)) {
        foreach ($decoded as $img) {
            $img = trim((string) $img);
            if ($img !== '') $additionalImagesArr[] = $img;
        }
    } else {
        foreach (preg_split('/\r?\n/', $additionalImagesRaw) as $line) {
            $line = trim($line);
            if ($line !== '') $additionalImagesArr[] = $line;
        }
    }
}
$additionalImagesJson = $additionalImagesArr ? json_encode($additionalImagesArr, JSON_UNESCAPED_SLASHES) : null;

$includedItems = [];
if ($whatsIncluded !== '') {
    foreach (preg_split('/\r?\n/', $whatsIncluded) as $line) {
        $line = trim(preg_replace('/^[-•*]\s*/', '', $line));
        if ($line !== '') $includedItems[] = ['label' => $line];
    }
} elseif (isset($input['included_items'])) {
    $decoded = json_decode((string) $input['included_items'], true);
    if (is_array($decoded)) {
        foreach ($decoded as $it) {
            $label = is_array($it) ? trim((string) ($it['label'] ?? $it['name'] ?? '')) : trim((string) $it);
            if ($label !== '') $includedItems[] = ['label' => $label];
        }
    } else {
        foreach (preg_split('/\r?\n/', (string) $input['included_items']) as $line) {
            $line = trim($line);
            if ($line !== '') $includedItems[] = ['label' => $line];
        }
    }
}
$includedJson = json_encode($includedItems, JSON_UNESCAPED_SLASHES);

$productItems = [];
if (isset($input['product_ids'])) {
    $raw = is_array($input['product_ids']) ? $input['product_ids'] : preg_split('/[,\s]+/', (string) $input['product_ids']);
    foreach ($raw as $pid) {
        $pid = (int) $pid;
        if ($pid > 0) $productItems[$pid] = ($productItems[$pid] ?? 0) + 1;
    }
}

if ($id > 0) {
    $beforeStmt = $conn->prepare('SELECT stock, image FROM bundles WHERE id = ? LIMIT 1');
    $beforeStmt->bind_param('i', $id);
    $beforeStmt->execute();
    $before = $beforeStmt->get_result()->fetch_assoc();
    if (!$before) sendResponse('error', 'Bundle not found.', null, 404);
    if (!$uploaded && $image === '') $image = $before['image'] ?: 'img/poster.webp';

    $stmt = $conn->prepare('UPDATE bundles SET name=?, slug=?, description=?, category=?, tags=?, included_items=?, whats_included=?, file_specification=?, additional_images=?, price=?, old_price=?, image=?, badge_text=?, rating=?, stock=?, is_featured=?, is_active=? WHERE id=?');
    $stmt->bind_param('sssssssssddssdiiii', $name, $slug, $description, $category, $tags, $includedJson, $whatsIncluded, $fileSpec, $additionalImagesJson, $price, $old, $image, $badgeText, $rating, $stock, $featured, $active, $id);
    if (!$stmt->execute()) sendResponse('error', 'Could not update bundle: ' . $stmt->error, null, 500);
    adminRecordInventory($conn, 'bundle', $id, (int) $before['stock'], $stock, 'Admin bundle update');
} else {
    $stmt = $conn->prepare('INSERT INTO bundles (name, slug, description, category, tags, included_items, whats_included, file_specification, additional_images, price, old_price, image, badge_text, rating, stock, is_featured, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('sssssssssddssdiii', $name, $slug, $description, $category, $tags, $includedJson, $whatsIncluded, $fileSpec, $additionalImagesJson, $price, $old, $image, $badgeText, $rating, $stock, $featured, $active);
    if (!$stmt->execute()) sendResponse('error', 'Could not create bundle: ' . $stmt->error, null, 500);
    $id = (int) $conn->insert_id;
    adminRecordInventory($conn, 'bundle', $id, 0, $stock, 'Admin bundle create');
}

$conn->begin_transaction();
try {
    // Only update bundle_items if product_ids were provided (and parsed into at least one valid product id).
    // This prevents accidental wiping of existing bundle contents during edits where the admin leaves product_ids blank.
    $shouldUpdateItems = !empty($productItems);
    if ($shouldUpdateItems) {
        $clear = $conn->prepare('DELETE FROM bundle_items WHERE bundle_id = ?');
        $clear->bind_param('i', $id);
        $clear->execute();

        $insert = $conn->prepare('INSERT INTO bundle_items (bundle_id, product_id, quantity) VALUES (?, ?, ?)');
        foreach ($productItems as $productId => $quantity) {
            $insert->bind_param('iii', $id, $productId, $quantity);
            $insert->execute();
        }
    }
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    sendResponse('error', 'Could not update bundle products.', null, 500);
}

sendResponse('success', 'Bundle saved.', ['id' => $id, 'image' => $image]);
