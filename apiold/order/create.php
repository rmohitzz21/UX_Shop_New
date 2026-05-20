<?php
header('Content-Type: application/json');

require_once '../../includes/config.php';
require_once '../../includes/helpers.php';

requireUserAuth();

// Get raw POST data
$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
    sendResponse('error', 'Invalid JSON data', null, 400);
}
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($data['csrf_token'] ?? '');
validateCsrfFromToken((string) $csrfToken);

// 1. Validate required fields
$required_fields = ['items', 'paymentMethod', 'shipping'];
foreach ($required_fields as $field) {
    if (!isset($data[$field])) {
        sendResponse('error', "Missing field: $field", null, 400);
    }
}
if (!is_array($data['items']) || count($data['items']) === 0) {
    sendResponse('error', 'Cart is empty. Add at least one item before checkout.', null, 400);
}
if (!is_array($data['shipping'])) {
    sendResponse('error', 'Invalid shipping data', null, 400);
}

$user_id = (int) $_SESSION['user_id'];

// 3. Initiate Calculation & Validation
$calculated_subtotal = 0;
$order_items_data = [];
$has_digital_items = false;

// Start transaction for stock check/update
$conn->begin_transaction();

try {
    foreach ($data['items'] as $item) {
        $product_id = intval($item['id'] ?? 0);
        $quantity = intval($item['quantity'] ?? 0);
        // Use raw size for prepared statements to avoid double escaping
        $size = isset($item['size']) ? $item['size'] : '';

        if ($product_id <= 0) {
            throw new InvalidArgumentException("Invalid product ID in cart");
        }
        if ($quantity <= 0) {
            throw new InvalidArgumentException("Quantity must be positive for all items");
        }

        if ($quantity > 10) {
            throw new Exception("Maximum 10 items per product allowed");
        }

        // Fetch Product Data (Price, Stock, Type, Name, Image) from DB
        $stmt = $conn->prepare("SELECT price, stock, available_type, name, image FROM products WHERE id = ? FOR UPDATE");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new InvalidArgumentException("Product ID $product_id not found");
        }
        
        $product = $result->fetch_assoc();
        $stmt->close();
        
        $price         = floatval($product['price']);
        $current_stock = intval($product['stock']);
        $type          = $product['available_type']; // physical, digital, both
        $product_name  = $product['name'];
        $product_image = $product['image'];
        
        // Digital Check
        // If product is strictly digital, flag it. 
        // If it's "both" and user selected digital, flag it (we trust user selection for format if 'both')
        // Current cart logic sends 'available_type' in item.
        $selected_type = isset($item['available_type']) ? $item['available_type'] : 'physical';
        if (!in_array($selected_type, ['physical', 'digital', 'both'], true)) {
            throw new InvalidArgumentException("Invalid available_type for product ID $product_id");
        }
        
        if ($type === 'digital' || ($type === 'both' && $selected_type === 'digital')) {
            $has_digital_items = true;
        }

        // Stock Validation (Skip for digital items usually, but let's assume stock tracks licenses too or just unlimited?)
        // Assuming infinite stock for digital, or managed.
        // If physical, check stock.
        if ($selected_type === 'physical') {
            if ($current_stock < $quantity) {
                throw new Exception("Insufficient stock for product ID $product_id. Available: $current_stock");
            }
            
            // Deduct Stock
            $updateStock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $updateStock->bind_param("ii", $quantity, $product_id);
            if (!$updateStock->execute()) {
                throw new Exception("Failed to update stock");
            }
            $updateStock->close();
        }

        // Add to subtotal
        $item_total = $price * $quantity;
        $calculated_subtotal += $item_total;
        
        $order_items_data[] = [
            'product_id'    => $product_id,
            'quantity'      => $quantity,
            'price'         => $price,         // Use DB price — never trust client
            'size'          => $size,
            'type'          => $selected_type,
            'product_name'  => $product_name,  // Snapshot name
            'product_image' => $product_image, // Snapshot image path
        ];
    }
    if (count($order_items_data) === 0) {
        throw new InvalidArgumentException('No valid cart items found');
    }
    
    // 4. Calculate Taxes & Shipping
    // Determine if all items are digital — if so, shipping is free
    $all_digital = true;
    foreach ($order_items_data as $oi) {
        if (isset($oi['type']) && $oi['type'] !== 'digital') {
            $all_digital = false;
            break;
        }
        if (!isset($oi['type'])) {
            $all_digital = false;
            break;
        }
    }
    $shipping_cost = ($calculated_subtotal > 0 && !$all_digital) ? 50.00 : 0.00;
    $tax = round($calculated_subtotal * 0.18, 2);
    $calculated_total = $calculated_subtotal + $shipping_cost + $tax;
    if ($calculated_total <= 0) {
        throw new InvalidArgumentException('Order total must be greater than zero');
    }
    
    // 5. Validate Payment Logic
    $payment_method = $data['paymentMethod'];
    $allowed_methods = ['card', 'upi', 'cod'];
    
    if (!in_array($payment_method, $allowed_methods)) {
        throw new Exception("Invalid payment method");
    }
    
    if ($payment_method === 'cod' && $has_digital_items) {
        throw new Exception("Cash on Delivery is not available for digital products.");
    }

    // 6. Create Order
    // Handle savedAddressId - fetch from database if provided
    $shipping_data = $data['shipping'];

    if (!empty($data['savedAddressId'])) {
        $savedAddrId = intval($data['savedAddressId']);

        // Fetch saved address with ownership verification (IDOR prevention)
        $addrStmt = $conn->prepare("SELECT * FROM addresses WHERE id = ? AND user_id = ?");
        $addrStmt->bind_param("ii", $savedAddrId, $user_id);
        $addrStmt->execute();
        $savedAddr = $addrStmt->get_result()->fetch_assoc();
        $addrStmt->close();

        if (!$savedAddr) {
            throw new Exception("Selected address not found or access denied");
        }

        // Build shipping data from saved address
        // Map database columns to the JSON structure expected by the system
        $shipping_data = [
            'firstName' => $savedAddr['first_name'],
            'lastName' => $savedAddr['last_name'],
            'email' => $data['shipping']['email'] ?? '', // Email comes from form (not stored in address)
            'phone' => $savedAddr['phone'],
            'address' => $savedAddr['address_line1'],
            'address2' => $savedAddr['address_line2'] ?? '',
            'city' => $savedAddr['city'],
            'state' => $savedAddr['state'], 
            'zip' => $savedAddr['zip_code'],
            'country' => $savedAddr['country']
        ];
    }

    $order_number = 'UXP-' . date('Y') . '-' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
    // COD → pending (fulfillment); card/UPI → awaiting_payment until Razorpay marks paid
    $status = ($payment_method === 'cod') ? 'pending' : 'awaiting_payment';
    $shipping_address_json = json_encode($shipping_data);
    
    $stmt = $conn->prepare("INSERT INTO orders (order_number, user_id, total, subtotal, shipping, tax, payment_method, status, shipping_address, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("siddddsss", 
        $order_number, 
        $user_id, 
        $calculated_total, 
        $calculated_subtotal, 
        $shipping_cost, 
        $tax, 
        $payment_method, 
        $status, 
        $shipping_address_json
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Error creating order. Please try again.");
    }
    
    $order_id = $conn->insert_id;
    $stmt->close();

    // 7. Insert Items & Remove from Cart
    $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, size, product_name, product_image) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    // Check for size matching (handling empty/null)
    $delete_cart = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ? AND (size = ? OR size = '' OR size IS NULL) AND available_type = ?");
    
    foreach ($order_items_data as $item) {
        // Insert into order_items
        $stmt_item->bind_param("iiidsss",
            $order_id,
            $item['product_id'],
            $item['quantity'],
            $item['price'],
            $item['size'],
            $item['product_name'],
            $item['product_image']
        );
        if (!$stmt_item->execute()) {
            throw new Exception("Error inserting order items");
        }
        
        // Remove from cart
        if ($delete_cart) {
             $p_type = isset($item['type']) ? $item['type'] : 'physical';
             $delete_cart->bind_param("iiss", $user_id, $item['product_id'], $item['size'], $p_type);
             $delete_cart->execute();
        }
    }
    $stmt_item->close();
    if ($delete_cart) $delete_cart->close();

    // 8. Handle "Save Address" (Optional feature from checkout)
    // FIXED: Use correct column names (address_line1, zip_code instead of address, zip)
    if (!empty($data['saveAddress']) && !empty($data['shipping'])) {
        $ship = $data['shipping'];
        // Validate that we have actual address data (not when using savedAddressId)
        if (!empty($ship['address']) && empty($data['savedAddressId'])) {
            // Check how many addresses user has (limit to 10)
            $countStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM addresses WHERE user_id = ?");
            $countStmt->bind_param("i", $user_id);
            $countStmt->execute();
            $countRow = $countStmt->get_result()->fetch_assoc();
            $countStmt->close();

            if ($countRow['cnt'] < 10) {
                // Check if this is user's first address (auto-set as default)
                $isDefault = ($countRow['cnt'] == 0) ? 1 : 0;

                // Sanitize inputs
                $addrFirstName = htmlspecialchars(trim($ship['firstName'] ?? ''), ENT_QUOTES, 'UTF-8');
                $addrLastName = htmlspecialchars(trim($ship['lastName'] ?? ''), ENT_QUOTES, 'UTF-8');
                $addrLine1 = htmlspecialchars(trim($ship['address'] ?? ''), ENT_QUOTES, 'UTF-8');
                $addrLine2 = htmlspecialchars(trim($ship['address2'] ?? ''), ENT_QUOTES, 'UTF-8');
                $addrCity = htmlspecialchars(trim($ship['city'] ?? ''), ENT_QUOTES, 'UTF-8');
                $addrState = htmlspecialchars(trim($ship['state'] ?? ''), ENT_QUOTES, 'UTF-8');
                $addrZipCode = htmlspecialchars(trim($ship['zip'] ?? ''), ENT_QUOTES, 'UTF-8');
                $addrCountry = htmlspecialchars(trim($ship['country'] ?? 'IN'), ENT_QUOTES, 'UTF-8');
                $addrPhone = preg_replace('/[^\d+\-\s()]/', '', $ship['phone'] ?? '');

                // FIXED: Use correct column names matching the addresses table schema
                $saveAddrStmt = $conn->prepare("INSERT INTO addresses
                    (user_id, first_name, last_name, address_line1, address_line2, city, state, zip_code, country, phone, is_default, label)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Checkout')");
                if ($saveAddrStmt) {
                    $saveAddrStmt->bind_param("issssssssis",
                        $user_id,
                        $addrFirstName,
                        $addrLastName,
                        $addrLine1,      // Maps to address_line1 (NOT address)
                        $addrLine2,      // Maps to address_line2
                        $addrCity,
                        $addrState,
                        $addrZipCode,    // Maps to zip_code (NOT zip)
                        $addrCountry,
                        $addrPhone,
                        $isDefault
                    );
                    $saveAddrStmt->execute();
                    $saveAddrStmt->close();
                }
            }
        }
    }
    
    $conn->commit();
    
    // Send order confirmation email
    $userStmt = $conn->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
    $userStmt->bind_param("i", $user_id);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $userData = $userResult->fetch_assoc();
    $userStmt->close();
    
    if ($userData) {
        $userName = $userData['first_name'] . ' ' . $userData['last_name'];
        $userEmail = $userData['email'];
        
        $orderData = [
            'order_id' => $order_id,
            'order_number' => $order_number,
            'date' => date('Y-m-d'),
            'items' => array_map(function($item) {
                return [
                    'name' => $item['product_name'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity']
                ];
            }, $order_items_data),
            'total' => $calculated_total,
            'subtotal' => $calculated_subtotal,
            'tax' => $tax,
            'shipping' => $shipping_cost
        ];
        
        sendOrderConfirmationEmail($userEmail, $userName, $orderData);
    }
    
    sendResponse('success', 'Order placed successfully', [
        'orderNumber'   => $order_number,
        'orderId'       => $order_id,
        'total'         => $calculated_total,
        'subtotal'      => $calculated_subtotal,
        'tax'           => $tax,
        'shipping_cost' => $shipping_cost,
        'status'        => $status,
    ]);
    
} catch (InvalidArgumentException $e) {
    $conn->rollback();
    sendResponse('error', $e->getMessage(), null, 400);
} catch (Exception $e) {
    $conn->rollback();
    error_log('order/create failed: ' . $e->getMessage());
    sendResponse('error', 'Failed to create order', null, 500);
}

$conn->close();
?>
