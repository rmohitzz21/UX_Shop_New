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

$revenueTotal = adminStatsScalar(
    $conn,
    "SELECT COALESCE(SUM(total), 0) FROM orders WHERE status IN ({$revIn})",
    $revTypes,
    $revenueStatuses,
    'float'
);

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

$revenueLast = adminStatsScalar(
    $conn,
    "SELECT COALESCE(SUM(total), 0) FROM orders WHERE status IN ({$revIn}) AND created_at BETWEEN ? AND ?",
    $revTypes . 'ss',
    [...$revenueStatuses, $lastMonth, $lastMonthEnd],
    'float'
);

$ordersMonth = adminStatsScalar($conn, 'SELECT COUNT(*) FROM orders WHERE created_at >= ?', 's', [$thisMonth]);
$ordersLast  = adminStatsScalar($conn, 'SELECT COUNT(*) FROM orders WHERE created_at BETWEEN ? AND ?', 'ss', [$lastMonth, $lastMonthEnd]);
$usersMonth  = adminStatsScalar($conn, "SELECT COUNT(*) FROM users WHERE role = 'customer' AND created_at >= ?", 's', [$thisMonth]);
$usersLast   = adminStatsScalar($conn, "SELECT COUNT(*) FROM users WHERE role = 'customer' AND created_at BETWEEN ? AND ?", 'ss', [$lastMonth, $lastMonthEnd]);

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

$addedThisMonth = adminStatsScalar($conn, 'SELECT COUNT(*) FROM products WHERE created_at >= ?', 's', [$thisMonth]);

$unreadMessages = 0;
if (tableExists($conn, 'contact_messages') && columnExists($conn, 'contact_messages', 'is_read')) {
    $unreadWhere = 'archived = 0 AND is_read = 0';
    if (!columnExists($conn, 'contact_messages', 'archived')) {
        $unreadWhere = 'is_read = 0';
    }
    $unreadMessages = adminStatsScalar($conn, "SELECT COUNT(*) FROM contact_messages WHERE {$unreadWhere}");
}

$paidOrderCount = adminStatsScalar(
    $conn,
    "SELECT COUNT(*) FROM orders WHERE status IN ({$revIn})",
    $revTypes,
    $revenueStatuses
);

sendResponse('success', 'Dashboard stats loaded.', [
    'users' => [
        'total'  => adminStatsScalar($conn, "SELECT COUNT(*) FROM users WHERE role = 'customer'"),
        'change' => adminStatsFormatChange((float) $usersMonth, (float) $usersLast),
    ],
    'products' => [
        'total'  => adminStatsScalar($conn, 'SELECT COUNT(*) FROM products WHERE is_active = 1'),
        'all'    => adminStatsScalar($conn, 'SELECT COUNT(*) FROM products'),
        'change' => '+' . $addedThisMonth . ' added this month',
    ],
    'bundles' => [
        'total'  => adminStatsScalar($conn, 'SELECT COUNT(*) FROM bundles'),
        'active' => adminStatsScalar($conn, 'SELECT COUNT(*) FROM bundles WHERE is_active = 1'),
    ],
    'categories' => [
        'total'  => adminStatsScalar($conn, 'SELECT COUNT(*) FROM categories'),
        'active' => adminStatsScalar($conn, 'SELECT COUNT(*) FROM categories WHERE is_active = 1'),
    ],
    'orders' => [
        'total'   => adminStatsScalar($conn, 'SELECT COUNT(*) FROM orders'),
        'pending' => adminStatsScalar(
            $conn,
            "SELECT COUNT(*) FROM orders WHERE status IN ({$pendIn})",
            $pendTypes,
            $pendingStatuses
        ),
        'change'  => adminStatsFormatChange((float) $ordersMonth, (float) $ordersLast),
    ],
    'revenue' => [
        'total'  => $revenueTotal,
        'today'  => $revenueToday,
        'month'  => $revenueMonth,
        'change' => adminStatsFormatChange($revenueMonth, $revenueLast),
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
