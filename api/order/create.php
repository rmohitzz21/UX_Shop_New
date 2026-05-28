<?php
require_once __DIR__ . '/../_bootstrap.php';

apiRequirePost();
$user  = apiRequireUser();
$input = apiInput();
validateCsrf();

$items = $input['items'] ?? [];
if (!is_array($items) || count($items) === 0) {
    sendResponse('error', 'Cart is empty.', null, 400);
}

$paymentMethod = strtolower(trim((string) ($input['paymentMethod'] ?? 'cod')));
if (!in_array($paymentMethod, ['cod', 'card', 'upi', 'razorpay'], true)) {
    sendResponse('error', 'Invalid payment method.', null, 400);
}

$shipping = $input['shipping'] ?? [];
if (!is_array($shipping)) {
    $shipping = [];
}

$conn->begin_transaction();
try {
    $subtotal    = 0.0;
    $orderItems  = [];
    $hasPhysical = false;

    foreach ($items as $cartItem) {
        $type = ($cartItem['item_type'] ?? $cartItem['type'] ?? 'product') === 'bundle' ? 'bundle' : 'product';
        $id   = (int) ($cartItem['id'] ?? $cartItem['product_id'] ?? 0);
        $qty  = max(1, min(10, (int) ($cartItem['quantity'] ?? 1)));
        if ($id <= 0) {
            throw new InvalidArgumentException('Invalid cart item.');
        }

        $table = $type === 'bundle' ? 'bundles' : 'products';
        $stmt  = $conn->prepare("SELECT id, name, price, image, stock, available_type FROM `{$table}` WHERE id = ? AND is_active = 1 LIMIT 1 FOR UPDATE");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) {
            throw new InvalidArgumentException('An item in your cart is no longer available.');
        }

        $selectedType = $cartItem['available_type'] ?? ($type === 'bundle' ? 'digital' : ($row['available_type'] ?? 'digital'));
        if ($selectedType !== 'digital') {
            $hasPhysical = true;
        }
        if ($selectedType !== 'digital' && (int) $row['stock'] < $qty) {
            throw new InvalidArgumentException($row['name'] . ' has insufficient stock.');
        }

        if ($selectedType !== 'digital') {
            $update = $conn->prepare("UPDATE `{$table}` SET stock = stock - ?, sales_count = sales_count + ? WHERE id = ?");
            $update->bind_param('iii', $qty, $qty, $id);
            $update->execute();
        } else {
            $update = $conn->prepare("UPDATE `{$table}` SET sales_count = sales_count + ? WHERE id = ?");
            $update->bind_param('ii', $qty, $id);
            $update->execute();
        }

        $price     = (float) $row['price'];
        $subtotal += $price * $qty;
        $orderItems[] = [
            'type'           => $type,
            'id'             => $id,
            'quantity'       => $qty,
            'price'          => $price,
            'size'           => (string) ($cartItem['size'] ?? ''),
            'name'           => $row['name'],
            'image'          => $row['image'] ?? '',
            'available_type' => $selectedType,
        ];
    }

    $shippingCost = $hasPhysical ? 50.00 : 0.00;
    $tax          = round($subtotal * 0.18, 2);
    $total        = $subtotal + $shippingCost + $tax;
    $orderNumber  = 'UXP-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    $orderStatus  = ($paymentMethod === 'razorpay') ? 'awaiting_payment' : 'Pending';
    $shippingJson = json_encode($shipping, JSON_UNESCAPED_SLASHES);

    $stmt = $conn->prepare('INSERT INTO orders (order_number, user_id, total, subtotal, shipping, tax, payment_method, status, shipping_address, status_updated_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
    $stmt->bind_param('siddddsss', $orderNumber, $user['id'], $total, $subtotal, $shippingCost, $tax, $paymentMethod, $orderStatus, $shippingJson);
    if (!$stmt->execute()) {
        throw new RuntimeException('Could not create order.');
    }
    $orderId = (int) $conn->insert_id;

    $itemStmt = $conn->prepare('INSERT INTO order_items (order_id, product_id, bundle_id, quantity, price, size, product_name, product_image, item_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($orderItems as $item) {
        $productId = $item['type'] === 'product' ? $item['id'] : null;
        $bundleId  = $item['type'] === 'bundle'  ? $item['id'] : null;
        $itemStmt->bind_param('iiiidssss', $orderId, $productId, $bundleId, $item['quantity'], $item['price'], $item['size'], $item['name'], $item['image'], $item['type']);
        $itemStmt->execute();

        // Clear this item from cart for both products and bundles
        if ($item['type'] === 'product') {
            $del = $conn->prepare('DELETE FROM cart WHERE user_id = ? AND product_id = ?');
            $del->bind_param('ii', $user['id'], $item['id']);
            $del->execute();
        } elseif ($item['type'] === 'bundle') {
            // Remove any product proxy that represents this bundle from cart
            $del = $conn->prepare(
                'DELETE FROM cart WHERE user_id = ? AND product_id IN (SELECT p.id FROM products p WHERE p.name = (SELECT b.name FROM bundles b WHERE b.id = ?) LIMIT 1)'
            );
            $del->bind_param('ii', $user['id'], $item['id']);
            $del->execute();
        }
    }

    $conn->commit();

    // Send order confirmation email (non-fatal)
    try {
        $emailItems = array_map(fn($i) => ['name' => $i['name'], 'quantity' => $i['quantity'], 'price' => $i['price']], $orderItems);
        sendOrderConfirmationEmail(
            $user['email'],
            $user['firstName'] . ' ' . $user['lastName'],
            ['order_number' => $orderNumber, 'date' => date('Y-m-d'), 'items' => $emailItems, 'total' => $total]
        );
    } catch (Throwable $ignored) {
        error_log('Order confirmation email failed for order ' . $orderNumber);
    }

    sendResponse('success', 'Order placed successfully.', [
        'orderNumber'   => $orderNumber,
        'orderId'       => $orderId,
        'total'         => $total,
        'subtotal'      => $subtotal,
        'tax'           => $tax,
        'shipping_cost' => $shippingCost,
        'status'        => $orderStatus,
    ]);
} catch (InvalidArgumentException $e) {
    $conn->rollback();
    sendResponse('error', $e->getMessage(), null, 400);
} catch (Throwable $e) {
    $conn->rollback();
    error_log('api/order/create.php: ' . $e->getMessage());
    sendResponse('error', 'Failed to place order. Please try again.', null, 500);
}
