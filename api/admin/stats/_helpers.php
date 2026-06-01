<?php

/** Order statuses that count toward revenue and sold-units metrics. */
function adminStatsRevenueStatuses(): array
{
    return ['paid', 'processing', 'shipped', 'delivered', 'Paid', 'Processing', 'Shipped', 'Delivered'];
}

/** Orders needing admin attention (payment or early fulfillment). */
function adminStatsPendingStatuses(): array
{
    return [
        'pending', 'Pending',
        'awaiting_payment', 'Awaiting Payment',
        'paid', 'Paid',
        'processing', 'Processing',
    ];
}

function adminStatsInClause(array $values): string
{
    return implode(',', array_fill(0, count($values), '?'));
}

function adminStatsScalar(mysqli $conn, string $sql, string $types = '', array $params = [], string $cast = 'int')
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('admin stats prepare failed: ' . $conn->error . ' SQL: ' . $sql);
        return $cast === 'float' ? 0.0 : 0;
    }
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    if (!$stmt->execute()) {
        error_log('admin stats execute failed: ' . $stmt->error);
        return $cast === 'float' ? 0.0 : 0;
    }
    $row   = $stmt->get_result()->fetch_row();
    $value = $row[0] ?? 0;
    return $cast === 'float' ? (float) $value : (int) $value;
}

function adminStatsRows(mysqli $conn, string $sql, string $types = '', array $params = []): array
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('admin stats prepare failed: ' . $conn->error);
        return [];
    }
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    if (!$stmt->execute()) {
        error_log('admin stats execute failed: ' . $stmt->error);
        return [];
    }
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function adminStatsFormatChange(float $current, float $previous, string $suffix = ''): string
{
    if ($previous === 0.0) {
        return $current === 0.0
            ? 'No change'
            : '+' . number_format($current) . ($suffix ? " {$suffix}" : '') . ' this month';
    }
    $pct = (($current - $previous) / $previous) * 100;
    return ($pct >= 0 ? '+' : '') . round($pct, 1) . '% vs last month';
}
