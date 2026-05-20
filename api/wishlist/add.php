<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/_table.php';

$user = apiRequireUser();
$input = apiInput();
validateCsrf();

$productId = apiEnsureProduct($conn, $input);
$stmt = $conn->prepare('INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?, ?)');
$stmt->bind_param('ii', $user['id'], $productId);
$stmt->execute();

sendResponse('success', 'Added to wishlist.', ['product_id' => $productId]);
