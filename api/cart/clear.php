<?php
require_once __DIR__ . '/../_bootstrap.php';

apiRequirePost();
$user = apiRequireUser();
validateCsrf();

$stmt = $conn->prepare('DELETE FROM cart WHERE user_id = ?');
$stmt->bind_param('i', $user['id']);
if (!$stmt->execute()) {
    sendResponse('error', 'Could not clear cart.', null, 500);
}

sendResponse('success', 'Cart cleared.');
