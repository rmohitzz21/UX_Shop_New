<?php
/**
 * Idempotent order payment capture for Razorpay (customer verify + webhook).
 */

declare(strict_types=1);

require_once __DIR__ . '/RazorpayClient.php';

/**
 * Mark order paid after Razorpay payment is verified (signature + API amount).
 *
 * @param int|null $sessionUserId If set (browser flow), must equal orders.user_id
 * @return array{ok:bool, duplicate?:bool, http?:int, message?:string, order_id?:int}
 */
function order_capture_razorpay_payment(
    mysqli $conn,
    int $internalOrderId,
    ?int $sessionUserId,
    string $rzpPaymentId,
    string $rzpOrderId,
    int $amountPaiseFromGateway,
    string $currencyFromGateway
): array {
    $currencyFromGateway = strtolower(trim($currencyFromGateway));
    if ($currencyFromGateway !== 'inr') {
        error_log("Razorpay capture: unsupported currency {$currencyFromGateway} order={$internalOrderId}");
        return ['ok' => false, 'http' => 400, 'message' => 'Invalid payment currency'];
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare('SELECT id, user_id, total, status, payment_id, razorpay_order_id FROM orders WHERE id = ? FOR UPDATE');
        if (!$stmt) {
            throw new RuntimeException('Prepare failed');
        }
        $stmt->bind_param('i', $internalOrderId);
        $stmt->execute();
        $res = $stmt->get_result();
        $order = $res->fetch_assoc();
        $stmt->close();

        if (!$order) {
            $conn->rollback();
            return ['ok' => false, 'http' => 404, 'message' => 'Order not found'];
        }

        if ($sessionUserId !== null && (int) $order['user_id'] !== $sessionUserId) {
            $conn->rollback();
            return ['ok' => false, 'http' => 403, 'message' => 'Order does not belong to this user'];
        }

        // Idempotent: already paid with same payment id
        if ($order['status'] === 'paid') {
            if (!empty($order['payment_id']) && hash_equals((string) $order['payment_id'], $rzpPaymentId)) {
                $conn->commit();
                return ['ok' => true, 'duplicate' => true, 'order_id' => $internalOrderId];
            }
            $conn->rollback();
            error_log("Razorpay capture: order {$internalOrderId} already paid with different payment");
            return ['ok' => false, 'http' => 409, 'message' => 'Order already paid'];
        }

        if ($order['status'] !== 'awaiting_payment') {
            $conn->rollback();
            return ['ok' => false, 'http' => 400, 'message' => 'Order is not awaiting payment'];
        }

        $expectedPaise = rzp_order_total_to_paise((float) $order['total']);
        if ($expectedPaise !== $amountPaiseFromGateway) {
            $conn->rollback();
            error_log("Razorpay capture: amount mismatch order={$internalOrderId} expected_paise={$expectedPaise} got={$amountPaiseFromGateway}");
            return ['ok' => false, 'http' => 400, 'message' => 'Payment amount does not match order total'];
        }

        $dbRzp = trim((string) ($order['razorpay_order_id'] ?? ''));
        if ($dbRzp !== '' && !hash_equals($dbRzp, $rzpOrderId)) {
            $conn->rollback();
            error_log("Razorpay capture: razorpay order id mismatch order={$internalOrderId}");
            return ['ok' => false, 'http' => 400, 'message' => 'Payment order mismatch'];
        }

        $now    = date('Y-m-d H:i:s');
        $upd    = $conn->prepare('UPDATE orders SET status = ?, payment_method = ?, payment_id = ?, razorpay_order_id = ?, payment_verified_at = ?, payment_currency = ? WHERE id = ? AND status = ?');
        $paid   = 'paid';
        $method = 'razorpay';
        $cur    = 'INR';
        $await  = 'awaiting_payment';
        $upd->bind_param(
            'ssssssis',
            $paid,
            $method,
            $rzpPaymentId,
            $rzpOrderId,
            $now,
            $cur,
            $internalOrderId,
            $await
        );
        $upd->execute();
        if ($upd->affected_rows === 0) {
            $upd->close();
            $conn->rollback();
            return ['ok' => false, 'http' => 409, 'message' => 'Could not update order (race or state changed)'];
        }
        $upd->close();

        $uid = (int) $order['user_id'];
        $clr = $conn->prepare('DELETE FROM cart WHERE user_id = ?');
        $clr->bind_param('i', $uid);
        $clr->execute();
        $clr->close();

        $conn->commit();
        return ['ok' => true, 'duplicate' => false, 'order_id' => $internalOrderId];
    } catch (Throwable $e) {
        $conn->rollback();
        error_log('order_capture_razorpay_payment: ' . $e->getMessage());
        return ['ok' => false, 'http' => 500, 'message' => 'Payment processing error'];
    }
}

/**
 * Webhook: mark payment failed (only from awaiting_payment).
 */
function order_mark_payment_failed(mysqli $conn, string $rzpOrderId, string $rzpPaymentId): array
{
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare('SELECT id, status FROM orders WHERE razorpay_order_id = ? FOR UPDATE');
        $stmt->bind_param('s', $rzpOrderId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            $conn->rollback();
            return ['ok' => true, 'skipped' => true];
        }

        if ($row['status'] === 'paid') {
            $conn->commit();
            return ['ok' => true, 'skipped' => true];
        }

        if ($row['status'] !== 'awaiting_payment') {
            $conn->rollback();
            return ['ok' => true, 'skipped' => true];
        }

        $failed = 'failed';
        $upd    = $conn->prepare('UPDATE orders SET status = ?, payment_id = ? WHERE id = ? AND status = ?');
        $await  = 'awaiting_payment';
        $upd->bind_param('ssis', $failed, $rzpPaymentId, $row['id'], $await);
        $upd->execute();
        $upd->close();
        $conn->commit();
        return ['ok' => true];
    } catch (Throwable $e) {
        $conn->rollback();
        error_log('order_mark_payment_failed: ' . $e->getMessage());
        return ['ok' => false];
    }
}
