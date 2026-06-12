<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Mailer.php';

/**
 * Central outbound email service for UX Pacific Shop.
 * Credentials and domains come from .env only — never hardcode secrets.
 */
class EmailService
{
    private static function mailer(): Mailer
    {
        $m = new Mailer();
        $reply = trim((string) (getenv('SUPPORT_EMAIL') ?: getenv('SMTP_FROM_EMAIL') ?: getenv('SMTP_FROM') ?: ''));
        if ($reply !== '') {
            $m->setReplyTo($reply);
        }
        return $m;
    }

    private static function appUrl(): string
    {
        return rtrim((string) (getenv('APP_URL') ?: 'http://localhost'), '/');
    }

    private static function supportEmail(): string
    {
        return (string) (getenv('SUPPORT_EMAIL') ?: getenv('SMTP_FROM_EMAIL') ?: getenv('SMTP_FROM') ?: 'support@uxpacific.com');
    }

    private static function adminNotifyEmail(): string
    {
        $admin = trim((string) (getenv('ADMIN_NOTIFICATION_EMAIL') ?: getenv('ADMIN_EMAIL') ?: ''));
        if ($admin !== '' && filter_var($admin, FILTER_VALIDATE_EMAIL)) {
            return $admin;
        }
        return self::supportEmail();
    }

    private static function send(string $to, string $subject, string $bodyHtml, string $plainText = ''): bool
    {
        return self::sendWithReplyTo($to, $subject, $bodyHtml, '', $plainText);
    }

    private static function sendWithReplyTo(
        string $to,
        string $subject,
        string $bodyHtml,
        string $replyTo = '',
        string $plainText = ''
    ): bool {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        try {
            $mailer = new Mailer();
            $reply  = trim($replyTo);
            if ($reply !== '' && filter_var($reply, FILTER_VALIDATE_EMAIL)) {
                $mailer->setReplyTo($reply);
            } else {
                $fallback = trim(self::supportEmail());
                if ($fallback !== '' && filter_var($fallback, FILTER_VALIDATE_EMAIL)) {
                    $mailer->setReplyTo($fallback);
                }
            }

            $html = self::wrapInTemplate($subject, $bodyHtml);
            $ok   = $mailer->send($to, $subject, $html, $plainText);
            if ($ok) {
                error_log('EmailService: sent "' . $subject . '" to ' . $to);
            } else {
                error_log('EmailService: failed to send "' . $subject . '" to ' . $to);
            }
            return $ok;
        } catch (Throwable $e) {
            error_log('EmailService::send failed for ' . $to . ': ' . $e->getMessage());
            return false;
        }
    }

    private static function wrapInTemplate(string $title, string $bodyHtml): string
    {
        $appUrl       = htmlspecialchars(self::appUrl(), ENT_QUOTES, 'UTF-8');
        $supportEmail = htmlspecialchars(self::supportEmail(), ENT_QUOTES, 'UTF-8');
        $safeTitle    = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $year         = date('Y');

        return "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1.0'></head>
<body style='margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;'>
<div style='max-width:600px;margin:0 auto;padding:20px;'>
<div style='text-align:center;padding:24px 0;'><h1 style='margin:0;font-size:24px;color:#111827;'>UX Pacific</h1>
<p style='margin:4px 0 0;color:#6b7280;font-size:13px;'>Premium Design Resources</p></div>
<div style='background:#fff;border-radius:12px;padding:32px;box-shadow:0 1px 3px rgba(0,0,0,0.1);'>
<h2 style='margin:0 0 16px;font-size:20px;color:#111827;'>{$safeTitle}</h2>
{$bodyHtml}
</div>
<div style='text-align:center;padding:24px 0;color:#9ca3af;font-size:12px;'>
<p>&copy; {$year} UX Pacific. All rights reserved.</p>
<p><a href='{$appUrl}/shopAll.php' style='color:#6b7280;text-decoration:none;'>Shop</a> ·
<a href='{$appUrl}/contact.php' style='color:#6b7280;text-decoration:none;'>Contact</a> ·
<a href='mailto:{$supportEmail}' style='color:#6b7280;text-decoration:none;'>{$supportEmail}</a></p>
</div></div></body></html>";
    }

    private static function btn(string $label, string $href): string
    {
        $safeHref = htmlspecialchars($href, ENT_QUOTES, 'UTF-8');
        return "<p style='text-align:center;margin:24px 0;'><a href='{$safeHref}' style='background:#2563eb;color:#fff;padding:12px 32px;border-radius:8px;text-decoration:none;font-weight:500;display:inline-block;'>{$label}</a></p>";
    }

    /** @return array{order:array,items:array,has_digital:bool,has_physical:bool}|null */
    private static function fetchOrderContext(int $orderId, mysqli $conn): ?array
    {
        $stmt = $conn->prepare("
            SELECT o.*, u.email AS user_email, u.first_name, u.last_name
            FROM orders o
            JOIN users u ON u.id = o.user_id
            WHERE o.id = ?
            LIMIT 1
        ");
        if ($stmt === false) {
            return null;
        }
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        if (!$order) {
            return null;
        }

        $iStmt = $conn->prepare("
            SELECT COALESCE(product_name, 'Item') AS name, quantity, price, size,
                   item_type, selected_format
            FROM order_items WHERE order_id = ?
        ");
        if ($iStmt === false) {
            return null;
        }
        $iStmt->bind_param('i', $orderId);
        $iStmt->execute();
        $items = $iStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $hasDigital  = false;
        $hasPhysical = false;
        foreach ($items as $item) {
            $fmt = strtolower((string) ($item['selected_format'] ?? 'digital'));
            if ($fmt === 'physical') {
                $hasPhysical = true;
            } else {
                $hasDigital = true;
            }
        }

        return [
            'order'         => $order,
            'items'         => $items,
            'has_digital'   => $hasDigital,
            'has_physical'  => $hasPhysical,
        ];
    }

    private static function buildOrderItemsTable(array $items): string
    {
        $rows = '';
        foreach ($items as $item) {
            $name  = htmlspecialchars((string) ($item['name'] ?? 'Item'), ENT_QUOTES, 'UTF-8');
            $qty   = (int) ($item['quantity'] ?? 1);
            $price = number_format((float) ($item['price'] ?? 0), 2);
            $rows .= "<tr><td style='padding:8px;border-bottom:1px solid #e5e7eb;'>{$name}</td>
<td style='padding:8px;border-bottom:1px solid #e5e7eb;text-align:center;'>{$qty}</td>
<td style='padding:8px;border-bottom:1px solid #e5e7eb;text-align:right;'>₹{$price}</td></tr>";
        }
        return "<table style='width:100%;border-collapse:collapse;margin:16px 0;'>
<thead><tr style='background:#f9fafb;'><th style='padding:8px;text-align:left;'>Item</th>
<th style='padding:8px;text-align:center;'>Qty</th><th style='padding:8px;text-align:right;'>Price</th></tr></thead>
<tbody>{$rows}</tbody></table>";
    }

    private static function buildShippingInfo(array $order): string
    {
        $addr = json_decode((string) ($order['shipping_address'] ?? '{}'), true);
        if (!is_array($addr) || $addr === []) {
            return '';
        }
        $lines = array_filter([
            trim(($addr['firstName'] ?? '') . ' ' . ($addr['lastName'] ?? '')),
            $addr['address'] ?? '',
            trim(($addr['city'] ?? '') . ', ' . ($addr['state'] ?? '') . ' ' . ($addr['zip'] ?? '')),
            $addr['country'] ?? '',
            isset($addr['phone']) ? 'Phone: ' . $addr['phone'] : '',
        ]);
        if ($lines === []) {
            return '';
        }
        $html = '<div style="background:#f9fafb;border-radius:8px;padding:14px;margin:16px 0;"><strong>Shipping address</strong><br>';
        foreach ($lines as $line) {
            $html .= htmlspecialchars((string) $line, ENT_QUOTES, 'UTF-8') . '<br>';
        }
        return $html . '</div>';
    }

    private static function customerName(array $order): string
    {
        return trim(((string) ($order['first_name'] ?? '')) . ' ' . ((string) ($order['last_name'] ?? '')));
    }

    // ── Public email methods ───────────────────────────────────────────────────

    public static function sendWelcome(array $user): bool
    {
        $email = (string) ($user['email'] ?? '');
        $name  = htmlspecialchars(trim((string) ($user['first_name'] ?? $user['name'] ?? 'there')), ENT_QUOTES, 'UTF-8');
        $html  = "<p>Welcome to UX Pacific, {$name}!</p>
<p>Your account is ready. Browse premium UI templates, mockups, UI kits, and design resources.</p>"
            . self::btn('Browse the Shop', self::appUrl() . '/shopAll.php')
            . '<p>Need help? Contact us at ' . htmlspecialchars(self::supportEmail(), ENT_QUOTES, 'UTF-8') . '</p>';

        return self::send($email, 'Welcome to UX Pacific!', $html);
    }

    public static function sendPasswordReset(array $user, string $resetToken): bool
    {
        $email    = (string) ($user['email'] ?? '');
        $name     = htmlspecialchars(trim((string) ($user['first_name'] ?? 'there')), ENT_QUOTES, 'UTF-8');
        $resetUrl = self::appUrl() . '/reset-password.php?token=' . urlencode($resetToken);
        if (!empty($user['email'])) {
            $resetUrl .= '&email=' . urlencode((string) $user['email']);
        }

        $html = "<p>Hi {$name}, we received a request to reset your password.</p>
<p style='color:#6b7280;font-size:13px;'>This link expires in 1 hour. If you did not request a reset, ignore this email.</p>"
            . self::btn('Reset Password', $resetUrl);

        return self::send($email, 'Reset your password — UX Pacific', $html);
    }

    public static function sendOrderConfirmation(int $orderId, mysqli $conn): bool
    {
        $ctx = self::fetchOrderContext($orderId, $conn);
        if ($ctx === null) {
            return false;
        }
        $order = $ctx['order'];
        if (!empty($order['confirmation_email_sent_at'])) {
            return true;
        }

        $email       = (string) $order['user_email'];
        $name        = htmlspecialchars(self::customerName($order) ?: 'Customer', ENT_QUOTES, 'UTF-8');
        $orderNum    = htmlspecialchars((string) $order['order_number'], ENT_QUOTES, 'UTF-8');
        $total       = number_format((float) $order['total'], 2);
        $itemsTable  = self::buildOrderItemsTable($ctx['items']);
        $ordersUrl   = self::appUrl() . '/orders.php';
        $pm          = strtolower((string) ($order['payment_method'] ?? ''));
        $isFree      = $pm === 'free' || (float) $order['total'] <= 0;
        $isCod       = $pm === 'cod';
        $hasDigital  = $ctx['has_digital'];
        $hasPhysical = $ctx['has_physical'];

        if ($isFree && $hasDigital) {
            $subject = "Your free download is ready! (#{$orderNum})";
            $body    = "<p>Hi {$name}, thank you for downloading from UX Pacific!</p>
{$itemsTable}<p><strong>Total: ₹{$total}</strong></p>
<p>Your free resources are ready in My Orders.</p>"
                . self::btn('Access Your Downloads', $ordersUrl)
                . self::btn('Browse Premium Resources', self::appUrl() . '/shopAll.php');
        } elseif ($isCod && $hasPhysical && !$hasDigital) {
            $subject = "Order placed — Payment due on delivery (#{$orderNum})";
            $body    = "<p>Hi {$name}, your order has been placed.</p>
<p><strong>Payment will be collected on delivery.</strong> Please keep ₹{$total} ready for the delivery person.</p>
{$itemsTable}" . self::buildShippingInfo($order)
                . self::btn('Track Your Order', $ordersUrl);
        } elseif ($hasDigital && !$hasPhysical) {
            $subject = "Order confirmed — Your downloads are ready! (#{$orderNum})";
            $body    = "<p>Hi {$name}, thank you for your purchase!</p>
<p>Your digital products are ready to download.</p>
{$itemsTable}<p><strong>Total paid: ₹{$total}</strong></p>
<p style='color:#6b7280;font-size:13px;'>Access files from My Orders — we never email direct download links.</p>"
                . self::btn('Access Your Downloads', $ordersUrl);
        } elseif ($hasPhysical && !$hasDigital) {
            $subject = "Order confirmed (#{$orderNum})";
            $body    = "<p>Hi {$name}, thank you for your order. We are preparing your items for shipping.</p>
{$itemsTable}<p><strong>Total: ₹{$total}</strong></p>"
                . self::buildShippingInfo($order)
                . self::btn('Track Your Order', $ordersUrl);
        } else {
            $subject = "Order confirmed (#{$orderNum})";
            $body    = "<p>Hi {$name}, thank you! Digital downloads are ready and physical items are being prepared.</p>
{$itemsTable}<p><strong>Total: ₹{$total}</strong></p>"
                . self::buildShippingInfo($order)
                . self::btn('View Order &amp; Downloads', $ordersUrl);
        }

        $sent = self::send($email, $subject, $body);
        if ($sent) {
            $mark = $conn->prepare('UPDATE orders SET confirmation_email_sent_at = NOW() WHERE id = ? AND confirmation_email_sent_at IS NULL');
            if ($mark) {
                $mark->bind_param('i', $orderId);
                $mark->execute();
            }
        }
        return $sent;
    }

    public static function sendInvoice(int $orderId, mysqli $conn): bool
    {
        $ctx = self::fetchOrderContext($orderId, $conn);
        if ($ctx === null) {
            return false;
        }
        $order = $ctx['order'];
        if (!empty($order['invoice_email_sent_at'])) {
            return true;
        }

        $email     = (string) $order['user_email'];
        $orderNum  = htmlspecialchars((string) $order['order_number'], ENT_QUOTES, 'UTF-8');
        $subtotal  = number_format((float) ($order['subtotal'] ?? $order['total']), 2);
        $tax       = number_format((float) ($order['tax'] ?? 0), 2);
        $shipping  = number_format((float) ($order['shipping'] ?? 0), 2);
        $total     = number_format((float) $order['total'], 2);
        $pm        = htmlspecialchars(ucfirst((string) ($order['payment_method'] ?? 'Online')), ENT_QUOTES, 'UTF-8');
        $txn       = htmlspecialchars((string) ($order['payment_id'] ?? $order['razorpay_order_id'] ?? '—'), ENT_QUOTES, 'UTF-8');
        $date      = date('d M Y', strtotime((string) ($order['created_at'] ?? 'now')));

        $itemRows = '';
        foreach ($ctx['items'] as $item) {
            $n = htmlspecialchars((string) ($item['name'] ?? 'Item'), ENT_QUOTES, 'UTF-8');
            $q = (int) ($item['quantity'] ?? 1);
            $p = number_format((float) ($item['price'] ?? 0), 2);
            $lt = number_format((float) ($item['price'] ?? 0) * $q, 2);
            $itemRows .= "<tr><td style='padding:8px;border-bottom:1px solid #e5e7eb;'>{$n}</td>
<td style='padding:8px;text-align:center;border-bottom:1px solid #e5e7eb;'>{$q}</td>
<td style='padding:8px;text-align:right;border-bottom:1px solid #e5e7eb;'>₹{$p}</td>
<td style='padding:8px;text-align:right;border-bottom:1px solid #e5e7eb;'>₹{$lt}</td></tr>";
        }

        $html = "<p style='color:#6b7280;margin:0 0 8px;'>Invoice INV-{$orderId} · {$date}</p>
<table style='width:100%;border-collapse:collapse;'><thead><tr style='background:#f9fafb;'>
<th style='padding:8px;text-align:left;'>Item</th><th>Qty</th><th style='text-align:right;'>Unit</th><th style='text-align:right;'>Total</th>
</tr></thead><tbody>{$itemRows}</tbody></table>
<table style='width:100%;max-width:280px;margin-left:auto;margin-top:12px;'>
<tr><td style='padding:4px;color:#6b7280;'>Subtotal</td><td style='text-align:right;padding:4px;'>₹{$subtotal}</td></tr>
<tr><td style='padding:4px;color:#6b7280;'>Tax</td><td style='text-align:right;padding:4px;'>₹{$tax}</td></tr>
<tr><td style='padding:4px;color:#6b7280;'>Shipping</td><td style='text-align:right;padding:4px;'>₹{$shipping}</td></tr>
<tr style='font-weight:bold;border-top:2px solid #111;'><td style='padding:8px;'>Total</td><td style='text-align:right;padding:8px;'>₹{$total}</td></tr>
</table>
<p style='margin-top:16px;color:#6b7280;font-size:13px;'>Payment: {$pm}<br>Transaction ID: {$txn}</p>
<p style='font-size:12px;color:#9ca3af;'>System-generated invoice. Queries: " . htmlspecialchars(self::supportEmail(), ENT_QUOTES, 'UTF-8') . '</p>';

        $sent = self::send($email, "Invoice for Order #{$orderNum} — UX Pacific", $html);
        if ($sent) {
            $mark = $conn->prepare('UPDATE orders SET invoice_email_sent_at = NOW() WHERE id = ? AND invoice_email_sent_at IS NULL');
            if ($mark) {
                $mark->bind_param('i', $orderId);
                $mark->execute();
            }
        }
        return $sent;
    }

    public static function sendPaymentFailed(int $orderId, mysqli $conn): bool
    {
        $ctx = self::fetchOrderContext($orderId, $conn);
        if ($ctx === null) {
            return false;
        }
        $order = $ctx['order'];
        if (!empty($order['payment_failed_email_sent_at'])) {
            return true;
        }

        $email    = (string) $order['user_email'];
        $orderNum = htmlspecialchars((string) $order['order_number'], ENT_QUOTES, 'UTF-8');
        $html     = "<p>Your payment for order <strong>#{$orderNum}</strong> was unsuccessful.</p>
<p>You can retry payment from My Orders — your order has been saved.</p>"
            . self::btn('Try Again', self::appUrl() . '/orders.php');

        $sent = self::send($email, "Payment unsuccessful — Order #{$orderNum}", $html);
        if ($sent) {
            $mark = $conn->prepare('UPDATE orders SET payment_failed_email_sent_at = NOW() WHERE id = ?');
            if ($mark) {
                $mark->bind_param('i', $orderId);
                $mark->execute();
            }
        }
        return $sent;
    }

    public static function sendOrderShipped(int $orderId, mysqli $conn, string $trackingInfo = ''): bool
    {
        $ctx = self::fetchOrderContext($orderId, $conn);
        if ($ctx === null) {
            return false;
        }
        $order = $ctx['order'];
        if (!empty($order['shipped_email_sent_at'])) {
            return true;
        }

        $email    = (string) $order['user_email'];
        $orderNum = htmlspecialchars((string) $order['order_number'], ENT_QUOTES, 'UTF-8');
        $track    = $trackingInfo !== '' ? '<p><strong>Tracking:</strong> ' . htmlspecialchars($trackingInfo, ENT_QUOTES, 'UTF-8') . '</p>' : '';
        $html     = "<p>Great news! Order <strong>#{$orderNum}</strong> is on its way.</p>{$track}"
            . self::buildShippingInfo($order)
            . self::btn('Track Your Order', self::appUrl() . '/orders.php');

        $sent = self::send($email, "Your order has shipped! (#{$orderNum})", $html);
        if ($sent) {
            $mark = $conn->prepare('UPDATE orders SET shipped_email_sent_at = NOW() WHERE id = ?');
            if ($mark) {
                $mark->bind_param('i', $orderId);
                $mark->execute();
            }
        }
        return $sent;
    }

    public static function sendOrderDelivered(int $orderId, mysqli $conn): bool
    {
        $ctx = self::fetchOrderContext($orderId, $conn);
        if ($ctx === null) {
            return false;
        }
        $order = $ctx['order'];
        if (!empty($order['delivered_email_sent_at'])) {
            return true;
        }

        $email    = (string) $order['user_email'];
        $orderNum = htmlspecialchars((string) $order['order_number'], ENT_QUOTES, 'UTF-8');
        $html     = "<p>Your order <strong>#{$orderNum}</strong> has been delivered.</p>
<p>We hope you love your purchase. Share your experience with a review!</p>"
            . self::btn('Leave a Review', self::appUrl() . '/orders.php');

        $sent = self::send($email, "Your order has been delivered (#{$orderNum})", $html);
        if ($sent) {
            $mark = $conn->prepare('UPDATE orders SET delivered_email_sent_at = NOW() WHERE id = ?');
            if ($mark) {
                $mark->bind_param('i', $orderId);
                $mark->execute();
            }
        }
        return $sent;
    }

    public static function sendOrderCancelled(int $orderId, mysqli $conn, string $reason = ''): bool
    {
        $ctx = self::fetchOrderContext($orderId, $conn);
        if ($ctx === null) {
            return false;
        }
        $order    = $ctx['order'];
        $email    = (string) $order['user_email'];
        $orderNum = htmlspecialchars((string) $order['order_number'], ENT_QUOTES, 'UTF-8');
        $reasonHtml = $reason !== '' ? '<p>Reason: ' . htmlspecialchars($reason, ENT_QUOTES, 'UTF-8') . '</p>' : '';
        $html     = "<p>Your order <strong>#{$orderNum}</strong> has been cancelled.</p>{$reasonHtml}"
            . self::btn('Browse the Shop', self::appUrl() . '/shopAll.php');

        return self::send($email, "Order cancelled (#{$orderNum})", $html);
    }

    public static function notifyAdminNewOrder(int $orderId, mysqli $conn): bool
    {
        $ctx = self::fetchOrderContext($orderId, $conn);
        if ($ctx === null) {
            return false;
        }
        $order = $ctx['order'];
        $name  = htmlspecialchars(self::customerName($order), ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars((string) $order['user_email'], ENT_QUOTES, 'UTF-8');
        $num   = htmlspecialchars((string) $order['order_number'], ENT_QUOTES, 'UTF-8');
        $total = number_format((float) $order['total'], 2);
        $flags = ($ctx['has_digital'] ? 'Digital ' : '') . ($ctx['has_physical'] ? 'Physical' : '');

        $html = "<p><strong>New paid order</strong> #{$num}</p>
<p>Customer: {$name} ({$email})<br>Total: ₹{$total}<br>Type: {$flags}<br>Payment: " . htmlspecialchars((string) $order['payment_method'], ENT_QUOTES, 'UTF-8') . '</p>'
            . self::buildOrderItemsTable($ctx['items']);

        return self::send(self::adminNotifyEmail(), "[New Order] #{$num} — ₹{$total}", $html);
    }

    public static function notifyAdminPaymentFailed(int $orderId, mysqli $conn): bool
    {
        $ctx = self::fetchOrderContext($orderId, $conn);
        if ($ctx === null) {
            return false;
        }
        $order = $ctx['order'];
        $num   = htmlspecialchars((string) $order['order_number'], ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars((string) $order['user_email'], ENT_QUOTES, 'UTF-8');

        return self::send(self::adminNotifyEmail(), "[Payment Failed] #{$num} — {$email}",
            "<p>Payment failed for order <strong>#{$num}</strong> ({$email}).</p>");
    }

    public static function sendContactFormNotification(string $name, string $email, string $subject, string $message, string $phone = ''): bool
    {
        $safeName  = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        $safeSubj  = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
        $safeMsg   = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
        $safePhone = htmlspecialchars($phone !== '' ? $phone : '—', ENT_QUOTES, 'UTF-8');

        $html = "<p>You have received a new inquiry from the UX Pacific contact form.</p>
<div style='background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:16px;margin:16px 0;'>
  <p style='margin:0 0 8px;'><strong>Name:</strong> {$safeName}</p>
  <p style='margin:0 0 8px;'><strong>Email:</strong> <a href='mailto:{$safeEmail}'>{$safeEmail}</a></p>
  <p style='margin:0 0 8px;'><strong>Phone:</strong> {$safePhone}</p>
  <p style='margin:0;'><strong>Subject:</strong> {$safeSubj}</p>
</div>
<p><strong>Message:</strong></p>
<p>{$safeMsg}</p>
<p style='color:#6b7280;font-size:13px;'>Reply directly to this email to respond to the customer.</p>";

        $adminTo = self::adminNotifyEmail();
        return self::sendWithReplyTo(
            $adminTo,
            '[Contact Form] New inquiry from ' . $name,
            $html,
            $email
        );
    }

    public static function sendRefundConfirmation(int $orderId, mysqli $conn, float $refundAmount, string $reason = ''): bool
    {
        $ctx = self::fetchOrderContext($orderId, $conn);
        if ($ctx === null) {
            return false;
        }
        $order    = $ctx['order'];
        $email    = (string) $order['user_email'];
        $orderNum = htmlspecialchars((string) $order['order_number'], ENT_QUOTES, 'UTF-8');
        $amount   = number_format($refundAmount, 2);
        $reasonHtml = $reason !== '' ? '<p>Reason: ' . htmlspecialchars($reason, ENT_QUOTES, 'UTF-8') . '</p>' : '';
        $html     = "<p>Your refund of <strong>₹{$amount}</strong> for order <strong>#{$orderNum}</strong> has been processed.</p>
{$reasonHtml}
<p style='color:#6b7280;font-size:13px;'>Refunds typically take 5–7 business days to appear on your original payment method.</p>";

        return self::send($email, "Refund processed — ₹{$amount} (#{$orderNum})", $html);
    }

    public static function sendEmailVerification(array $user, string $token): bool
    {
        $email = (string) ($user['email'] ?? '');
        $name  = htmlspecialchars(trim((string) ($user['first_name'] ?? 'there')), ENT_QUOTES, 'UTF-8');
        $url   = self::appUrl() . '/verify-email.php?token=' . urlencode($token);
        $html  = "<p>Hi {$name}, please verify your email address.</p>
<p style='color:#6b7280;font-size:13px;'>This link expires in 24 hours. If you did not create an account, ignore this email.</p>"
            . self::btn('Verify Email', $url);

        return self::send($email, 'Verify your email — UX Pacific', $html);
    }

    public static function sendContactConfirmation(string $name, string $email): bool
    {
        $safeName    = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $support     = htmlspecialchars(self::supportEmail(), ENT_QUOTES, 'UTF-8');
        $html        = "<p>Hi {$safeName},</p>
<p><strong>Thank you for contacting UX Pacific.</strong></p>
<p>We have received your message and our team will get back to you within 24 hours on business days.</p>
<p style='color:#6b7280;font-size:13px;'>If your enquiry is urgent, email us at <a href='mailto:{$support}'>{$support}</a>.</p>";

        return self::send($email, 'Thank you for contacting UX Pacific', $html);
    }

    /**
     * Send admin inquiry + user thank-you emails after a contact form submission.
     *
     * @return array{admin: bool, user: bool}
     */
    public static function sendContactEmails(string $name, string $email, string $subject, string $message, string $phone = ''): array
    {
        $result = ['admin' => false, 'user' => false];

        try {
            $result['admin'] = self::sendContactFormNotification($name, $email, $subject, $message, $phone);
        } catch (Throwable $e) {
            error_log('EmailService::sendContactFormNotification: ' . $e->getMessage());
        }

        try {
            $result['user'] = self::sendContactConfirmation($name, $email);
        } catch (Throwable $e) {
            error_log('EmailService::sendContactConfirmation: ' . $e->getMessage());
        }

        return $result;
    }

    /** Run post-payment emails (confirmation + invoice + admin). */
    public static function sendPaidOrderEmails(int $orderId, mysqli $conn): void
    {
        try {
            self::sendOrderConfirmation($orderId, $conn);
        } catch (Throwable $e) {
            error_log('EmailService::sendOrderConfirmation: ' . $e->getMessage());
        }
        try {
            self::sendInvoice($orderId, $conn);
        } catch (Throwable $e) {
            error_log('EmailService::sendInvoice: ' . $e->getMessage());
        }
    }
}
