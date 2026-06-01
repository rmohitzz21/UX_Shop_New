<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../includes/reviews.php';

apiRequirePost();
validateCsrf();

$user = apiRequireUser();
$input = apiInput();

$productId = isset($input['product_id']) ? (int) $input['product_id'] : 0;
$bundleId  = isset($input['bundle_id']) ? (int) $input['bundle_id'] : 0;
$rating    = isset($input['rating']) ? (int) $input['rating'] : 0;
$comment   = trim((string) ($input['comment'] ?? ''));

if (($productId > 0) === ($bundleId > 0)) {
    sendResponse('error', 'Provide either product_id or bundle_id.', null, 422);
}

if ($rating < 1 || $rating > 5) {
    sendResponse('error', 'Rating must be between 1 and 5.', null, 422);
}

if (strlen($comment) > 2000) {
    sendResponse('error', 'Review comment is too long (max 2000 characters).', null, 422);
}

if ($productId > 0) {
    if (!reviewUserHasPurchasedProduct($conn, $user['id'], $productId)) {
        sendResponse('error', 'You can only review products you have purchased.', null, 403);
    }
    $pStmt = $conn->prepare('SELECT id FROM products WHERE id = ? AND is_active = 1 LIMIT 1');
    $pStmt->bind_param('i', $productId);
    $pStmt->execute();
    if (!$pStmt->get_result()->fetch_assoc()) {
        sendResponse('error', 'Product not found.', null, 404);
    }
    if (reviewFindByUserProduct($conn, $user['id'], $productId)) {
        sendResponse('error', 'You have already reviewed this product.', null, 409);
    }

    $approved = 0;
    $stmt     = $conn->prepare(
        'INSERT INTO reviews (user_id, product_id, bundle_id, rating, comment, is_approved) VALUES (?, ?, NULL, ?, ?, ?)'
    );
    $stmt->bind_param('iiisi', $user['id'], $productId, $rating, $comment, $approved);
} else {
    if (!reviewUserHasPurchasedBundle($conn, $user['id'], $bundleId)) {
        sendResponse('error', 'You can only review bundles you have purchased.', null, 403);
    }
    $bStmt = $conn->prepare('SELECT id FROM bundles WHERE id = ? AND is_active = 1 LIMIT 1');
    $bStmt->bind_param('i', $bundleId);
    $bStmt->execute();
    if (!$bStmt->get_result()->fetch_assoc()) {
        sendResponse('error', 'Bundle not found.', null, 404);
    }
    if (reviewFindByUserBundle($conn, $user['id'], $bundleId)) {
        sendResponse('error', 'You have already reviewed this bundle.', null, 409);
    }

    $approved = 0;
    $stmt     = $conn->prepare(
        'INSERT INTO reviews (user_id, product_id, bundle_id, rating, comment, is_approved) VALUES (?, NULL, ?, ?, ?, ?)'
    );
    $stmt->bind_param('iiisi', $user['id'], $bundleId, $rating, $comment, $approved);
}

if (!$stmt->execute()) {
    error_log('api/reviews/submit.php: ' . $conn->error);
    sendResponse('error', 'Could not save your review. Please try again.', null, 500);
}

sendResponse('success', 'Thank you! Your review was submitted and will appear after approval.', [
    'review_id' => (int) $conn->insert_id,
    'is_approved' => 0,
]);
