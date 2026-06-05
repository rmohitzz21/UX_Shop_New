<?php
require_once __DIR__ . '/../includes/env.php';
require_once __DIR__ . '/../includes/RazorpayClient.php';

$orderId = $argv[1] ?? 'order_SxpX35aWw7WYh7';
$result = rzp_api_get('/orders/' . $orderId);
echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
