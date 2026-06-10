<?php
/** CLI smoke checks for audit — not web accessible */
$base = 'http://localhost/Shop/UX_SHOP/UX_Shop_New';
$checks = [];

function hit(string $url, string $method = 'GET', ?string $body = null, array $headers = []): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 15,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hsz = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    return ['code' => $code, 'body' => substr((string) $raw, $hsz)];
}

// Unauthenticated order API → 401 JSON
$r = hit($base . '/api/order/get.php');
$checks['order_get_unauth'] = $r['code'] === 401 && str_contains($r['body'], '"status"');

// Unauthenticated cart list → 401 JSON
$r = hit($base . '/api/cart/list.php');
$checks['cart_list_unauth'] = $r['code'] === 401 && str_contains($r['body'], '"status"');

// test-pay without session → 401
$r = hit($base . '/api/payment/test-pay.php', 'POST', '{}', ['Content-Type: application/json']);
$checks['test_pay_unauth'] = $r['code'] === 401;

// Invalid product → 404 branded page
$r = hit($base . '/product.php?id=999999');
$checks['invalid_product_404'] = $r['code'] === 404 && str_contains($r['body'], '404');

// Homepage loads
$r = hit($base . '/index.php');
$checks['homepage_200'] = $r['code'] === 200 && !str_contains($r['body'], 'Fatal error');

// COD rejected server-side
$r = hit($base . '/api/order/create.php', 'POST', json_encode(['paymentMethod' => 'cod', 'items' => []]), ['Content-Type: application/json']);
$checks['cod_rejected'] = in_array($r['code'], [400, 401, 403], true);

foreach ($checks as $k => $ok) {
    echo ($ok ? 'PASS' : 'FAIL') . " $k\n";
}
exit(array_reduce($checks, fn($c, $v) => $c && $v, true) ? 0 : 1);
