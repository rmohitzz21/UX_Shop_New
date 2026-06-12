<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../includes/marketplace.php';

function drEnsureFreebieResourcesColumn(mysqli $conn): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    if (!tableExists($conn, 'digital_resources')) {
        return;
    }

    addColumnIfMissing(
        $conn,
        'digital_resources',
        'freebie_id',
        "`freebie_id` INT(11) DEFAULT NULL COMMENT 'Set for freebie resources' AFTER `bundle_id`"
    );
    if (!columnExists($conn, 'digital_resources', 'freebie_id')) {
        error_log('drEnsureFreebieResourcesColumn: could not add freebie_id — ' . $conn->error);
        return;
    }

    $idx = $conn->query("SHOW INDEX FROM `digital_resources` WHERE Key_name = 'idx_digital_resources_freebie'");
    if (!$idx || $idx->num_rows === 0) {
        $conn->query('ALTER TABLE `digital_resources` ADD INDEX `idx_digital_resources_freebie` (`freebie_id`)');
    }

    drUpdateOwnerXorConstraint($conn);
}

function drUpdateOwnerXorConstraint(mysqli $conn): void
{
    if (!columnExists($conn, 'digital_resources', 'freebie_id')) {
        return;
    }

    $check = $conn->query(
        "SELECT CHECK_CLAUSE FROM information_schema.CHECK_CONSTRAINTS
         WHERE CONSTRAINT_SCHEMA = DATABASE()
           AND TABLE_NAME = 'digital_resources'
           AND CONSTRAINT_NAME = 'chk_dr_owner_xor'
         LIMIT 1"
    );
    if ($check && ($row = $check->fetch_assoc())) {
        $clause = (string) ($row['CHECK_CLAUSE'] ?? '');
        if (stripos($clause, 'freebie_id') !== false) {
            return;
        }
    }

    $conn->query('ALTER TABLE `digital_resources` DROP CONSTRAINT `chk_dr_owner_xor`');
    $conn->query('ALTER TABLE `digital_resources` DROP CHECK `chk_dr_owner_xor`');
    $conn->query(
        "ALTER TABLE `digital_resources` ADD CONSTRAINT `chk_dr_owner_xor` CHECK (
            (`product_id` IS NOT NULL AND `bundle_id` IS NULL AND (`freebie_id` IS NULL OR `freebie_id` = 0)) OR
            (`bundle_id` IS NOT NULL AND `product_id` IS NULL AND (`freebie_id` IS NULL OR `freebie_id` = 0)) OR
            (`freebie_id` IS NOT NULL AND `freebie_id` > 0 AND `product_id` IS NULL AND `bundle_id` IS NULL)
        )"
    );
}

function drResourceListColumns(mysqli $conn): string
{
    $cols = 'id, product_id, bundle_id, title, resource_type, delivery_mode,
             storage_key, external_url, instructions, download_limit, expiry_days,
             sort_order, is_active';
    if (columnExists($conn, 'digital_resources', 'freebie_id')) {
        $cols = 'id, product_id, bundle_id, freebie_id, title, resource_type, delivery_mode,
                 storage_key, external_url, instructions, download_limit, expiry_days,
                 sort_order, is_active';
    }
    return $cols;
}

function drAllowedResourceTypes(): array
{
    return ['file', 'zip', 'pdf', 'canva', 'figma', 'external_link', 'instructions'];
}

function drDeliveryModeForType(string $type): string
{
    return match ($type) {
        'canva', 'figma', 'external_link' => 'open_link',
        'instructions' => 'instructions',
        default => 'download',
    };
}

function drStorageProviderForType(string $type): string
{
    return match ($type) {
        'canva', 'figma', 'external_link' => 'external',
        default => DigitalStorageService::getDriver() === 'local' ? 'local' : DigitalStorageService::getDriver(),
    };
}

function drValidateExternalUrl(string $url): bool
{
    if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    return str_starts_with(strtolower($url), 'https://');
}

function drAllowedUploadMimes(): array
{
    return [
        'pdf'  => 'application/pdf',
        'zip'  => 'application/zip',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'svg'  => 'image/svg+xml',
    ];
}

function drPublicResourceRow(array $row): array
{
    return [
        'id'             => (int) $row['id'],
        'product_id'     => $row['product_id'] !== null ? (int) $row['product_id'] : null,
        'bundle_id'      => $row['bundle_id'] !== null ? (int) $row['bundle_id'] : null,
        'freebie_id'     => isset($row['freebie_id']) && $row['freebie_id'] !== null ? (int) $row['freebie_id'] : null,
        'title'          => $row['title'],
        'resource_type'  => $row['resource_type'],
        'delivery_mode'  => $row['delivery_mode'],
        'external_url'   => $row['delivery_mode'] === 'open_link' ? (string) ($row['external_url'] ?? '') : '',
        'instructions'   => $row['delivery_mode'] === 'instructions' ? (string) ($row['instructions'] ?? '') : '',
        'download_limit' => (int) $row['download_limit'],
        'expiry_days'    => (int) $row['expiry_days'],
        'sort_order'     => (int) $row['sort_order'],
        'is_active'      => (int) $row['is_active'],
        'has_file'       => !empty($row['storage_key']),
    ];
}
