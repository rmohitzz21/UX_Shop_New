<?php
require_once __DIR__ . '/../_admin.php';

// ── Helper: scalar from prepared statement ───────────────────────────────────
function adminStatScalar(mysqli $conn, string $sql, string $types = '', array $params = [], string $cast = 'int') {
    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $row   = $stmt->get_result()->fetch_row();
    $value = $row[0] ?? 0;
    return $cast === 'float' ? (float) $value : (int) $value;
}

// ── Helper: rows from prepared statement ─────────────────────────────────────
function adminStatRows(mysqli $conn, string $sql, string $types = '', array $params = []): array {
    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $rows = [];
    $res  = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

$thisMonth    = date('Y-m-01 00:00:00');
$lastMonthEnd = date('Y-m-t 23:59:59', strtotime('last month'));
$lastMonth    = date('Y-m-01 00:00:00', strtotime('first day of last month'));

$paidStatusList = ['Processing', 'Shipped', 'Delivered', 'Paid', 'paid', 'processing', 'shipped', 'delivered'];
$placeholders   = implode(',', array_fill(0, count($paidStatusList), '?'));
$paidTypes      = str_repeat('s', count($paidStatusList));

$revenueTotal = adminStatScalar($conn,
    "SELECT COALESCE(SUM(total),0) FROM orders WHERE status IN ({$placeholders})",
    $paidTypes, $paidStatusList, 'float');

$revenueMonthParams = [...$paidStatusList, $thisMonth];
$revenueMonth = adminStatScalar($conn,
    "SELECT COALESCE(SUM(total),0) FROM orders WHERE status IN ({$placeholders}) AND created_at >= ?",
    $paidTypes . 's', $revenueMonthParams, 'float');

$revenueLastParams = [...$paidStatusList, $lastMonth, $lastMonthEnd];
$revenueLast = adminStatScalar($conn,
    "SELECT COALESCE(SUM(total),0) FROM orders WHERE status IN ({$placeholders}) AND created_at BETWEEN ? AND ?",
    $paidTypes . 'ss', $revenueLastParams, 'float');

$ordersMonth = adminStatScalar($conn, 'SELECT COUNT(*) FROM orders WHERE created_at >= ?', 's', [$thisMonth]);
$ordersLast  = adminStatScalar($conn, 'SELECT COUNT(*) FROM orders WHERE created_at BETWEEN ? AND ?', 'ss', [$lastMonth, $lastMonthEnd]);
$usersMonth  = adminStatScalar($conn, "SELECT COUNT(*) FROM users WHERE role = 'customer' AND created_at >= ?", 's', [$thisMonth]);
$usersLast   = adminStatScalar($conn, "SELECT COUNT(*) FROM users WHERE role = 'customer' AND created_at BETWEEN ? AND ?", 'ss', [$lastMonth, $lastMonthEnd]);

$formatChange = static function ($current, $previous, string $label = ''): string {
    if ((float) $previous === 0.0) {
        return ((float) $current === 0.0) ? 'No change' : '+' . number_format((float) $current) . ($label ? " {$label}" : '') . ' this month';
    }
    $pct = (($current - $previous) / $previous) * 100;
    return ($pct >= 0 ? '+' : '') . round($pct, 1) . '% vs last month';
};

$topProducts = adminStatRows($conn, "
    SELECT COALESCE(p.id, oi.product_id) AS id,
           COALESCE(NULLIF(oi.product_name,''), p.name, 'Catalog item') AS name,
           COALESCE(p.category, oi.item_type, 'Products') AS category,
           SUM(oi.quantity) AS units_sold,
           SUM(oi.quantity * oi.price) AS revenue
    FROM order_items oi
    LEFT JOIN products p ON p.id = oi.product_id AND oi.item_type = 'product'
    GROUP BY COALESCE(p.id, oi.product_id),
             COALESCE(NULLIF(oi.product_name,''), p.name, 'Catalog item'),
             COALESCE(p.category, oi.item_type, 'Products')
    ORDER BY units_sold DESC, revenue DESC
    LIMIT 8");

$recentOrders = adminStatRows($conn, "
    SELECT o.id, o.order_number, o.total, o.status, o.created_at,
           u.first_name, u.last_name, u.email
    FROM orders o
    LEFT JOIN users u ON u.id = o.user_id
    ORDER BY o.created_at DESC, o.id DESC
    LIMIT 6");

$lowStock = adminStatRows($conn, "
    SELECT 'product' AS item_type, id, name, stock, image FROM products WHERE is_active = 1 AND stock <= 5
    UNION ALL
    SELECT 'bundle'  AS item_type, id, name, stock, image FROM bundles  WHERE is_active = 1 AND stock <= 5
    ORDER BY stock ASC LIMIT 10");

$recentActivity = adminStatRows($conn, "
    SELECT item_type, item_id, change_qty, stock_before, stock_after, reason, created_at
    FROM inventory_movements
    ORDER BY created_at DESC, id DESC LIMIT 10");

$addedThisMonth = adminStatScalar($conn, 'SELECT COUNT(*) FROM products WHERE created_at >= ?', 's', [$thisMonth]);

sendResponse('success', 'Dashboard stats loaded.', [
    'users' => [
        'total'  => adminStatScalar($conn, "SELECT COUNT(*) FROM users WHERE role = 'customer'"),
        'change' => $formatChange($usersMonth, $usersLast),
    ],
    'products' => [
        'total'  => adminStatScalar($conn, 'SELECT COUNT(*) FROM products'),
        'active' => adminStatScalar($conn, 'SELECT COUNT(*) FROM products WHERE is_active = 1'),
        'change' => '+' . $addedThisMonth . ' added this month',
    ],
    'bundles' => [
        'total'  => adminStatScalar($conn, 'SELECT COUNT(*) FROM bundles'),
        'active' => adminStatScalar($conn, 'SELECT COUNT(*) FROM bundles WHERE is_active = 1'),
    ],
    'categories' => [
        'total'  => adminStatScalar($conn, 'SELECT COUNT(*) FROM categories'),
        'active' => adminStatScalar($conn, 'SELECT COUNT(*) FROM categories WHERE is_active = 1'),
    ],
    'orders' => [
        'total'   => adminStatScalar($conn, 'SELECT COUNT(*) FROM orders'),
        'pending' => adminStatScalar($conn, "SELECT COUNT(*) FROM orders WHERE status IN ('Pending','pending','awaiting_payment','Awaiting Payment')"),
        'change'  => $formatChange($ordersMonth, $ordersLast),
    ],
    'revenue' => [
        'total'  => $revenueTotal,
        'month'  => $revenueMonth,
        'change' => $formatChange($revenueMonth, $revenueLast),
    ],
    'inventory' => [
        'low_stock_count' => count($lowStock),
        'low_stock'       => $lowStock,
        'recent_activity' => $recentActivity,
    ],
    'recent_orders' => $recentOrders,
    'top_products'  => $topProducts,
]);
