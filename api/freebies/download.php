<?php
/**
 * Legacy direct freebie download — disabled for security.
 * Freebies must be claimed via checkout (₹0) and downloaded from My Orders
 * using the same encrypted token + signed URL flow as paid products.
 */
require_once __DIR__ . '/../../includes/config.php';

http_response_code(410);
header('Content-Type: application/json');
echo json_encode([
    'status'  => 'error',
    'message' => 'Direct downloads are disabled. Sign in, add the freebie to cart, complete checkout, then download from My Orders.',
]);
exit;
