<?php
require_once __DIR__ . '/../_bootstrap.php';

apiRequirePost();
$user  = apiRequireUser();
$input = apiInput();
validateCsrf();

$items = $input['cart'] ?? [];
if (!is_array($items)) {
    $items = [];
}
if (count($items) > 50) {
    $items = array_slice($items, 0, 50);
}

foreach ($items as $item) {
    if (!is_array($item)) {
        continue;
    }

    $itemType = apiNormalizeCartItemType((string) ($item['item_type'] ?? $item['type'] ?? 'product'));

    $quantity = max(1, min(10, (int) ($item['quantity'] ?? 1)));
    $size     = trim((string) ($item['size'] ?? ''));
    $sizeVal  = $size !== '' ? $size : null;

    if ($itemType === 'freebie') {
        $catalogId = (int) ($item['product_id'] ?? $item['id'] ?? 0);
        if ($catalogId <= 0) continue;

        $chk = $conn->prepare('SELECT id FROM freebies WHERE id = ? AND is_active = 1 LIMIT 1');
        $chk->bind_param('i', $catalogId);
        $chk->execute();
        if (!$chk->get_result()->fetch_assoc()) continue;

        $catalogAvailType = 'digital';
        $productId = $catalogId;
        $bundleId  = null;
    } elseif ($itemType === 'product') {
        $catalogId = (int) ($item['product_id'] ?? $item['id'] ?? 0);
        if ($catalogId <= 0) continue;

        $chk = $conn->prepare('SELECT id, available_type FROM products WHERE id = ? AND is_active = 1 LIMIT 1');
        $chk->bind_param('i', $catalogId);
        $chk->execute();
        $row = $chk->get_result()->fetch_assoc();
        if (!$row) continue;

        $catalogAvailType = (string) $row['available_type'];
        $productId = $catalogId;
        $bundleId  = null;
    } else {
        $catalogId = (int) ($item['bundle_id'] ?? $item['id'] ?? $item['product_id'] ?? 0);
        if ($catalogId <= 0) continue;

        $chk = $conn->prepare('SELECT id FROM bundles WHERE id = ? AND is_active = 1 LIMIT 1');
        $chk->bind_param('i', $catalogId);
        $chk->execute();
        if (!$chk->get_result()->fetch_assoc()) continue;

        $catalogAvailType = 'digital';
        $productId = null;
        $bundleId  = $catalogId;
    }

    $requestedFormat = trim((string) ($item['selected_format'] ?? $item['available_type'] ?? ''));
    if ($itemType === 'freebie' || $catalogAvailType === 'digital') {
        $selectedFormat = 'digital';
    } elseif ($catalogAvailType === 'physical') {
        $selectedFormat = 'physical';
    } else {
        $selectedFormat = $requestedFormat === 'digital' ? 'digital' : 'physical';
    }

    if ($itemType === 'product') {
        $sel = $conn->prepare(
            'SELECT id, quantity FROM cart
             WHERE user_id = ? AND item_type = ? AND product_id = ? AND selected_format = ?
               AND COALESCE(size, "") = COALESCE(?, "")
             LIMIT 1'
        );
        $sel->bind_param('isiss', $user['id'], $itemType, $productId, $selectedFormat, $sizeVal);
    } else {
        $sel = $conn->prepare(
            'SELECT id, quantity FROM cart
             WHERE user_id = ? AND item_type = ? AND bundle_id = ? AND selected_format = ?
               AND COALESCE(size, "") = COALESCE(?, "")
             LIMIT 1'
        );
        $sel->bind_param('isiss', $user['id'], $itemType, $bundleId, $selectedFormat, $sizeVal);
    }
    $sel->execute();
    $existing = $sel->get_result()->fetch_assoc();

    if ($existing) {
        $newQty = min(10, (int) $existing['quantity'] + $quantity);
        $upd = $conn->prepare('UPDATE cart SET quantity = ? WHERE id = ?');
        $upd->bind_param('ii', $newQty, $existing['id']);
        $upd->execute();
    } else {
        if ($itemType === 'product') {
            $ins = $conn->prepare(
                'INSERT INTO cart (user_id, item_type, product_id, quantity, size, available_type, selected_format)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $ins->bind_param('isiisss', $user['id'], $itemType, $productId, $quantity, $sizeVal, $catalogAvailType, $selectedFormat);
        } else {
            $ins = $conn->prepare(
                'INSERT INTO cart (user_id, item_type, bundle_id, quantity, size, available_type, selected_format)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $ins->bind_param('isiisss', $user['id'], $itemType, $bundleId, $quantity, $sizeVal, $catalogAvailType, $selectedFormat);
        }
        $ins->execute();
    }
}

sendResponse('success', 'Cart merged.');
