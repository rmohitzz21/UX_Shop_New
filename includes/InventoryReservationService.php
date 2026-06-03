<?php
declare(strict_types=1);

class InventoryReservationService
{
    public static function create(
        mysqli $conn,
        int $orderId,
        int $orderItemId,
        string $itemType,
        int $itemId,
        int $quantity,
        int $ttlMinutes = 30
    ): void {
        $ttlMinutes = max(1, min(1440, $ttlMinutes));
        $stmt = $conn->prepare(
            'INSERT INTO inventory_reservations (order_id, order_item_id, item_type, item_id, quantity, expires_at, created_at)
             VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), NOW())'
        );
        if (!$stmt) {
            throw new RuntimeException('Could not prepare inventory reservation.');
        }
        $stmt->bind_param('iisiii', $orderId, $orderItemId, $itemType, $itemId, $quantity, $ttlMinutes);
        $stmt->execute();
        $stmt->close();
    }

    public static function consumeOrder(mysqli $conn, int $orderId): void
    {
        $stmt = $conn->prepare(
            'UPDATE inventory_reservations
             SET consumed_at = NOW()
             WHERE order_id = ? AND consumed_at IS NULL AND released_at IS NULL'
        );
        if ($stmt) {
            $stmt->bind_param('i', $orderId);
            $stmt->execute();
            $stmt->close();
        }
    }

    public static function releaseOrder(mysqli $conn, int $orderId): void
    {
        $stmt = $conn->prepare(
            'SELECT id, item_type, item_id, quantity
             FROM inventory_reservations
             WHERE order_id = ? AND consumed_at IS NULL AND released_at IS NULL
             FOR UPDATE'
        );
        if (!$stmt) {
            throw new RuntimeException('Could not prepare reservation release.');
        }
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($rows as $row) {
            $reservationId = (int) $row['id'];
            $itemType = (string) $row['item_type'];
            $itemId = (int) $row['item_id'];
            $quantity = (int) $row['quantity'];

            if ($itemType === 'product') {
                $stock = $conn->prepare('UPDATE products SET stock = stock + ?, sales_count = GREATEST(sales_count - ?, 0) WHERE id = ?');
            } elseif ($itemType === 'bundle') {
                $stock = $conn->prepare('UPDATE bundles SET stock = stock + ?, sales_count = GREATEST(sales_count - ?, 0) WHERE id = ?');
            } else {
                continue;
            }

            if ($stock) {
                $stock->bind_param('iii', $quantity, $quantity, $itemId);
                $stock->execute();
                $stock->close();
            }

            $mark = $conn->prepare('UPDATE inventory_reservations SET released_at = NOW() WHERE id = ? AND released_at IS NULL AND consumed_at IS NULL');
            if ($mark) {
                $mark->bind_param('i', $reservationId);
                $mark->execute();
                $mark->close();
            }
        }
    }

    public static function releaseExpired(mysqli $conn): int
    {
        $stmt = $conn->prepare(
            "SELECT DISTINCT ir.order_id
             FROM inventory_reservations ir
             INNER JOIN orders o ON o.id = ir.order_id
             WHERE ir.expires_at < NOW()
               AND ir.consumed_at IS NULL
               AND ir.released_at IS NULL
               AND o.status = 'awaiting_payment'"
        );
        if (!$stmt) {
            return 0;
        }
        $stmt->execute();
        $orderIds = array_map('intval', array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'order_id'));
        $stmt->close();

        $released = 0;
        foreach ($orderIds as $orderId) {
            $conn->begin_transaction();
            try {
                self::releaseOrder($conn, $orderId);
                $failed = 'failed';
                $upd = $conn->prepare(
                    "UPDATE orders SET status = ?, payment_status = 'failed', status_updated_at = NOW()
                     WHERE id = ? AND status = 'awaiting_payment'"
                );
                if ($upd) {
                    $upd->bind_param('si', $failed, $orderId);
                    $upd->execute();
                    $upd->close();
                }
                $conn->commit();
                $released++;
            } catch (Throwable $e) {
                $conn->rollback();
                error_log('InventoryReservationService::releaseExpired: ' . $e->getMessage());
            }
        }

        return $released;
    }
}
