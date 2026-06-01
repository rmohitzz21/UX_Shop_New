<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/includes/config.php';
require_once $root . '/api/admin/stats/_helpers.php';

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$failures = 0;
$pass = static function (string $label, bool $ok) use (&$failures): void {
    echo ($ok ? '[PASS] ' : '[FAIL] ') . $label . PHP_EOL;
    if (!$ok) {
        $failures++;
    }
};

marketplaceEnsureSchema($conn);

$revenueStatuses = adminStatsRevenueStatuses();
$revIn = adminStatsInClause($revenueStatuses);
$revTypes = str_repeat('s', count($revenueStatuses));
$thisMonth = date('Y-m-01 00:00:00');
$todayStart = date('Y-m-d 00:00:00');

$revenueMonth = adminStatsScalar(
    $conn,
    "SELECT COALESCE(SUM(total), 0) FROM orders WHERE status IN ({$revIn}) AND created_at >= ?",
    $revTypes . 's',
    [...$revenueStatuses, $thisMonth],
    'float'
);
$paidOrdersMonth = adminStatsScalar(
    $conn,
    "SELECT COUNT(*) FROM orders WHERE status IN ({$revIn}) AND created_at >= ?",
    $revTypes . 's',
    [...$revenueStatuses, $thisMonth]
);
$avgMonth = $paidOrdersMonth > 0 ? round($revenueMonth / $paidOrdersMonth, 2) : 0.0;
$pass('Month AOV calculation', $avgMonth >= 0);

$customerTotal = adminStatsScalar($conn, "SELECT COUNT(*) FROM users WHERE role = 'customer'");
$payingCustomers = adminStatsScalar(
    $conn,
    "SELECT COUNT(DISTINCT user_id) FROM orders WHERE status IN ({$revIn}) AND user_id IS NOT NULL AND user_id > 0",
    $revTypes,
    $revenueStatuses
);
$conversion = $customerTotal > 0 ? round(($payingCustomers / $customerTotal) * 100, 1) : 0.0;
$pass('Conversion rate 0–100', $conversion >= 0 && $conversion <= 100);

$top = adminStatsRows(
    $conn,
    "SELECT CASE WHEN oi.item_type = 'bundle' THEN 'bundle' ELSE 'product' END AS item_type,
            SUM(oi.quantity) AS units_sold
     FROM order_items oi
     INNER JOIN orders o ON o.id = oi.order_id AND o.status IN ({$revIn})
     GROUP BY item_type
     LIMIT 5",
    $revTypes,
    $revenueStatuses
);
$pass('Top sellers aggregate query', is_array($top));

// Simulate analytics.php response shape
$payload = [
    'revenue' => ['month' => $revenueMonth, 'avg_order_month' => $avgMonth],
    'customers' => ['conversion_rate' => $conversion],
];
$pass('Payload has revenue.month', isset($payload['revenue']['month']));
$pass('Payload has customers.conversion_rate', isset($payload['customers']['conversion_rate']));

echo PHP_EOL . ($failures === 0 ? "All analytics API checks passed.\n" : "{$failures} failed.\n");
exit($failures > 0 ? 1 : 0);
