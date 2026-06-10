-- Link digital_resources to standalone freebies (secure local/cloud delivery).
-- Run: mysql -u root uxmerchandise < migrations/009_freebie_digital_resources.sql

ALTER TABLE `digital_resources`
    ADD COLUMN IF NOT EXISTS `freebie_id` INT(11) DEFAULT NULL COMMENT 'Set for freebie resources' AFTER `bundle_id`;

ALTER TABLE `digital_resources`
    ADD INDEX IF NOT EXISTS `idx_digital_resources_freebie` (`freebie_id`);
