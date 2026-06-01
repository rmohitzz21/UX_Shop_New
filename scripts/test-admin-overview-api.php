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

$total = adminStatsScalar(
    $conn,
    "SELECT COALESCE(SUM(total), 0) FROM orders WHERE status IN ({$revIn})",
    $revTypes,
    $revenueStatuses,
    'float'
);
$pass('Revenue total query runs', $total >= 0);

$topProducts = adminStatsRows(
    $conn,
    "SELECT
        CASE WHEN oi.item_type = 'bundle' THEN 'bundle' ELSE 'product' END AS item_type,
        CASE WHEN oi.item_type = 'bundle' THEN COALESCE(b.id, oi.bundle_id) ELSE COALESCE(p.id, oi.product_id) END AS id,
        COALESCE(
            NULLIF(oi.product_name, ''),
            CASE WHEN oi.item_type = 'bundle' THEN b.name ELSE p.name END,
            'Catalog item'
        ) AS name,
        COALESCE(
            CASE WHEN oi.item_type = 'bundle' THEN b.category ELSE p.category END,
            oi.item_type,
            'Products'
        ) AS category,
        SUM(oi.quantity) AS units_sold,
        SUM(oi.quantity * oi.price) AS revenue
     FROM order_items oi
     INNER JOIN orders o ON o.id = oi.order_id AND o.status IN ({$revIn})
     LEFT JOIN products p ON p.id = oi.product_id AND oi.item_type = 'product'
     LEFT JOIN bundles b ON b.id = oi.bundle_id AND oi.item_type = 'bundle'
     GROUP BY item_type, id, name, category
     ORDER BY units_sold DESC, revenue DESC
     LIMIT 8",
    $revTypes,
    $revenueStatuses
);
$pass('Top products query runs', is_array($topProducts));

$recent = adminStatsRows($conn, 'SELECT id FROM orders ORDER BY created_at DESC LIMIT 1');
$pass('Recent orders query runs', is_array($recent));

$pendingStatuses = adminStatsPendingStatuses();
$pendIn = adminStatsInClause($pendingStatuses);
$pendTypes = str_repeat('s', count($pendingStatuses));
$pending = adminStatsScalar(
    $conn,
    "SELECT COUNT(*) FROM orders WHERE status IN ({$pendIn})",
    $pendTypes,
    $pendingStatuses
);
$pass('Pending orders count runs', $pending >= 0);

$change = adminStatsFormatChange(10.0, 5.0);
$pass('Format change percent', str_contains($change, '%'));

$changeZero = adminStatsFormatChange(0.0, 0.0);
$pass('Format change no change', $changeZero === 'No change');

echo PHP_EOL . ($failures === 0 ? "All overview API checks passed.\n" : "{$failures} failed.\n");
exit($failures > 0 ? 1 : 0);
