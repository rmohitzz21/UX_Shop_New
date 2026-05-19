<?php
/**
 * Razorpay REST API + signature helpers (checkout + webhook).
 * No secrets in responses; failures logged server-side only.
 */

declare(strict_types=1);

/**
 * GET https://api.razorpay.com/v1/...
 */
function rzp_api_get(string $path): array
{
    $keyId     = getenv('RAZORPAY_KEY_ID') ?: '';
    $keySecret = getenv('RAZORPAY_KEY_SECRET') ?: '';
    if ($keyId === '' || $keySecret === '' || str_contains($keyId, 'REPLACE_ME')) {
        return ['ok' => false, 'http' => 503, 'error' => 'Payment system not configured'];
    }

    $url = 'https://api.razorpay.com/v1' . $path;
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPGET        => true,
        CURLOPT_USERPWD        => "{$keyId}:{$keySecret}",
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 20,
    ]);
    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr !== '') {
        error_log('Razorpay GET curl error: ' . $curlErr);
        return ['ok' => false, 'http' => 503, 'error' => 'Gateway unreachable'];
    }

    $json = json_decode((string) $response, true);
    if ($httpCode >= 400) {
        $msg = $json['error']['description'] ?? 'Razorpay API error';
        error_log("Razorpay GET {$path} HTTP {$httpCode}: {$response}");
        return ['ok' => false, 'http' => $httpCode >= 500 ? 502 : 400, 'error' => $msg];
    }

    return ['ok' => true, 'data' => $json];
}

/**
 * POST JSON body to Razorpay API.
 */
function rzp_api_post(string $path, array $payload): array
{
    $keyId     = getenv('RAZORPAY_KEY_ID') ?: '';
    $keySecret = getenv('RAZORPAY_KEY_SECRET') ?: '';
    if ($keyId === '' || $keySecret === '' || str_contains($keyId, 'REPLACE_ME')) {
        return ['ok' => false, 'http' => 503, 'error' => 'Payment system not configured'];
    }

    $url  = 'https://api.razorpay.com/v1' . $path;
    $body = json_encode($payload);
    $ch   = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_USERPWD        => "{$keyId}:{$keySecret}",
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 20,
    ]);
    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr !== '') {
        error_log('Razorpay POST curl error: ' . $curlErr);
        return ['ok' => false, 'http' => 503, 'error' => 'Gateway unreachable'];
    }

    $json = json_decode((string) $response, true);
    if ($httpCode !== 200 || empty($json['id'])) {
        $msg = $json['error']['description'] ?? 'Failed to create Razorpay order';
        error_log("Razorpay POST {$path} HTTP {$httpCode}: {$response}");
        return ['ok' => false, 'http' => $httpCode >= 500 ? 502 : 400, 'error' => $msg];
    }

    return ['ok' => true, 'data' => $json];
}

/**
 * Fetch payment entity by id (validates amount/currency server-side).
 */
function rzp_fetch_payment(string $paymentId): array
{
    $paymentId = trim($paymentId);
    if ($paymentId === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $paymentId)) {
        return ['ok' => false, 'http' => 400, 'error' => 'Invalid payment id'];
    }
    return rzp_api_get('/payments/' . rawurlencode($paymentId));
}

/**
 * Checkout signature: HMAC-SHA256(order_id + "|" + payment_id, key_secret)
 */
function rzp_verify_checkout_signature(string $rzpOrderId, string $rzpPaymentId, string $clientSignature, string $keySecret): bool
{
    if ($keySecret === '' || $clientSignature === '') {
        return false;
    }
    $expected = hash_hmac('sha256', $rzpOrderId . '|' . $rzpPaymentId, $keySecret);
    return hash_equals($expected, $clientSignature);
}

/**
 * Webhook: HMAC-SHA256(raw_body, webhook_secret) compare to X-Razorpay-Signature
 */
function rzp_verify_webhook_signature(string $rawBody, string $signatureHeader, string $webhookSecret): bool
{
    if ($webhookSecret === '' || $signatureHeader === '') {
        return false;
    }
    $expected = hash_hmac('sha256', $rawBody, $webhookSecret);
    return hash_equals($expected, $signatureHeader);
}

/**
 * Expected amount in paise from order total (INR).
 */
function rzp_order_total_to_paise(float $orderTotal): int
{
    return (int) round($orderTotal * 100);
}
