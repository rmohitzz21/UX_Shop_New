<?php

function adminCanonicalOrderStatus(string $raw): string {
    $key = strtolower(trim(str_replace(' ', '_', $raw)));
    $map = [
        'pending'          => 'pending',
        'awaiting_payment' => 'awaiting_payment',
        'paid'             => 'paid',
        'processing'       => 'processing',
        'shipped'          => 'shipped',
        'delivered'        => 'delivered',
        'cancelled'        => 'cancelled',
        'failed'           => 'cancelled',
    ];
    return $map[$key] ?? $key;
}

function adminAllowedOrderStatuses(): array {
    return ['pending', 'awaiting_payment', 'paid', 'processing', 'shipped', 'delivered', 'cancelled'];
}

/** DB values that match an admin filter key (legacy casing included). */
function adminOrderStatusFilterValues(string $filter): array {
    $key = adminCanonicalOrderStatus($filter);
    $groups = [
        'pending'          => ['pending', 'Pending'],
        'awaiting_payment' => ['awaiting_payment', 'Awaiting Payment'],
        'paid'             => ['paid', 'Paid'],
        'processing'       => ['processing', 'Processing'],
        'shipped'          => ['shipped', 'Shipped'],
        'delivered'        => ['delivered', 'Delivered'],
        'cancelled'        => ['cancelled', 'Cancelled', 'failed', 'Failed'],
    ];
    return $groups[$key] ?? [$filter];
}
