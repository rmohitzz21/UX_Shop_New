<?php
declare(strict_types=1);

require_once __DIR__ . '/DigitalDownloadService.php';
require_once __DIR__ . '/EmailService.php';
require_once __DIR__ . '/InventoryReservationService.php';

class OrderFulfillmentService
{
    /**
     * Run all post-payment fulfillment steps for a paid order.
     * Idempotent: safe to call multiple times (webhook retry, race between
     * browser verify and webhook, admin re-trigger via status update).
     *
     * @param bool $deferEmails When true, only DB work runs; call sendFulfillmentEmails() after flushJsonResponse().
     */
    public static function fulfillPaidOrder(int $orderId, mysqli $conn, bool $deferEmails = false): void
    {
        self::fulfillPaidOrderCore($orderId, $conn);
        if (!$deferEmails) {
            self::sendFulfillmentEmails($orderId, $conn);
        }
    }

    /** Inventory + download tokens only (fast path for checkout). */
    public static function fulfillPaidOrderCore(int $orderId, mysqli $conn): void
    {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare('SELECT id, payment_status FROM orders WHERE id = ? FOR UPDATE');
            if ($stmt === false) {
                throw new RuntimeException('Could not prepare order fulfillment check.');
            }
            $stmt->bind_param('i', $orderId);
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$order || strtolower((string) ($order['payment_status'] ?? 'pending')) !== 'paid') {
                throw new RuntimeException('Order is not paid and cannot be fulfilled.');
            }

            InventoryReservationService::consumeOrder($conn, $orderId);
            DigitalDownloadService::generateDownloadsForOrder($orderId, $conn);
            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /** Confirmation + invoice + admin notification (slow SMTP — defer on user-facing checkout). */
    public static function sendFulfillmentEmails(int $orderId, mysqli $conn): void
    {
        try {
            EmailService::sendOrderConfirmation($orderId, $conn);
        } catch (Throwable $e) {
            error_log('OrderFulfillmentService: confirmation email failed for order ' . $orderId . ': ' . $e->getMessage());
        }
        try {
            EmailService::sendInvoice($orderId, $conn);
        } catch (Throwable $e) {
            error_log('OrderFulfillmentService: invoice email failed for order ' . $orderId . ': ' . $e->getMessage());
        }
        self::notifyAdminOnce($orderId, $conn);
    }

    private static function notifyAdminOnce(int $orderId, mysqli $conn): void
    {
        $chk = $conn->prepare('SELECT admin_notified_at FROM orders WHERE id = ? LIMIT 1');
        if ($chk === false) {
            return;
        }
        $chk->bind_param('i', $orderId);
        $chk->execute();
        $row = $chk->get_result()->fetch_assoc();
        if (!$row || !empty($row['admin_notified_at'])) {
            return;
        }
        try {
            EmailService::notifyAdminNewOrder($orderId, $conn);
        } catch (Throwable $e) {
            error_log('OrderFulfillmentService: admin notification failed for order ' . $orderId . ': ' . $e->getMessage());
        }
        $upd = $conn->prepare('UPDATE orders SET admin_notified_at = NOW() WHERE id = ?');
        if ($upd !== false) {
            $upd->bind_param('i', $orderId);
            $upd->execute();
        }
    }
}
