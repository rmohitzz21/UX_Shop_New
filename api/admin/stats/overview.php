<?php
require_once __DIR__ . '/../_admin.php';

function adminScalar(mysqli $conn, string $sql, string $type = 'int') {
    $res = $conn->query($sql);
    $row = $res ? $res->fetch_row() : [0];
    $value = $row[0] ?? 0;
    return $type === 'float' ? (float) $value : (int) $value;
}

function adminRows(mysqli $conn, string $sql): array {
    $rows = [];
    $res = $conn->query($sql);
    while ($res && $row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

$thisMonth = date('Y-m-01 00:00:00');
$lastMonth = date('Y-m-01 00:00:00', strtotime('first day of last month'));
$lastMonthEnd = date('Y-m-t 23:59:59', strtotime('last month'));
$paidStatus = "'Processing','Shipped','Delivered','Paid','paid','processing','shipped','delivered'";

$revenueTotal = adminScalar($conn, "SELECT COALESCE(SUM(total),0) FROM orders WHERE status IN ($paidStatus)", 'float');
$revenueMonth = adminScalar($conn, "SELECT COALESCE(SUM(total),0) FROM orders WHERE status IN ($paidStatus) AND created_at >= '{$conn->real_escape_string($thisMonth)}'", 'float');
$revenueLast = adminScalar($conn, "SELECT COALESCE(SUM(total),0) FROM orders WHERE status IN ($paidStatus) AND created_at BETWEEN '{$conn->real_escape_string($lastMonth)}' AND '{$conn->real_escape_string($lastMonthEnd)}'", 'float');

$ordersMonth = adminScalar($conn, "SELECT COUNT(*) FROM orders WHERE created_at >= '{$conn->real_escape_string($thisMonth)}'");
$ordersLast = adminScalar($conn, "SELECT COUNT(*) FROM orders WHERE created_at BETWEEN '{$conn->real_escape_string($lastMonth)}' AND '{$conn->real_escape_string($lastMonthEnd)}'");
$usersMonth = adminScalar($conn, "SELECT COUNT(*) FROM users WHERE role = 'customer' AND created_at >= '{$conn->real_escape_string($thisMonth)}'");
$usersLast = adminScalar($conn, "SELECT COUNT(*) FROM users WHERE role = 'customer' AND created_at BETWEEN '{$conn->real_escape_string($lastMonth)}' AND '{$conn->real_escape_string($lastMonthEnd)}'");

$formatChange = static function ($current, $previous, string $label = ''): string {
    if ((float) $previous === 0.0) {
        return ((float) $current === 0.0) ? 'No change' : '+' . number_format((float) $current) . ($label ? " {$label}" : '') . ' this month';
    }
    $pct = (($current - $previous) / $previous) * 100;
    return ($pct >= 0 ? '+' : '') . round($pct, 1) . '% vs last month';
};

$topProducts = adminRows($conn, "
    SELECT
        COALESCE(p.id, oi.product_id) AS id,
        COALESCE(NULLIF(oi.product_name, ''), p.name, 'Catalog item') AS name,
        COALESCE(p.category, oi.item_type, 'Products') AS category,
        SUM(oi.quantity) AS units_sold,
        SUM(oi.quantity * oi.price) AS revenue
    FROM order_items oi
    LEFT JOIN products p ON p.id = oi.product_id AND oi.item_type = 'product'
    GROUP BY COALESCE(p.id, oi.product_id), COALESCE(NULLIF(oi.product_name, ''), p.name, 'Catalog item'), COALESCE(p.category, oi.item_type, 'Products')
    ORDER BY units_sold DESC, revenue DESC
    LIMIT 8
");

$recentOrders = adminRows($conn, "
    SELECT o.id, o.order_number, o.total, o.status, o.created_at, u.first_name, u.last_name, u.email
    FROM orders o
    LEFT JOIN users u ON u.id = o.user_id
    ORDER BY o.created_at DESC, o.id DESC
    LIMIT 6
");

$lowStock = adminRows($conn, "
    SELECT 'product' AS item_type, id, name, stock, image
    FROM products
    WHERE is_active = 1 AND stock <= 5
    UNION ALL
    SELECT 'bundle' AS item_type, id, name, stock, image
    FROM bundles
    WHERE is_active = 1 AND stock <= 5
    ORDER BY stock ASC
    LIMIT 10
");

$recentActivity = adminRows($conn, "
    SELECT item_type, item_id, change_qty, stock_before, stock_after, reason, created_at
    FROM inventory_movements
    ORDER BY created_at DESC, id DESC
    LIMIT 10
");

sendResponse('success', 'Dashboard stats loaded.', [
    'users' => [
        'total' => adminScalar($conn, "SELECT COUNT(*) FROM users WHERE role = 'customer'"),
        'change' => $formatChange($usersMonth, $usersLast),
    ],
    'products' => [
        'total' => adminScalar($conn, "SELECT COUNT(*) FROM products"),
        'active' => adminScalar($conn, "SELECT COUNT(*) FROM products WHERE is_active = 1"),
        'change' => '+' . adminScalar($conn, "SELECT COUNT(*) FROM products WHERE created_at >= '{$conn->real_escape_string($thisMonth)}'") . ' added this month',
    ],
    'bundles' => [
        'total' => adminScalar($conn, "SELECT COUNT(*) FROM bundles"),
        'active' => adminScalar($conn, "SELECT COUNT(*) FROM bundles WHERE is_active = 1"),
    ],
    'categories' => [
        'total' => adminScalar($conn, "SELECT COUNT(*) FROM categories"),
        'active' => adminScalar($conn, "SELECT COUNT(*) FROM categories WHERE is_active = 1"),
    ],
    'orders' => [
        'total' => adminScalar($conn, "SELECT COUNT(*) FROM orders"),
        'change' => $formatChange($ordersMonth, $ordersLast),
    ],
    'revenue' => [
        'total' => $revenueTotal,
        'month' => $revenueMonth,
        'change' => $formatChange($revenueMonth, $revenueLast),
    ],
    'inventory' => [
        'low_stock_count' => count($lowStock),
        'low_stock' => $lowStock,
        'recent_activity' => $recentActivity,
    ],
    'recent_orders' => $recentOrders,
    'top_products' => $topProducts,
]);
