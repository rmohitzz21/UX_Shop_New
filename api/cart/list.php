<?php
require_once __DIR__ . '/../_bootstrap.php';

$user = apiRequireUser();

// Cleanup stale product-type rows where the product was deleted or deactivated
$cleanProd = $conn->prepare(
    'DELETE c FROM cart c
     LEFT JOIN products p ON p.id = c.product_id AND p.is_active = 1
     WHERE c.user_id = ? AND c.item_type = "product" AND p.id IS NULL'
);
$cleanProd->bind_param('i', $user['id']);
$cleanProd->execute();

// Cleanup stale bundle-type rows where the bundle was deleted or deactivated
$cleanBund = $conn->prepare(
    'DELETE c FROM cart c
     LEFT JOIN bundles b ON b.id = c.bundle_id AND b.is_active = 1
     WHERE c.user_id = ? AND c.item_type = "bundle" AND b.id IS NULL'
);
$cleanBund->bind_param('i', $user['id']);
$cleanBund->execute();

$stmt = $conn->prepare('
    SELECT
        c.id               AS cart_id,
        c.item_type,
        c.product_id,
        c.bundle_id,
        c.quantity,
        c.size,
        c.available_type,
        c.selected_format,
        COALESCE(p.name,  b.name)                       AS name,
        COALESCE(p.price, b.price)                      AS price,
        COALESCE(p.image, b.image, "img/sticker.webp")  AS image,
        COALESCE(p.description, b.description, "")      AS description,
        COALESCE(p.category, b.category, "")            AS category
    FROM cart c
    LEFT JOIN products p ON p.id = c.product_id AND c.item_type = "product" AND p.is_active = 1
    LEFT JOIN bundles  b ON b.id = c.bundle_id  AND c.item_type = "bundle"  AND b.is_active = 1
    WHERE c.user_id = ?
      AND (
          (c.item_type = "product" AND p.id IS NOT NULL)
          OR
          (c.item_type = "bundle"  AND b.id IS NOT NULL)
      )
    ORDER BY c.created_at DESC, c.id DESC
');
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $catalogId      = $row['item_type'] === 'bundle' ? (int) $row['bundle_id'] : (int) $row['product_id'];
    $selectedFormat = $row['selected_format'] ?? $row['available_type'] ?? 'physical';
    $items[] = [
        'cart_id'        => (int)    $row['cart_id'],
        'id'             => (string) $catalogId,
        'item_type'      => $row['item_type'],
        'product_id'     => $row['product_id'] !== null ? (int) $row['product_id'] : null,
        'bundle_id'      => $row['bundle_id']  !== null ? (int) $row['bundle_id']  : null,
        'name'           => (string) $row['name'],
        'price'          => (float)  $row['price'],
        'image'          => apiProductImage($row['image']),
        'description'    => (string) ($row['description'] ?? ''),
        'category'       => (string) ($row['category']    ?? ''),
        'quantity'       => (int)    $row['quantity'],
        'size'           => $row['size'] ?? null,
        'available_type' => $selectedFormat,
        'selected_format'=> $selectedFormat,
    ];
}

sendResponse('success', 'Cart loaded.', $items);
