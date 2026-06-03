<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../includes/OrderFulfillmentService.php';
require_once __DIR__ . '/../../includes/InventoryReservationService.php';

apiRequirePost();
$user  = apiRequireUser();
$input = apiInput();
validateCsrf();

$items = $input['items'] ?? [];
if (!is_array($items) || count($items) === 0) {
    sendResponse('error', 'Cart is empty.', null, 400);
}
if (count($items) > 50) {
    sendResponse('error', 'Too many items in cart. Maximum 50 allowed.', null, 400);
}

$paymentMethod = strtolower(trim((string) ($input['paymentMethod'] ?? 'cod')));
$allowedMethods = ['cod', 'card', 'upi', 'razorpay'];
if (getenv('ENABLE_TEST_PAYMENT') === 'true' && getenv('APP_ENV') !== 'production') {
    $allowedMethods[] = 'test';
}
if (!in_array($paymentMethod, $allowedMethods, true)) {
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
    $hasDigital  = false;

    foreach ($items as $cartItem) {
        $type = ($cartItem['item_type'] ?? $cartItem['type'] ?? 'product') === 'bundle' ? 'bundle' : 'product';
        $id   = $type === 'bundle'
            ? (int) ($cartItem['bundle_id'] ?? $cartItem['id'] ?? $cartItem['product_id'] ?? 0)
            : (int) ($cartItem['product_id'] ?? $cartItem['id'] ?? 0);
        $qty  = max(1, min(10, (int) ($cartItem['quantity'] ?? 1)));
        if ($id <= 0) {
            throw new InvalidArgumentException('Invalid cart item.');
        }

        if ($type === 'bundle') {
            $table = 'bundles';
            $stmt = $conn->prepare(
                'SELECT id, name, price, image, stock, "digital" AS available_type FROM bundles WHERE id = ? AND is_active = 1 LIMIT 1 FOR UPDATE'
            );
        } else {
            $table = 'products';
            $stmt = $conn->prepare(
                'SELECT id, name, price, image, stock, available_type FROM products WHERE id = ? AND is_active = 1 LIMIT 1 FOR UPDATE'
            );
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) {
            throw new InvalidArgumentException('An item in your cart is no longer available.');
        }

        $catalogType = (string) ($row['available_type'] ?? 'digital');
        if ($type === 'bundle') {
            $selectedFormat = 'digital';
        } elseif ($catalogType === 'digital') {
            $selectedFormat = 'digital';
        } elseif ($catalogType === 'physical') {
            $selectedFormat = 'physical';
        } else {
            $requested = trim((string) ($cartItem['selected_format'] ?? $cartItem['available_type'] ?? ''));
            $selectedFormat = $requested === 'physical' ? 'physical' : 'digital';
        }

        if ($selectedFormat === 'digital') {
            $hasDigital = true;
        } else {
            $hasPhysical = true;
        }

        if ($selectedFormat !== 'digital' && (int) $row['stock'] < $qty) {
            throw new InvalidArgumentException($row['name'] . ' has insufficient stock.');
        }

        if ($selectedFormat !== 'digital') {
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
            'type'            => $type,
            'id'              => $id,
            'quantity'        => $qty,
            'price'           => $price,
            'size'            => (string) ($cartItem['size'] ?? ''),
            'name'            => $row['name'],
            'image'           => $row['image'] ?? '',
            'selected_format' => $selectedFormat,
        ];
    }

    if ($hasDigital && $paymentMethod === 'cod') {
        throw new InvalidArgumentException('Cash on Delivery is not available for orders containing digital products. Please use online payment.');
    }

    $shippingCost = $hasPhysical ? 50.00 : 0.00;
    $tax          = round($subtotal * 0.18, 2);
    $total        = $subtotal + $shippingCost + $tax;
    $orderNumber  = 'UXP-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    $isFreeOrder  = $total <= 0;

    if ($isFreeOrder) {
        $orderStatus    = 'paid';
        $paymentStatus  = 'paid';
        $paymentMethod  = 'free';
    } elseif (in_array($paymentMethod, ['razorpay', 'test'], true)) {
        $orderStatus   = 'awaiting_payment';
        $paymentStatus = 'pending';
    } else {
        $orderStatus   = 'pending';
        $paymentStatus = 'pending';
    }

    $shippingJson = json_encode($shipping, JSON_UNESCAPED_SLASHES);

    $stmt = $conn->prepare(
        'INSERT INTO orders (order_number, user_id, total, subtotal, shipping, tax, payment_method, status, payment_status, shipping_address, status_updated_at, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
    );
    $stmt->bind_param(
        'siddddssss',
        $orderNumber,
        $user['id'],
        $total,
        $subtotal,
        $shippingCost,
        $tax,
        $paymentMethod,
        $orderStatus,
        $paymentStatus,
        $shippingJson
    );
    if (!$stmt->execute()) {
        throw new RuntimeException('Could not create order.');
    }
    $orderId = (int) $conn->insert_id;

    if ($isFreeOrder) {
        $paidAt = $conn->prepare('UPDATE orders SET paid_at = NOW() WHERE id = ?');
        $paidAt->bind_param('i', $orderId);
        $paidAt->execute();
    }

    $itemStmt = $conn->prepare(
        'INSERT INTO order_items (order_id, product_id, bundle_id, quantity, price, size, product_name, product_image, item_type, selected_format)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ($orderItems as $item) {
        $productId = $item['type'] === 'product' ? $item['id'] : null;
        $bundleId  = $item['type'] === 'bundle'  ? $item['id'] : null;
        $format    = $item['selected_format'];
        $itemStmt->bind_param(
            'iiiidsssss',
            $orderId,
            $productId,
            $bundleId,
            $item['quantity'],
            $item['price'],
            $item['size'],
            $item['name'],
            $item['image'],
            $item['type'],
            $format
        );
        $itemStmt->execute();
        $orderItemId = (int) $conn->insert_id;

        if ($item['selected_format'] !== 'digital') {
            InventoryReservationService::create(
                $conn,
                $orderId,
                $orderItemId,
                $item['type'],
                $item['id'],
                $item['quantity']
            );
            if ($isFreeOrder || !in_array($paymentMethod, ['razorpay', 'test'], true)) {
                InventoryReservationService::consumeOrder($conn, $orderId);
            }
        }

        if ($isFreeOrder || !in_array($paymentMethod, ['razorpay', 'test'], true)) {
            if ($item['type'] === 'product') {
                $del = $conn->prepare('DELETE FROM cart WHERE user_id = ? AND item_type = "product" AND product_id = ?');
                $del->bind_param('ii', $user['id'], $item['id']);
                $del->execute();
            } elseif ($item['type'] === 'bundle') {
                $del = $conn->prepare('DELETE FROM cart WHERE user_id = ? AND item_type = "bundle" AND bundle_id = ?');
                $del->bind_param('ii', $user['id'], $item['id']);
                $del->execute();
            }
        }
    }

    $conn->commit();

    if ($isFreeOrder) {
        try {
            OrderFulfillmentService::fulfillPaidOrder($orderId, $conn);
        } catch (Throwable $e) {
            error_log('api/order/create.php free fulfillment: ' . $e->getMessage());
        }

        sendResponse('success', 'Order placed successfully.', [
            'orderNumber'   => $orderNumber,
            'orderId'       => $orderId,
            'total'         => $total,
            'subtotal'      => $subtotal,
            'tax'           => $tax,
            'shipping_cost' => $shippingCost,
            'status'        => $orderStatus,
            'payment_status'=> $paymentStatus,
            'free_order'    => true,
            'redirect'      => 'order-confirmation.php?order_id=' . $orderId,
        ]);
    }

    if (!in_array($paymentMethod, ['razorpay', 'test'], true)) {
        try {
            require_once __DIR__ . '/../../includes/EmailService.php';
            EmailService::sendOrderConfirmation($orderId, $conn);
            if ($paymentMethod === 'cod') {
                // COD uses same confirmation email with delivery messaging; no invoice until paid.
            }
        } catch (Throwable $ignored) {
            error_log('Order confirmation email failed for order ' . $orderNumber);
        }
    }

    sendResponse('success', 'Order placed successfully.', [
        'orderNumber'    => $orderNumber,
        'orderId'        => $orderId,
        'total'          => $total,
        'subtotal'       => $subtotal,
        'tax'            => $tax,
        'shipping_cost'  => $shippingCost,
        'status'         => $orderStatus,
        'payment_status' => $paymentStatus,
    ]);
} catch (InvalidArgumentException $e) {
    $conn->rollback();
    sendResponse('error', $e->getMessage(), null, 400);
} catch (Throwable $e) {
    $conn->rollback();
    error_log('api/order/create.php: ' . $e->getMessage());
    sendResponse('error', 'Failed to place order. Please try again.', null, 500);
}
