<?php
/**
 * Customer product/bundle reviews (post-purchase).
 */
declare(strict_types=1);

/** Order statuses that count as a completed purchase for leaving a review. */
function reviewEligibleOrderStatuses(): array
{
    return ['paid', 'processing', 'shipped', 'delivered', 'pending'];
}

function reviewOrderStatusAllows(string $status): bool
{
    return in_array(strtolower(trim($status)), reviewEligibleOrderStatuses(), true);
}

function reviewUserHasPurchasedProduct(mysqli $conn, int $userId, int $productId): bool
{
    if ($productId <= 0) {
        return false;
    }
    $statuses = reviewEligibleOrderStatuses();
    $in       = implode(',', array_fill(0, count($statuses), '?'));
    $sql      = "SELECT 1 FROM order_items oi
                 INNER JOIN orders o ON o.id = oi.order_id
                 WHERE o.user_id = ? AND o.status IN ({$in})
                   AND oi.product_id = ? AND oi.item_type = 'product'
                 LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }
    $types  = 'i' . str_repeat('s', count($statuses)) . 'i';
    $params = array_merge([$userId], $statuses, [$productId]);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return (bool) $stmt->get_result()->fetch_row();
}

function reviewUserHasPurchasedBundle(mysqli $conn, int $userId, int $bundleId): bool
{
    if ($bundleId <= 0) {
        return false;
    }
    $statuses = reviewEligibleOrderStatuses();
    $in       = implode(',', array_fill(0, count($statuses), '?'));
    $sql      = "SELECT 1 FROM order_items oi
                 INNER JOIN orders o ON o.id = oi.order_id
                 WHERE o.user_id = ? AND o.status IN ({$in})
                   AND oi.bundle_id = ? AND oi.item_type = 'bundle'
                 LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }
    $types  = 'i' . str_repeat('s', count($statuses)) . 'i';
    $params = array_merge([$userId], $statuses, [$bundleId]);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return (bool) $stmt->get_result()->fetch_row();
}

function reviewFindByUserProduct(mysqli $conn, int $userId, int $productId): ?array
{
    if ($productId <= 0) {
        return null;
    }
    $stmt = $conn->prepare(
        'SELECT id, rating, comment, is_approved, created_at FROM reviews
         WHERE user_id = ? AND product_id = ? LIMIT 1'
    );
    $stmt->bind_param('ii', $userId, $productId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}

function reviewFindByUserBundle(mysqli $conn, int $userId, int $bundleId): ?array
{
    if ($bundleId <= 0) {
        return null;
    }
    $stmt = $conn->prepare(
        'SELECT id, rating, comment, is_approved, created_at FROM reviews
         WHERE user_id = ? AND bundle_id = ? LIMIT 1'
    );
    $stmt->bind_param('ii', $userId, $bundleId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}

/**
 * @param list<array<string,mixed>> $items
 * @return list<array<string,mixed>>
 */
function reviewEnrichOrderItems(mysqli $conn, int $userId, string $orderStatus, array $items): array
{
    $orderOk = reviewOrderStatusAllows($orderStatus);
    $out     = [];

    foreach ($items as $item) {
        $type      = strtolower((string) ($item['item_type'] ?? 'product'));
        $productId = (int) ($item['product_id'] ?? 0);
        $bundleId  = (int) ($item['bundle_id'] ?? 0);

        $meta = [
            'can_review'   => false,
            'has_reviewed' => false,
            'review'       => null,
        ];

        if ($type === 'freebie' || (!$productId && !$bundleId)) {
            $out[] = array_merge($item, $meta);
            continue;
        }

        $existing = null;
        if ($type === 'bundle' && $bundleId > 0) {
            $existing = reviewFindByUserBundle($conn, $userId, $bundleId);
        } elseif ($productId > 0) {
            $existing = reviewFindByUserProduct($conn, $userId, $productId);
        }

        if ($existing) {
            $meta['has_reviewed'] = true;
            $meta['review']       = [
                'id'          => (int) $existing['id'],
                'rating'      => (int) $existing['rating'],
                'comment'     => (string) ($existing['comment'] ?? ''),
                'is_approved' => (int) $existing['is_approved'],
            ];
        } elseif ($orderOk) {
            if ($type === 'bundle' && $bundleId > 0) {
                $meta['can_review'] = reviewUserHasPurchasedBundle($conn, $userId, $bundleId);
            } elseif ($productId > 0) {
                $meta['can_review'] = reviewUserHasPurchasedProduct($conn, $userId, $productId);
            }
        }

        $out[] = array_merge($item, $meta);
    }

    return $out;
}
