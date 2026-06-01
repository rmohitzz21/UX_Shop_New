<?php
/**
 * Analytics tab metrics (paid revenue, conversion, top sellers).
 */
require_once __DIR__ . '/../_admin.php';
require_once __DIR__ . '/_helpers.php';

$revenueStatuses = adminStatsRevenueStatuses();
$revIn           = adminStatsInClause($revenueStatuses);
$revTypes        = str_repeat('s', count($revenueStatuses));

$thisMonth    = date('Y-m-01 00:00:00');
$todayStart   = date('Y-m-d 00:00:00');
$lastMonthEnd = date('Y-m-t 23:59:59', strtotime('last month'));
$lastMonth    = date('Y-m-01 00:00:00', strtotime('first day of last month'));

$revenueToday = adminStatsScalar(
    $conn,
    "SELECT COALESCE(SUM(total), 0) FROM orders WHERE status IN ({$revIn}) AND created_at >= ?",
    $revTypes . 's',
    [...$revenueStatuses, $todayStart],
    'float'
);

$revenueMonth = adminStatsScalar(
    $conn,
    "SELECT COALESCE(SUM(total), 0) FROM orders WHERE status IN ({$revIn}) AND created_at >= ?",
    $revTypes . 's',
    [...$revenueStatuses, $thisMonth],
    'float'
);

$revenueLastMonth = adminStatsScalar(
    $conn,
    "SELECT COALESCE(SUM(total), 0) FROM orders WHERE status IN ({$revIn}) AND created_at BETWEEN ? AND ?",
    $revTypes . 'ss',
    [...$revenueStatuses, $lastMonth, $lastMonthEnd],
    'float'
);

$revenueTotal = adminStatsScalar(
    $conn,
    "SELECT COALESCE(SUM(total), 0) FROM orders WHERE status IN ({$revIn})",
    $revTypes,
    $revenueStatuses,
    'float'
);

$paidOrdersToday = adminStatsScalar(
    $conn,
    "SELECT COUNT(*) FROM orders WHERE status IN ({$revIn}) AND created_at >= ?",
    $revTypes . 's',
    [...$revenueStatuses, $todayStart]
);

$paidOrdersMonth = adminStatsScalar(
    $conn,
    "SELECT COUNT(*) FROM orders WHERE status IN ({$revIn}) AND created_at >= ?",
    $revTypes . 's',
    [...$revenueStatuses, $thisMonth]
);

$paidOrdersTotal = adminStatsScalar(
    $conn,
    "SELECT COUNT(*) FROM orders WHERE status IN ({$revIn})",
    $revTypes,
    $revenueStatuses
);

$ordersMonthAll = adminStatsScalar($conn, 'SELECT COUNT(*) FROM orders WHERE created_at >= ?', 's', [$thisMonth]);

$customerTotal = adminStatsScalar($conn, "SELECT COUNT(*) FROM users WHERE role = 'customer'");

$payingCustomers = adminStatsScalar(
    $conn,
    "SELECT COUNT(DISTINCT user_id) FROM orders WHERE status IN ({$revIn}) AND user_id IS NOT NULL AND user_id > 0",
    $revTypes,
    $revenueStatuses
);

$conversionRate = $customerTotal > 0
    ? round(($payingCustomers / $customerTotal) * 100, 1)
    : 0.0;

$avgOrderMonth = $paidOrdersMonth > 0
    ? round($revenueMonth / $paidOrdersMonth, 2)
    : 0.0;

$avgOrderLifetime = $paidOrdersTotal > 0
    ? round($revenueTotal / $paidOrdersTotal, 2)
    : 0.0;

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
     LIMIT 10",
    $revTypes,
    $revenueStatuses
);

sendResponse('success', 'Analytics loaded.', [
    'revenue' => [
        'today'        => $revenueToday,
        'month'        => $revenueMonth,
        'last_month'   => $revenueLastMonth,
        'total'        => $revenueTotal,
        'change'       => adminStatsFormatChange($revenueMonth, $revenueLastMonth),
        'avg_order_month'    => $avgOrderMonth,
        'avg_order_lifetime' => $avgOrderLifetime,
    ],
    'orders' => [
        'paid_today'     => $paidOrdersToday,
        'paid_month'     => $paidOrdersMonth,
        'paid_total'     => $paidOrdersTotal,
        'all_month'      => $ordersMonthAll,
    ],
    'customers' => [
        'total'           => $customerTotal,
        'with_paid_order' => $payingCustomers,
        'conversion_rate' => $conversionRate,
    ],
    'top_products' => $topProducts,
]);
