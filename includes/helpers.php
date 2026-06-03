<?php
// includes/helpers.php

function sendResponse($status, $message, $data = null, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');

    $response = [
        "status"  => $status,
        "message" => $message
    ];

    if ($data !== null) {
        $response["data"] = $data;
    }

    echo json_encode($response);
    exit;
}

function requireUserAuth() {
    if (empty($_SESSION['user_id'])) {
        sendResponse("error", "Unauthorized: Login required", null, 401);
    }
}

function requireAdmin() {
    if (empty($_SESSION['admin_id'])) {
        sendResponse("error", "Unauthorized: Admin access required", null, 401);
    }
}

function requireAuth() {
    requireUserAuth();
}

function validateCsrf() {
    if (empty($_SESSION['csrf_token'])) {
        sendResponse("error", "Session expired", null, 403);
    }

    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if (empty($token)) {
        $body = json_decode(file_get_contents('php://input'), true);
        $token = $body['csrf_token'] ?? '';
    }

    if (!$token || !hash_equals($_SESSION['csrf_token'], $token)) {
        sendResponse("error", "Invalid CSRF token", null, 403);
    }
}

function isAdmin() {
    return !empty($_SESSION['admin_id']);
}

/* ================= EMAIL SYSTEM ================= */

function sendWelcomeEmail($email, $name) {
    require_once __DIR__ . '/EmailService.php';
    try {
        return EmailService::sendWelcome(['email' => $email, 'first_name' => $name]);
    } catch (Throwable $e) {
        error_log('sendWelcomeEmail: ' . $e->getMessage());
        return false;
    }
}

function sendOrderConfirmationEmail($email, $name, $orderData) {
    // Legacy wrapper — prefer EmailService::sendOrderConfirmation($orderId, $conn) when order id is known.
    try {
        require_once __DIR__ . '/../core/Mailer.php';
        $mailer = new Mailer();
        $html = getOrderConfirmationTemplate($name, $orderData);
        return $mailer->send($email, 'Order Confirmation — UX Pacific', $html);
    } catch (Throwable $e) {
        error_log('sendOrderConfirmationEmail: ' . $e->getMessage());
        return false;
    }
}

function sendContactEmail($data) {
    require_once __DIR__ . '/EmailService.php';
    try {
        $adminSent = EmailService::sendContactFormNotification(
            (string) ($data['name'] ?? ''),
            (string) ($data['email'] ?? ''),
            (string) ($data['subject'] ?? 'General enquiry'),
            (string) ($data['message'] ?? ''),
            (string) ($data['phone'] ?? '')
        );
        $userSent = false;
        if (!empty($data['email']) && filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $userSent = EmailService::sendContactConfirmation(
                (string) ($data['name'] ?? 'Customer'),
                (string) $data['email']
            );
        }
        return $adminSent || $userSent;
    } catch (Throwable $e) {
        error_log('sendContactEmail: ' . $e->getMessage());
        return false;
    }
}

/* ================= TEMPLATES ================= */

function buildEmailLayout($title, $preheader, $contentHtml) {
    $safeTitle = htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8');
    $safePreheader = htmlspecialchars((string) $preheader, ENT_QUOTES, 'UTF-8');

    return <<<HTML
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$safeTitle}</title>
  <style>
    body { margin: 0; padding: 0; background: #f4f6fb; font-family: Arial, sans-serif; color: #111827; }
    .wrap { width: 100%; background: #f4f6fb; padding: 24px 0; }
    .container { max-width: 620px; margin: 0 auto; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; }
    .header { background: #111827; color: #ffffff; padding: 20px 24px; }
    .brand { margin: 0; font-size: 18px; letter-spacing: 0.3px; }
    .title { margin: 6px 0 0 0; font-size: 22px; line-height: 1.3; }
    .content { padding: 24px; font-size: 15px; line-height: 1.65; color: #1f2937; }
    .content p { margin: 0 0 14px; }
    .muted { color: #6b7280; font-size: 13px; }
    .panel { border: 1px solid #e5e7eb; border-radius: 8px; padding: 14px; background: #f9fafb; margin: 14px 0; }
    .footer { border-top: 1px solid #e5e7eb; background: #fafafa; padding: 14px 24px; color: #6b7280; font-size: 12px; line-height: 1.6; }
    table { width: 100%; border-collapse: collapse; margin: 14px 0; }
    th, td { text-align: left; padding: 10px; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
    th { background: #f3f4f6; color: #111827; font-weight: 600; }
    .right { text-align: right; }
  </style>
</head>
<body>
  <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">{$safePreheader}</div>
  <div class="wrap">
    <div class="container">
      <div class="header">
        <p class="brand">UX Pacific Shop</p>
        <h1 class="title">{$safeTitle}</h1>
      </div>
      <div class="content">
        {$contentHtml}
      </div>
      <div class="footer">
        <div>UX Pacific Shop</div>
        <div>If you were not expecting this email, you can safely ignore it.</div>
        <div>Email: support@uxpacific.com</div>
      </div>
    </div>
  </div>
</body>
</html>
HTML;
}

function getWelcomeEmailTemplate($name) {
    $safeName = htmlspecialchars(trim((string) $name), ENT_QUOTES, 'UTF-8');
    if ($safeName === '') {
        $safeName = 'Customer';
    }

    $content = <<<HTML
<p>Hello {$safeName},</p>
<p>Thank you for creating your account with UX Pacific Shop.</p>
<div class="panel">
  <p style="margin:0;"><strong>Your account is now active.</strong> You can sign in and manage your orders anytime.</p>
</div>
<p class="muted">Need help? Reply to this email and our team will assist you.</p>
HTML;

    return buildEmailLayout(
        'Welcome to UX Pacific Shop',
        'Your UX Pacific account has been created.',
        $content
    );
}

function getOrderConfirmationTemplate($name, $orderData) {
    $safeName = htmlspecialchars(trim((string) $name), ENT_QUOTES, 'UTF-8');
    if ($safeName === '') {
        $safeName = 'Customer';
    }
    $items = $orderData['items'] ?? [];
    $total = $orderData['total'] ?? 0;
    $orderNumber = htmlspecialchars((string) ($orderData['order_number'] ?? ($orderData['order_id'] ?? 'N/A')), ENT_QUOTES, 'UTF-8');
    $orderDate = htmlspecialchars((string) ($orderData['date'] ?? date('Y-m-d')), ENT_QUOTES, 'UTF-8');

    $itemsHtml = '';
    foreach ($items as $item) {
        $itemName = htmlspecialchars((string) ($item['name'] ?? 'Item'), ENT_QUOTES, 'UTF-8');
        $itemQty = (int) ($item['quantity'] ?? 1);
        $itemPrice = number_format((float) ($item['price'] ?? 0), 2);
        $itemsHtml .= "<tr><td>{$itemName}</td><td>{$itemQty}</td><td class=\"right\">INR {$itemPrice}</td></tr>";
    }
    if ($itemsHtml === '') {
        $itemsHtml = '<tr><td colspan="3" class="muted">No line items available</td></tr>';
    }
    $totalFormatted = number_format((float) $total, 2);

    $ordersUrl   = htmlspecialchars((string) ($orderData['orders_url'] ?? ''), ENT_QUOTES, 'UTF-8');
    $ordersLinkHtml = $ordersUrl !== ''
        ? "<p style=\"margin:14px 0 0;\"><a href=\"{$ordersUrl}\" style=\"display:inline-block;padding:11px 26px;background:#7c5dfa;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;font-size:14px;\">View My Orders &amp; Downloads</a></p>"
        : '';

    $content = <<<HTML
<p>Hello {$safeName},</p>
<p>Thank you for your order. Your digital files are ready — access them any time from your orders page.</p>
<div class="panel">
  <p style="margin:0 0 8px;"><strong>Order number:</strong> {$orderNumber}</p>
  <p style="margin:0;"><strong>Order date:</strong> {$orderDate}</p>
</div>
<table>
  <thead>
    <tr>
      <th>Item</th>
      <th>Qty</th>
      <th class="right">Price</th>
    </tr>
  </thead>
  <tbody>
    {$itemsHtml}
  </tbody>
</table>
<p><strong>Total: INR {$totalFormatted}</strong></p>
{$ordersLinkHtml}
<p class="muted" style="margin-top:14px;">Please keep this email for your records. Downloads are available from your orders page.</p>
HTML;

    return buildEmailLayout(
        'Order Confirmation',
        'Your UX Pacific Shop order has been received.',
        $content
    );
}

function getContactFormTemplate($data) {
    $safeName = htmlspecialchars((string) ($data['name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $safeEmail = htmlspecialchars((string) ($data['email'] ?? ''), ENT_QUOTES, 'UTF-8');
    $safeSubject = htmlspecialchars((string) ($data['subject'] ?? 'General enquiry'), ENT_QUOTES, 'UTF-8');
    $safePhone = htmlspecialchars((string) ($data['phone'] ?? '-'), ENT_QUOTES, 'UTF-8');
    $safeMessage = nl2br(htmlspecialchars((string) ($data['message'] ?? ''), ENT_QUOTES, 'UTF-8'));

    $content = <<<HTML
<p>A new contact request was submitted on the website.</p>
<div class="panel">
  <p style="margin:0 0 6px;"><strong>Name:</strong> {$safeName}</p>
  <p style="margin:0 0 6px;"><strong>Email:</strong> {$safeEmail}</p>
  <p style="margin:0 0 6px;"><strong>Phone:</strong> {$safePhone}</p>
  <p style="margin:0;"><strong>Subject:</strong> {$safeSubject}</p>
</div>
<p><strong>Message:</strong></p>
<p>{$safeMessage}</p>
HTML;

    return buildEmailLayout(
        'New Contact Form Submission',
        'A new contact request was submitted on UX Pacific Shop.',
        $content
    );
}

function getContactAcknowledgmentTemplate($name) {
    $safeName = htmlspecialchars(trim((string) $name), ENT_QUOTES, 'UTF-8');
    if ($safeName === '') {
        $safeName = 'Customer';
    }

    $content = <<<HTML
<p>Hello {$safeName},</p>
<p>Thank you for contacting UX Pacific Shop. We have received your message.</p>
<p>Our team will review your request and reply as soon as possible.</p>
<p class="muted">For urgent matters, you can reply directly to this email.</p>
HTML;

    return buildEmailLayout(
        'We Received Your Message',
        'Your message was received by UX Pacific Shop.',
        $content
    );
}

function validateCsrfFromToken(string $token): void {
    if (empty($_SESSION['csrf_token']) || !$token || !hash_equals($_SESSION['csrf_token'], $token)) {
        sendResponse("error", "Invalid CSRF token", null, 403);
    }
}

/* ================= PASSWORD RESET EMAIL ================= */

function sendPasswordResetEmail(string $email, string $name, string $resetLink): bool {
    require_once __DIR__ . '/EmailService.php';
    try {
        $token = '';
        if (preg_match('/[?&]token=([^&]+)/', $resetLink, $m)) {
            $token = urldecode($m[1]);
        }
        return EmailService::sendPasswordReset(
            ['email' => $email, 'first_name' => $name],
            $token
        );
    } catch (Throwable $e) {
        error_log('sendPasswordResetEmail: ' . $e->getMessage());
        return false;
    }
}

function getPasswordResetEmailTemplate(string $name, string $resetLink): string {
    $safeName = htmlspecialchars(trim($name) ?: 'Customer', ENT_QUOTES, 'UTF-8');
    $safeLink = htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8');

    $content = <<<HTML
<p>Hello {$safeName},</p>
<p>We received a request to reset your password for your UX Pacific Shop account.</p>
<div class="panel">
  <p style="margin:0 0 12px;">Click the button below to reset your password. This link expires in <strong>1 hour</strong>.</p>
  <p style="margin:0;"><a href="{$safeLink}" style="display:inline-block;padding:12px 28px;background:#6d3dff;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;font-size:15px;">Reset Password</a></p>
</div>
<p>If the button does not work, copy and paste this link into your browser:</p>
<p style="word-break:break-all;">{$safeLink}</p>
<p class="muted">If you did not request a password reset, you can safely ignore this email. Your password will not change.</p>
HTML;

    return buildEmailLayout(
        'Reset Your Password',
        'Reset your UX Pacific Shop password — link expires in 1 hour.',
        $content
    );
}
