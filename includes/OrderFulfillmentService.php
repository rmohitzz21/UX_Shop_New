<?php
declare(strict_types=1);

require_once __DIR__ . '/DigitalDownloadService.php';
require_once __DIR__ . '/EmailService.php';

class OrderFulfillmentService
{
    /**
     * Run all post-payment fulfillment steps for a paid order.
     * Idempotent: safe to call multiple times (webhook retry, race between
     * browser verify and webhook, admin re-trigger via status update).
     */
    public static function fulfillPaidOrder(int $orderId, mysqli $conn): void
    {
        DigitalDownloadService::generateDownloadsForOrder($orderId, $conn);
        EmailService::sendPaidOrderEmails($orderId, $conn);
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
