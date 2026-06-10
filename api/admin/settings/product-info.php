<?php
require_once __DIR__ . '/../_admin.php';
require_once dirname(__DIR__, 3) . '/includes/ShopSettings.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    sendResponse('success', 'Quick view product info loaded.', shopSettingsGetAll($conn));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', 'Method not allowed.', null, 405);
}

validateCsrf();
$input = adminInput();

try {
    shopSettingsSaveProductInfo($input, $conn);
    sendResponse('success', 'Quick view product info saved.', shopSettingsGetAll($conn));
} catch (Throwable $e) {
    sendResponse('error', $e->getMessage(), null, 500);
}
