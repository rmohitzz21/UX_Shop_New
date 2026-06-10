<?php
declare(strict_types=1);

function drEnsureFreebieResourcesColumn(mysqli $conn): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    $conn->query(
        "ALTER TABLE digital_resources
         ADD COLUMN IF NOT EXISTS freebie_id INT(11) DEFAULT NULL COMMENT 'Set for freebie resources' AFTER bundle_id"
    );
    $conn->query(
        'ALTER TABLE digital_resources ADD INDEX IF NOT EXISTS idx_digital_resources_freebie (freebie_id)'
    );
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
