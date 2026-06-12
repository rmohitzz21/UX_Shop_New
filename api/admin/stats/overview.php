<?php
require_once __DIR__ . '/../_admin.php';
require_once __DIR__ . '/_helpers.php';

$revenueStatuses = adminStatsRevenueStatuses();
$pendingStatuses = adminStatsPendingStatuses();
$revIn           = adminStatsInClause($revenueStatuses);
$revTypes        = str_repeat('s', count($revenueStatuses));
$pendIn          = adminStatsInClause($pendingStatuses);
$pendTypes       = str_repeat('s', count($pendingStatuses));

$thisMonth    = date('Y-m-01 00:00:00');
$todayStart   = date('Y-m-d 00:00:00');
$lastMonthEnd = date('Y-m-t 23:59:59', strtotime('last month'));
$lastMonth    = date('Y-m-01 00:00:00', strtotime('first day of last month'));

// ── Batched catalog + user counts (1 round trip) ─────────────────────────────
$catalogCounts = adminStatsRows(
    $conn,
    "SELECT
        (SELECT COUNT(*) FROM users WHERE role = 'customer') AS users_total,
        (SELECT COUNT(*) FROM users WHERE role = 'customer' AND created_at >= ?) AS users_month,
        (SELECT COUNT(*) FROM users WHERE role = 'customer' AND created_at BETWEEN ? AND ?) AS users_last,
        (SELECT COUNT(*) FROM products WHERE is_active = 1) AS products_active,
        (SELECT COUNT(*) FROM products) AS products_all,
        (SELECT COUNT(*) FROM products WHERE created_at >= ?) AS products_added_month,
        (SELECT COUNT(*) FROM bundles) AS bundles_total,
        (SELECT COUNT(*) FROM bundles WHERE is_active = 1) AS bundles_active,
        (SELECT COUNT(*) FROM categories) AS categories_total,
        (SELECT COUNT(*) FROM categories WHERE is_active = 1) AS categories_active",
    'ssss',
    [$thisMonth, $lastMonth, $lastMonthEnd, $thisMonth]
);
$counts = $catalogCounts[0] ?? [];

$usersMonth  = (float) ($counts['users_month'] ?? 0);
$usersLast   = (float) ($counts['users_last'] ?? 0);
$ordersMonth = 0.0;
$ordersLast  = 0.0;
$addedThisMonth = (int) ($counts['products_added_month'] ?? 0);

// ── Batched order metrics (1 round trip) ─────────────────────────────────────
$orderMetrics = adminStatsRows(
    $conn,
    "SELECT
        COALESCE(SUM(CASE WHEN status IN ({$revIn}) THEN total ELSE 0 END), 0) AS revenue_total,
        COALESCE(SUM(CASE WHEN status IN ({$revIn}) AND created_at >= ? THEN total ELSE 0 END), 0) AS revenue_today,
        COALESCE(SUM(CASE WHEN status IN ({$revIn}) AND created_at >= ? THEN total ELSE 0 END), 0) AS revenue_month,
        COALESCE(SUM(CASE WHEN status IN ({$revIn}) AND created_at BETWEEN ? AND ? THEN total ELSE 0 END), 0) AS revenue_last,
        COUNT(CASE WHEN status IN ({$revIn}) THEN 1 END) AS paid_order_count,
        COUNT(*) AS orders_total,
        COUNT(CASE WHEN created_at >= ? THEN 1 END) AS orders_month,
        COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) AS orders_last,
        COUNT(CASE WHEN status IN ({$pendIn}) THEN 1 END) AS orders_pending",
    $revTypes . 'sssssss' . $pendTypes,
    [
        ...$revenueStatuses,
        $todayStart,
        $thisMonth,
        $lastMonth,
        $lastMonthEnd,
        $thisMonth,
        $lastMonth,
        $lastMonthEnd,
        ...$pendingStatuses,
    ]
);
$orders = $orderMetrics[0] ?? [];

$revenueTotal    = (float) ($orders['revenue_total'] ?? 0);
$revenueToday    = (float) ($orders['revenue_today'] ?? 0);
$revenueMonth    = (float) ($orders['revenue_month'] ?? 0);
$revenueLast     = (float) ($orders['revenue_last'] ?? 0);
$paidOrderCount  = (int) ($orders['paid_order_count'] ?? 0);
$ordersMonth     = (float) ($orders['orders_month'] ?? 0);
$ordersLast      = (float) ($orders['orders_last'] ?? 0);

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

$recentOrders = adminStatsRows($conn, "
    SELECT o.id, o.order_number, o.total, o.status, o.created_at,
           u.first_name, u.last_name, u.email
    FROM orders o
    LEFT JOIN users u ON u.id = o.user_id
    ORDER BY o.created_at DESC, o.id DESC
    LIMIT 6");

$lowStock = adminStatsRows($conn, "
    SELECT 'product' AS item_type, id, name, stock, image FROM products WHERE is_active = 1 AND stock <= 5
    UNION ALL
    SELECT 'bundle' AS item_type, id, name, stock, image FROM bundles WHERE is_active = 1 AND stock <= 5
    ORDER BY stock ASC
    LIMIT 10");

$recentActivity = adminStatsRows($conn, "
    SELECT item_type, item_id, change_qty, stock_before, stock_after, reason, created_at
    FROM inventory_movements
    ORDER BY created_at DESC, id DESC
    LIMIT 10");

$unreadMessages = adminStatsScalar(
    $conn,
    'SELECT COUNT(*) FROM contact_messages WHERE COALESCE(archived, 0) = 0 AND COALESCE(is_read, 0) = 0'
);

sendResponse('success', 'Dashboard stats loaded.', [
    'users' => [
        'total'  => (int) ($counts['users_total'] ?? 0),
        'change' => adminStatsFormatChange($usersMonth, $usersLast),
    ],
    'products' => [
        'total'  => (int) ($counts['products_active'] ?? 0),
        'all'    => (int) ($counts['products_all'] ?? 0),
        'change' => '+' . $addedThisMonth . ' added this month',
    ],
    'bundles' => [
        'total'  => (int) ($counts['bundles_total'] ?? 0),
        'active' => (int) ($counts['bundles_active'] ?? 0),
    ],
    'categories' => [
        'total'  => (int) ($counts['categories_total'] ?? 0),
        'active' => (int) ($counts['categories_active'] ?? 0),
    ],
    'orders' => [
        'total'   => (int) ($orders['orders_total'] ?? 0),
        'pending' => (int) ($orders['orders_pending'] ?? 0),
        'change'  => adminStatsFormatChange($ordersMonth, $ordersLast),
    ],
    'revenue' => [
        'total'     => $revenueTotal,
        'today'     => $revenueToday,
        'month'     => $revenueMonth,
        'change'    => adminStatsFormatChange($revenueMonth, $revenueLast),
        'avg_order' => $paidOrderCount > 0 ? round($revenueTotal / $paidOrderCount, 2) : 0,
    ],
    'messages' => [
        'unread' => $unreadMessages,
    ],
    'inventory' => [
        'low_stock_count' => count($lowStock),
        'low_stock'       => $lowStock,
        'recent_activity' => $recentActivity,
    ],
    'recent_orders' => $recentOrders,
    'top_products'  => $topProducts,
]);
