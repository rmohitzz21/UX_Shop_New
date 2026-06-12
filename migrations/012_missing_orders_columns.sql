-- Migration 012: Add missing orders columns
-- Adds email-tracking columns from migration 005 (in case not applied)
-- and admin_notified_at which was added to code without a migration.
-- Safe on MySQL 5.7 (no IF NOT EXISTS needed; use ALTER TABLE per column).

ALTER TABLE `orders`
    ADD COLUMN IF NOT EXISTS `invoice_email_sent_at`       DATETIME NULL AFTER `confirmation_email_sent_at`,
    ADD COLUMN IF NOT EXISTS `shipped_email_sent_at`        DATETIME NULL AFTER `invoice_email_sent_at`,
    ADD COLUMN IF NOT EXISTS `delivered_email_sent_at`      DATETIME NULL AFTER `shipped_email_sent_at`,
    ADD COLUMN IF NOT EXISTS `payment_failed_email_sent_at` DATETIME NULL AFTER `delivered_email_sent_at`,
    ADD COLUMN IF NOT EXISTS `admin_notified_at`            DATETIME NULL AFTER `payment_failed_email_sent_at`;
