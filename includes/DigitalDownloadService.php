<?php
declare(strict_types=1);

require_once __DIR__ . '/DigitalStorageService.php';

class DigitalDownloadService
{
    /**
     * Create download tokens for every digital resource in a paid order.
     * Idempotent: INSERT IGNORE on (order_item_id, resource_id).
     */
    public static function generateDownloadsForOrder(int $orderId, mysqli $conn): void
    {
        $oStmt = $conn->prepare('SELECT user_id FROM orders WHERE id = ? LIMIT 1');
        $oStmt->bind_param('i', $orderId);
        $oStmt->execute();
        $orderRow = $oStmt->get_result()->fetch_assoc();
        if (!$orderRow) {
            return;
        }
        $userId = (int) $orderRow['user_id'];

        $iStmt = $conn->prepare("
            SELECT oi.id AS order_item_id, oi.item_type, oi.product_id, oi.bundle_id, oi.selected_format,
                   COALESCE(oi.product_name, p.name, b.name, f.name, 'Item') AS item_name,
                   COALESCE(p.digital_file_path, b.digital_file_path, f.file_url, '') AS legacy_file_path,
                   COALESCE(p.download_limit, b.download_limit, 5) AS dl_limit,
                   COALESCE(p.download_expiry_days, b.download_expiry_days, 30) AS expiry_days,
                   COALESCE(p.available_type, 'digital') AS available_type
            FROM order_items oi
            LEFT JOIN products p ON p.id = oi.product_id AND oi.item_type = 'product'
            LEFT JOIN bundles  b ON b.id = oi.bundle_id  AND oi.item_type = 'bundle'
            LEFT JOIN freebies f ON f.id = oi.product_id AND oi.item_type = 'freebie'
            WHERE oi.order_id = ?
        ");
        if ($iStmt === false) {
            return;
        }
        $iStmt->bind_param('i', $orderId);
        $iStmt->execute();
        $items = $iStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $ins = $conn->prepare("
            INSERT IGNORE INTO digital_downloads
                (order_id, order_item_id, resource_id, user_id, product_id, bundle_id,
                 token, download_limit, expires_at, file_path, item_name)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? DAY), ?, ?)
        ");
        if ($ins === false) {
            return;
        }

        $resStmt = $conn->prepare("
            SELECT id, title, download_limit, expiry_days
            FROM digital_resources
            WHERE is_active = 1
              AND ((product_id = ? AND ? IS NOT NULL) OR (bundle_id = ? AND ? IS NOT NULL))
            ORDER BY sort_order ASC, id ASC
        ");

        foreach ($items as $item) {
            if (($item['selected_format'] ?? 'digital') !== 'digital') {
                continue;
            }

            $orderItemId = (int) $item['order_item_id'];
            $productId   = in_array($item['item_type'], ['product', 'freebie'], true)
                ? (int) $item['product_id'] : null;
            $bundleId    = $item['item_type'] === 'bundle' ? (int) $item['bundle_id'] : null;
            $itemName    = (string) $item['item_name'];
            $resources   = [];

            if ($resStmt !== false) {
                $resStmt->bind_param('iiii', $productId, $productId, $bundleId, $bundleId);
                $resStmt->execute();
                $resources = $resStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            }

            if ($resources !== []) {
                foreach ($resources as $resource) {
                    $token      = bin2hex(random_bytes(32));
                    $dlLimit    = max(1, (int) $resource['download_limit']);
                    $expiryDays = max(1, (int) $resource['expiry_days']);
                    $resourceId = (int) $resource['id'];
                    $label      = (string) ($resource['title'] ?: $itemName);
                    $legacyPath = '';

                    $pid = $productId;
                    $bid = $bundleId;
                    $ins->bind_param(
                        'iiiiiisiiss',
                        $orderId,
                        $orderItemId,
                        $resourceId,
                        $userId,
                        $pid,
                        $bid,
                        $token,
                        $dlLimit,
                        $expiryDays,
                        $legacyPath,
                        $label
                    );
                    $ins->execute();
                }
                continue;
            }

            $legacyPath = trim((string) $item['legacy_file_path']);
            if ($legacyPath === '') {
                continue;
            }

            $token      = bin2hex(random_bytes(32));
            $dlLimit    = max(1, (int) $item['dl_limit']);
            $expiryDays = max(1, (int) $item['expiry_days']);
            $resourceId = 0;

            $pid = $productId;
            $bid = $bundleId;
            $ins->bind_param(
                'iiiiiisiiss',
                $orderId,
                $orderItemId,
                $resourceId,
                $userId,
                $pid,
                $bid,
                $token,
                $dlLimit,
                $expiryDays,
                $legacyPath,
                $itemName
            );
            $ins->execute();
        }
    }

    public static function getDownloadsForOrder(int $orderId, int $userId, mysqli $conn): array
    {
        $stmt = $conn->prepare("
            SELECT dd.id, dd.token, dd.item_name, dd.download_count, dd.download_limit,
                   dd.expires_at, dd.file_path, dd.resource_id,
                   dr.resource_type, dr.delivery_mode,
                   (dd.expires_at > NOW() AND dd.download_count < dd.download_limit) AS is_available
            FROM digital_downloads dd
            LEFT JOIN digital_resources dr ON dr.id = dd.resource_id AND dd.resource_id > 0
            WHERE dd.order_id = ?
              AND dd.user_id = ?
              AND (dd.resource_id = 0 OR dr.is_active = 1)
            ORDER BY dd.id ASC
        ");
        if ($stmt === false) {
            return [];
        }
        $stmt->bind_param('ii', $orderId, $userId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        return array_map(static function (array $row): array {
            $mode = (string) ($row['delivery_mode'] ?? 'download');
            $hasFile = (int) $row['resource_id'] > 0 || $row['file_path'] !== '';
            $label = match ($mode) {
                'open_link' => 'Open',
                'instructions' => 'View instructions',
                default => 'Download',
            };
            return [
                'id'             => (int)  $row['id'],
                'token'          => $row['token'],
                'item_name'      => $row['item_name'],
                'resource_type'  => $row['resource_type'] ?? 'file',
                'delivery_mode'  => $mode,
                'action_label'   => $label,
                'download_count' => (int)  $row['download_count'],
                'download_limit' => (int)  $row['download_limit'],
                'expires_at'     => $row['expires_at'],
                'has_file'       => $hasFile,
                'is_available'   => (bool) $row['is_available'],
            ];
        }, $rows);
    }

    public static function validateAndServe(string $token, int $userId, mysqli $conn): void
    {
        $conn->begin_transaction();
        $stmt = $conn->prepare("
            SELECT dd.id, dd.user_id, dd.file_path, dd.download_count, dd.download_limit,
                   dd.expires_at, dd.item_name, dd.resource_id, dd.order_id,
                   o.payment_status,
                   dr.delivery_mode, dr.storage_key, dr.external_url, dr.instructions, dr.is_active
            FROM digital_downloads dd
            INNER JOIN orders o ON o.id = dd.order_id
            LEFT JOIN digital_resources dr ON dr.id = dd.resource_id AND dd.resource_id > 0
            WHERE dd.token = ?
            LIMIT 1
            FOR UPDATE
        ");
        if ($stmt === false) {
            $conn->rollback();
            self::jsonError(503, 'Download service unavailable.');
        }
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if (!$row) {
            $conn->rollback();
            self::jsonError(404, 'Download link not found.');
        }
        if ((int) $row['user_id'] !== $userId) {
            $conn->rollback();
            self::jsonError(403, 'Access denied.');
        }
        if ((int) $row['resource_id'] > 0 && (int) ($row['is_active'] ?? 0) !== 1) {
            $conn->rollback();
            self::jsonError(404, 'This resource is no longer available.');
        }
        if (strtolower((string) ($row['payment_status'] ?? 'pending')) !== 'paid') {
            $conn->rollback();
            self::jsonError(403, 'Order not paid.');
        }
        if (strtotime((string) $row['expires_at']) < time()) {
            $conn->rollback();
            self::jsonError(410, 'This download link has expired.');
        }
        if ((int) $row['download_count'] >= (int) $row['download_limit']) {
            $conn->rollback();
            self::jsonError(429, 'Download limit reached for this item.');
        }

        $upd = $conn->prepare('UPDATE digital_downloads SET download_count = download_count + 1 WHERE id = ?');
        $upd->bind_param('i', $row['id']);
        $upd->execute();
        $conn->commit();

        $resourceId = (int) $row['resource_id'];
        if ($resourceId > 0) {
            $mode = (string) ($row['delivery_mode'] ?? 'download');
            if ($mode === 'open_link') {
                $url = trim((string) ($row['external_url'] ?? ''));
                if ($url === '' || !str_starts_with(strtolower($url), 'https://')) {
                    self::jsonError(503, 'Link not available.');
                }
                header('Location: ' . $url);
                exit;
            }
            if ($mode === 'instructions') {
                header('Content-Type: text/html; charset=UTF-8');
                echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Instructions</title></head><body>';
                echo '<h1>' . htmlspecialchars((string) $row['item_name'], ENT_QUOTES, 'UTF-8') . '</h1>';
                echo '<div>' . nl2br(htmlspecialchars((string) ($row['instructions'] ?? ''), ENT_QUOTES, 'UTF-8')) . '</div>';
                echo '</body></html>';
                exit;
            }

            $storageKey = trim((string) ($row['storage_key'] ?? ''));
            if ($storageKey !== '') {
                $signed = DigitalStorageService::getSignedUrl($storageKey);
                if ($signed !== null) {
                    header('Location: ' . $signed);
                    exit;
                }
                DigitalStorageService::streamLocal($storageKey);
            }
            self::jsonError(503, 'File not yet available. Please contact support.');
        }

        $filePath = trim((string) $row['file_path']);
        if ($filePath !== '' && preg_match('/^https?:\/\//i', $filePath)) {
            header('Location: ' . $filePath);
            exit;
        }

        $absPath = self::resolveFilePath($filePath);
        if ($filePath === '' || !is_file($absPath)) {
            self::jsonError(503, 'File not yet available. Please contact support.');
        }

        $filename = basename($absPath);
        header('Content-Type: ' . self::getMime($absPath));
        header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
        header('Content-Length: ' . (string) filesize($absPath));
        header('Cache-Control: private, no-store');
        header('X-Content-Type-Options: nosniff');
        readfile($absPath);
        exit;
    }

    private static function jsonError(int $code, string $message): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $message]);
        exit;
    }

    private static function resolveFilePath(string $path): string
    {
        if ($path === '') {
            return '';
        }
        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path)) {
            return $path;
        }
        $docRoot = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
        return $docRoot . '/' . ltrim($path, '/\\');
    }

    private static function getMime(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match ($ext) {
            'pdf'          => 'application/pdf',
            'zip'          => 'application/zip',
            'sketch'       => 'application/zip',
            'fig', 'xd'    => 'application/octet-stream',
            'png'          => 'image/png',
            'jpg', 'jpeg'  => 'image/jpeg',
            'svg'          => 'image/svg+xml',
            'webp'         => 'image/webp',
            default        => 'application/octet-stream',
        };
    }
}
