-- Migration 005: Additional email idempotency markers on orders
ALTER TABLE `orders`
    ADD COLUMN IF NOT EXISTS `invoice_email_sent_at` DATETIME NULL AFTER `confirmation_email_sent_at`,
    ADD COLUMN IF NOT EXISTS `shipped_email_sent_at` DATETIME NULL AFTER `invoice_email_sent_at`,
    ADD COLUMN IF NOT EXISTS `delivered_email_sent_at` DATETIME NULL AFTER `shipped_email_sent_at`,
    ADD COLUMN IF NOT EXISTS `payment_failed_email_sent_at` DATETIME NULL AFTER `delivered_email_sent_at`;
