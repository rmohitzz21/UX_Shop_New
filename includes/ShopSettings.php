<?php

function shopDbConn(?mysqli $conn = null): mysqli
{
    if ($conn instanceof mysqli) {
        return $conn;
    }
    global $conn;
    if (!($conn instanceof mysqli)) {
        throw new RuntimeException('Database connection unavailable.');
    }
    return $conn;
}

function shopSettingsEnsureTable(mysqli $conn): void
{
    static $ready = false;
    if ($ready) {
        return;
    }
    $conn->query("CREATE TABLE IF NOT EXISTS shop_settings (
        setting_key VARCHAR(64) NOT NULL PRIMARY KEY,
        setting_value TEXT NULL,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $ready = true;
}

function shopSettingsProductInfoKeyMap(): array
{
    return [
        'section_title' => 'qv_section_title',
        'high_resolution' => 'qv_high_resolution',
        'compatible_software' => 'qv_compatible_software',
        'software_version' => 'qv_software_version',
        'files_included' => 'qv_files_included',
        'grid_columns' => 'qv_grid_columns',
        'layout_type' => 'qv_layout_type',
        'license_type' => 'qv_license_type',
        'info_note' => 'qv_info_note',
        'extra_rows' => 'qv_extra_rows',
    ];
}

function shopSettingsProductInfoDefaults(): array
{
    return [
        'section_title' => 'Product Information',
        'high_resolution' => 'Yes',
        'compatible_software' => 'All UX Pacific resources',
        'software_version' => 'Latest',
        'files_included' => 'FIG, PNG, PDF, SVG',
        'grid_columns' => '12 Column',
        'layout_type' => 'Responsive',
        'license_type' => 'Premium',
        'info_note' => '',
        'extra_rows' => [],
    ];
}

function shopSettingsParseExtraRows(mixed $raw): array
{
    if (is_array($raw)) {
        $rows = $raw;
    } else {
        $decoded = json_decode((string) $raw, true);
        $rows = is_array($decoded) ? $decoded : [];
    }
    $out = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $label = trim((string) ($row['label'] ?? ''));
        $value = trim((string) ($row['value'] ?? ''));
        if ($label === '' || $value === '') {
            continue;
        }
        $out[] = ['label' => $label, 'value' => $value];
    }
    return $out;
}

function shopSettingsGetAll(?mysqli $conn = null): array
{
    $conn = shopDbConn($conn);
    shopSettingsEnsureTable($conn);
    $defaults = shopSettingsProductInfoDefaults();
    $map = shopSettingsProductInfoKeyMap();
    $out = $defaults;

    $keys = array_values($map);
    $placeholders = implode(',', array_fill(0, count($keys), '?'));
    $types = str_repeat('s', count($keys));
    $stmt = $conn->prepare("SELECT setting_key, setting_value FROM shop_settings WHERE setting_key IN ($placeholders)");
    if ($stmt) {
        $stmt->bind_param($types, ...$keys);
        $stmt->execute();
        $res = $stmt->get_result();
        $db = [];
        while ($row = $res->fetch_assoc()) {
            $db[$row['setting_key']] = (string) ($row['setting_value'] ?? '');
        }
        foreach ($map as $field => $key) {
            if (!array_key_exists($key, $db)) {
                continue;
            }
            if ($field === 'extra_rows') {
                $out[$field] = shopSettingsParseExtraRows($db[$key]);
            } else {
                $out[$field] = $db[$key];
            }
        }
    }

    return $out;
}

function shopSettingsSaveProductInfo(array $input, ?mysqli $conn = null): void
{
    $conn = shopDbConn($conn);
    shopSettingsEnsureTable($conn);
    $defaults = shopSettingsProductInfoDefaults();
    $map = shopSettingsProductInfoKeyMap();

    $stmt = $conn->prepare('INSERT INTO shop_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    if (!$stmt) {
        throw new RuntimeException('Could not save shop settings.');
    }

    foreach ($map as $field => $key) {
        if ($field === 'extra_rows') {
            $rows = shopSettingsParseExtraRows($input['extra_rows'] ?? []);
            $value = json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            $value = trim((string) ($input[$field] ?? $defaults[$field] ?? ''));
            if ($value === '' && $field !== 'info_note') {
                $value = (string) ($defaults[$field] ?? '');
            }
        }
        $stmt->bind_param('ss', $key, $value);
        $stmt->execute();
    }
}

function shopSettingsApplyProductInfo(array $specs, ?mysqli $conn = null): array
{
    $global = shopSettingsGetAll($conn);
    $specs['global_info_note'] = $global['info_note'] ?? '';
    $specs['product_info_section_title'] = $global['section_title'] ?: 'Product Information';
    $specs['global_extra_rows'] = is_array($global['extra_rows']) ? $global['extra_rows'] : [];

    return $specs;
}
