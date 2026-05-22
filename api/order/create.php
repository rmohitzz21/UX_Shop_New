<?php
require_once __DIR__ . '/../_bootstrap.php';

$user = apiRequireUser();
$input = apiInput();
validateCsrf();

$items = $input['items'] ?? [];
if (!is_array($items) || count($items) === 0) sendResponse('error', 'Cart is empty.', null, 400);

$paymentMethod = strtolower(trim((string) ($input['paymentMethod'] ?? 'cod')));
if (!in_array($paymentMethod, ['cod', 'card', 'upi'], true)) sendResponse('error', 'Invalid payment method.', null, 400);

$shipping = $input['shipping'] ?? [];
if (!is_array($shipping)) $shipping = [];

$conn->begin_transaction();
try {
    $subtotal = 0.0;
    $orderItems = [];
    $hasPhysical = false;

    foreach ($items as $cartItem) {
        $type = ($cartItem['item_type'] ?? $cartItem['type'] ?? 'product') === 'bundle' ? 'bundle' : 'product';
        $id = (int) ($cartItem['id'] ?? $cartItem['product_id'] ?? 0);
        $qty = max(1, min(10, (int) ($cartItem['quantity'] ?? 1)));
        if ($id <= 0) throw new InvalidArgumentException('Invalid cart item.');

        $table = $type === 'bundle' ? 'bundles' : 'products';
        $stmt = $conn->prepare("SELECT id, name, price, image, stock, available_type FROM `$table` WHERE id = ? AND is_active = 1 LIMIT 1 FOR UPDATE");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) throw new InvalidArgumentException('An item in your cart is no longer available.');

        $selectedType = $cartItem['available_type'] ?? ($type === 'bundle' ? 'digital' : ($row['available_type'] ?? 'digital'));
        if ($selectedType !== 'digital') $hasPhysical = true;
        if ($selectedType !== 'digital' && (int) $row['stock'] < $qty) {
            throw new InvalidArgumentException($row['name'] . ' has insufficient stock.');
        }
        if ($selectedType !== 'digital') {
            $update = $conn->prepare("UPDATE `$table` SET stock = stock - ?, sales_count = sales_count + ? WHERE id = ?");
            $update->bind_param('iii', $qty, $qty, $id);
            $update->execute();
        } else {
            $update = $conn->prepare("UPDATE `$table` SET sales_count = sales_count + ? WHERE id = ?");
            $update->bind_param('ii', $qty, $id);
            $update->execute();
        }

        $price = (float) $row['price'];
        $subtotal += $price * $qty;
        $orderItems[] = [
            'type' => $type,
            'id' => $id,
            'quantity' => $qty,
            'price' => $price,
            'size' => (string) ($cartItem['size'] ?? ''),
            'name' => $row['name'],
            'image' => $row['image'] ?? '',
            'available_type' => $selectedType,
        ];
    }

    $shippingCost = $hasPhysical ? 50.00 : 0.00;
    $tax = round($subtotal * 0.18, 2);
    $total = $subtotal + $shippingCost + $tax;
    $orderNumber = 'UXP-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    $status = 'Pending';
    $shippingJson = json_encode($shipping, JSON_UNESCAPED_SLASHES);

    $stmt = $conn->prepare('INSERT INTO orders (order_number, user_id, total, subtotal, shipping, tax, payment_method, status, shipping_address, status_updated_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
    $stmt->bind_param('siddddsss', $orderNumber, $user['id'], $total, $subtotal, $shippingCost, $tax, $paymentMethod, $status, $shippingJson);
    if (!$stmt->execute()) throw new RuntimeException('Could not create order.');
    $orderId = (int) $conn->insert_id;

    $itemStmt = $conn->prepare('INSERT INTO order_items (order_id, product_id, bundle_id, quantity, price, size, product_name, product_image, item_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($orderItems as $item) {
        $productId = $item['type'] === 'product' ? $item['id'] : null;
        $bundleId = $item['type'] === 'bundle' ? $item['id'] : null;
        $itemStmt->bind_param('iiiidssss', $orderId, $productId, $bundleId, $item['quantity'], $item['price'], $item['size'], $item['name'], $item['image'], $item['type']);
        $itemStmt->execute();

        if ($item['type'] === 'product') {
            $delete = $conn->prepare('DELETE FROM cart WHERE user_id = ? AND product_id = ?');
            $delete->bind_param('ii', $user['id'], $item['id']);
            $delete->execute();
        }
    }

    $conn->commit();
    sendResponse('success', 'Order placed successfully.', [
        'orderNumber' => $orderNumber,
        'orderId' => $orderId,
        'total' => $total,
        'subtotal' => $subtotal,
        'tax' => $tax,
        'shipping_cost' => $shippingCost,
        'status' => $status,
    ]);
} catch (InvalidArgumentException $e) {
    $conn->rollback();
    sendResponse('error', $e->getMessage(), null, 400);
} catch (Throwable $e) {
    $conn->rollback();
    error_log('api/order/create.php: ' . $e->getMessage());
    sendResponse('error', 'Failed to place order.', null, 500);
}
