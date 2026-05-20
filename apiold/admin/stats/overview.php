<?php
// api/admin/stats/overview.php
// Returns current-month vs last-month comparison for the admin dashboard stat cards.

header('Content-Type: application/json');
require_once '../../../includes/config.php';
requireAdmin();

$now            = new DateTime();
$thisMonthStart = (clone $now)->modify('first day of this month')->format('Y-m-d 00:00:00');
$lastMonthStart = (clone $now)->modify('first day of last month')->format('Y-m-d 00:00:00');
$lastMonthEnd   = (clone $now)->modify('last day of last month')->format('Y-m-d 23:59:59');

// ── Users ─────────────────────────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM users WHERE role = 'customer'");
$stmt->execute();
$totalUsers = (int)$stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM users WHERE role = 'customer' AND created_at >= ?");
$stmt->bind_param('s', $thisMonthStart);
$stmt->execute();
$usersThisMonth = (int)$stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM users WHERE role = 'customer' AND created_at >= ? AND created_at <= ?");
$stmt->bind_param('ss', $lastMonthStart, $lastMonthEnd);
$stmt->execute();
$usersLastMonth = (int)$stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();

// ── Products ──────────────────────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM products WHERE is_active = 1");
$stmt->execute();
$totalProducts = (int)$stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM products WHERE created_at >= ?");
$stmt->bind_param('s', $thisMonthStart);
$stmt->execute();
$productsThisMonth = (int)$stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM products WHERE created_at >= ? AND created_at <= ?");
$stmt->bind_param('ss', $lastMonthStart, $lastMonthEnd);
$stmt->execute();
$productsLastMonth = (int)$stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();

// ── Orders ────────────────────────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM orders");
$stmt->execute();
$totalOrders = (int)$stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM orders WHERE created_at >= ?");
$stmt->bind_param('s', $thisMonthStart);
$stmt->execute();
$ordersThisMonth = (int)$stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM orders WHERE created_at >= ? AND created_at <= ?");
$stmt->bind_param('ss', $lastMonthStart, $lastMonthEnd);
$stmt->execute();
$ordersLastMonth = (int)$stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();

// ── Revenue ───────────────────────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT COALESCE(SUM(total), 0) AS total FROM orders WHERE status NOT IN ('cancelled','failed','awaiting_payment')");
$stmt->execute();
$totalRevenue = floatval($stmt->get_result()->fetch_assoc()['total']);
$stmt->close();

$stmt = $conn->prepare("SELECT COALESCE(SUM(total), 0) AS amt FROM orders WHERE status NOT IN ('cancelled','failed','awaiting_payment') AND created_at >= ?");
$stmt->bind_param('s', $thisMonthStart);
$stmt->execute();
$revenueThisMonth = floatval($stmt->get_result()->fetch_assoc()['amt']);
$stmt->close();

$stmt = $conn->prepare("SELECT COALESCE(SUM(total), 0) AS amt FROM orders WHERE status NOT IN ('cancelled','failed','awaiting_payment') AND created_at >= ? AND created_at <= ?");
$stmt->bind_param('ss', $lastMonthStart, $lastMonthEnd);
$stmt->execute();
$revenueLastMonth = floatval($stmt->get_result()->fetch_assoc()['amt']);
$stmt->close();

$conn->close();

// ── Helper: format change ─────────────────────────────────────────────────────
function formatChange($current, $last, $prefix = '', $suffix = '') {
    if ($last == 0) {
        if ($current == 0) return 'No change';
        return '+' . $prefix . number_format($current) . $suffix . ' this month';
    }
    $pct = (($current - $last) / $last) * 100;
    $sign = $pct >= 0 ? '+' : '';
    return $sign . round($pct, 1) . '% vs last month';
}

echo json_encode([
    'status' => 'success',
    'data'   => [
        'users'    => [
            'total'  => $totalUsers,
            'change' => formatChange($usersThisMonth, $usersLastMonth),
        ],
        'products' => [
            'total'  => $totalProducts,
            'change' => $productsThisMonth > 0
                ? '+' . $productsThisMonth . ' added this month'
                : 'No new products this month',
        ],
        'orders'   => [
            'total'  => $totalOrders,
            'change' => formatChange($ordersThisMonth, $ordersLastMonth),
        ],
        'revenue'  => [
            'total'  => $totalRevenue,
            'change' => formatChange($revenueThisMonth, $revenueLastMonth, '$'),
        ],
    ]
]);
