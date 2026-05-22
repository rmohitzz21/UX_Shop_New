<?php
// includes/EmailService.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $mailer;
    private $logger;

    public function __construct() {
        require_once __DIR__ . '/../vendor/autoload.php';
        
        $this->mailer = new PHPMailer(true);
        $this->logger = new Logger();

        // SMTP configuration
        $this->mailer->isSMTP();
        $this->mailer->Host = getenv('SMTP_HOST') ?: 'mail.uxpacific.com';
        $this->mailer->Port = getenv('SMTP_PORT') ?: 465;
        $this->mailer->SMTPSecure = getenv('SMTP_SECURE') ?: 'ssl';
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = getenv('SMTP_USER') ?: 'support@uxpacific.com';
        $this->mailer->Password = getenv('SMTP_PASS') ?: '';
        
        $this->mailer->setFrom(
            getenv('SMTP_FROM') ?: 'support@uxpacific.com',
            getenv('SMTP_FROM_NAME') ?: 'UX Pacific Shop'
        );

        $this->mailer->isHTML(true);
        $this->mailer->CharSet = 'UTF-8';
    }

    /**
     * Send welcome email to new user
     */
    public function sendWelcomeEmail(string $email, string $name): bool {
        try {
            $this->mailer->addAddress($email, $name);
            $this->mailer->Subject = 'Welcome to UX Pacific Shop!';
            $this->mailer->Body = $this->getWelcomeEmailTemplate($name);
            $this->mailer->AltBody = strip_tags($this->mailer->Body);

            $result = $this->mailer->send();
            $this->logger->log('email', "Welcome email sent to {$email}");
            $this->mailer->clearAddresses();
            return $result;
        } catch (Exception $e) {
            $this->logger->log('email_error', "Failed to send welcome email to {$email}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send order confirmation email to user
     */
    public function sendOrderConfirmationEmail(string $email, string $name, array $orderData): bool {
        try {
            $this->mailer->addAddress($email, $name);
            $this->mailer->Subject = 'Order Confirmation - UX Pacific Shop';
            $this->mailer->Body = $this->getOrderConfirmationTemplate($name, $orderData);
            $this->mailer->AltBody = strip_tags($this->mailer->Body);

            $result = $this->mailer->send();
            $this->logger->log('email', "Order confirmation email sent to {$email}");
            $this->mailer->clearAddresses();
            return $result;
        } catch (Exception $e) {
            $this->logger->log('email_error', "Failed to send order confirmation to {$email}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send contact form submission to admin
     */
    public function sendContactFormToAdmin(array $contactData): bool {
        try {
            $adminEmail = getenv('ADMIN_EMAIL') ?: 'hello@uxpacific.com';
            $this->mailer->addAddress($adminEmail);
            $this->mailer->Subject = "New Contact Form Submission from " . $contactData['name'];
            $this->mailer->Body = $this->getContactFormTemplate($contactData);
            $this->mailer->AltBody = strip_tags($this->mailer->Body);

            $result = $this->mailer->send();
            $this->logger->log('email', "Contact form submitted to admin from {$contactData['email']}");
            $this->mailer->clearAddresses();
            return $result;
        } catch (Exception $e) {
            $this->logger->log('email_error', "Failed to send contact form to admin: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send contact acknowledgment email to user
     */
    public function sendContactAcknowledgmentEmail(string $email, string $name): bool {
        try {
            $this->mailer->addAddress($email, $name);
            $this->mailer->Subject = 'We Received Your Message - UX Pacific Shop';
            $this->mailer->Body = $this->getContactAcknowledgmentTemplate($name);
            $this->mailer->AltBody = strip_tags($this->mailer->Body);

            $result = $this->mailer->send();
            $this->logger->log('email', "Contact acknowledgment email sent to {$email}");
            $this->mailer->clearAddresses();
            return $result;
        } catch (Exception $e) {
            $this->logger->log('email_error', "Failed to send contact acknowledgment to {$email}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Email template: Welcome
     */
    private function getWelcomeEmailTemplate(string $name): string {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #667eea; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background-color: #f9f9f9; padding: 20px; border-radius: 0 0 8px 8px; }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #999; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to UX Pacific Shop!</h1>
        </div>
        <div class="content">
            <p>Hi {$name},</p>
            <p>Thank you for creating an account with us! We're excited to have you join the UX Pacific community.</p>
            <p>With your account, you can:</p>
            <ul>
                <li>Browse and purchase premium design products</li>
                <li>Save your favorite items</li>
                <li>Track your orders in real-time</li>
                <li>Get exclusive updates and new launches</li>
            </ul>
            <p>Ready to explore? Start shopping now!</p>
            <a href="https://uxpacific.com/shopAll.php" class="btn">Shop Now</a>
            <p>If you have any questions or need assistance, feel free to contact our support team.</p>
            <p>Best regards,<br>The UX Pacific Team</p>
        </div>
        <div class="footer">
            <p>&copy; 2026 UX Pacific. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Email template: Order Confirmation
     */
    private function getOrderConfirmationTemplate(string $name, array $orderData): string {
        $items = $orderData['items'] ?? [];
        $total = $orderData['total'] ?? 0;
        $orderId = $orderData['order_id'] ?? 'N/A';
        $orderDate = $orderData['date'] ?? date('Y-m-d');

        $itemsHtml = '';
        foreach ($items as $item) {
            $itemsHtml .= '<tr><td>' . htmlspecialchars($item['name'] ?? '') . '</td><td>&#8377;' . number_format($item['price'] ?? 0, 2) . '</td><td>' . (int) ($item['quantity'] ?? 1) . '</td></tr>';
        }

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #667eea; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background-color: #f9f9f9; padding: 20px; border-radius: 0 0 8px 8px; }
        .order-details { background: white; padding: 15px; margin: 15px 0; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #667eea; color: white; }
        .total { text-align: right; font-size: 18px; font-weight: bold; margin-top: 15px; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #999; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Order Confirmation</h1>
        </div>
        <div class="content">
            <p>Hi {$name},</p>
            <p>Thank you for your order! We're processing it and will ship it soon.</p>
            
            <div class="order-details">
                <h2>Order Details</h2>
                <p><strong>Order ID:</strong> {$orderId}</p>
                <p><strong>Order Date:</strong> {$orderDate}</p>
                
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Price</th>
                            <th>Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$itemsHtml}
                    </tbody>
                </table>
                
                <div class="total">Total: &#8377;{$total}</div>
            </div>
            
            <p><a href="https://uxpacific.com/order-tracking.php?order_id={$orderId}" class="btn">Track Your Order</a></p>
            
            <p>If you have any questions about your order, please contact us at support@uxpacific.com</p>
            <p>Best regards,<br>The UX Pacific Team</p>
        </div>
        <div class="footer">
            <p>&copy; 2026 UX Pacific. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Email template: Contact Form Submission (for admin)
     */
    private function getContactFormTemplate(array $data): string {
        $name = htmlspecialchars((string) ($data['name'] ?? ''), ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars((string) ($data['email'] ?? ''), ENT_QUOTES, 'UTF-8');
        $phone = htmlspecialchars((string) ($data['phone'] ?? ''), ENT_QUOTES, 'UTF-8');
        $subject = htmlspecialchars((string) ($data['subject'] ?? ''), ENT_QUOTES, 'UTF-8');
        $message = nl2br(htmlspecialchars((string) ($data['message'] ?? ''), ENT_QUOTES, 'UTF-8'));
        $submittedAt = htmlspecialchars((string) ($data['timestamp'] ?? date('Y-m-d H:i:s')), ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #667eea; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background-color: #f9f9f9; padding: 20px; border-radius: 0 0 8px 8px; }
        .detail { margin: 10px 0; padding: 10px; background: white; border-left: 3px solid #667eea; }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #999; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>New Contact Form Submission</h1>
        </div>
        <div class="content">
            <div class="detail">
                <strong>Name:</strong><br>
                {$name}
            </div>
            <div class="detail">
                <strong>Email:</strong><br>
                {$email}
            </div>
            <div class="detail">
                <strong>Phone:</strong><br>
                {$phone}
            </div>
            <div class="detail">
                <strong>Subject:</strong><br>
                {$subject}
            </div>
            <div class="detail">
                <strong>Message:</strong><br>
                <p>{$message}</p>
            </div>
            <div class="detail">
                <strong>Submitted at:</strong><br>
                {$submittedAt}
            </div>
        </div>
        <div class="footer">
            <p>This is an automated email from UX Pacific Contact Form</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Email template: Contact Acknowledgment (for user)
     */
    private function getContactAcknowledgmentTemplate(string $name): string {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #667eea; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background-color: #f9f9f9; padding: 20px; border-radius: 0 0 8px 8px; }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #999; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>We Received Your Message</h1>
        </div>
        <div class="content">
            <p>Hi {$name},</p>
            <p>Thank you for reaching out to UX Pacific! We have received your message and will get back to you as soon as possible.</p>
            <p>Our support team typically responds within 24-48 hours during business days.</p>
            <p>In the meantime, if you have any urgent questions, you can always:</p>
            <ul>
                <li>Visit our <a href="https://uxpacific.com">website</a></li>
                <li>Check our FAQ section</li>
                <li>Follow us on social media for updates</li>
            </ul>
            <p>Best regards,<br>The UX Pacific Team</p>
        </div>
        <div class="footer">
            <p>&copy; 2026 UX Pacific. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
