<?php
// includes/DigitalDownloadService.php

class DigitalDownloadService
{
    /**
     * Create download tokens for every digital item in a paid order.
     * Idempotent: uses INSERT IGNORE so duplicate calls are safe.
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
            SELECT oi.id              AS order_item_id,
                   oi.item_type,
                   oi.product_id,
                   oi.bundle_id,
                   COALESCE(oi.product_name, p.name, b.name, 'Item') AS item_name,
                   COALESCE(p.digital_file_path,    b.digital_file_path,    '') AS file_path,
                   COALESCE(p.download_limit,       b.download_limit,       5)  AS dl_limit,
                   COALESCE(p.download_expiry_days, b.download_expiry_days, 30) AS expiry_days,
                   COALESCE(p.available_type,       b.available_type,       'physical') AS available_type
            FROM order_items oi
            LEFT JOIN products p ON p.id = oi.product_id AND oi.item_type = 'product'
            LEFT JOIN bundles  b ON b.id = oi.bundle_id  AND oi.item_type = 'bundle'
            WHERE oi.order_id = ?
        ");
        // Columns may not exist before migration runs
        if ($iStmt === false) {
            return;
        }
        $iStmt->bind_param('i', $orderId);
        $iStmt->execute();
        $items = $iStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $ins = $conn->prepare("
            INSERT IGNORE INTO digital_downloads
                (order_id, order_item_id, user_id, product_id, bundle_id,
                 token, download_limit, expires_at, file_path, item_name)
            VALUES (?, ?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? DAY), ?, ?)
        ");
        if ($ins === false) {
            return;
        }

        foreach ($items as $item) {
            // Skip physical-only items — they need no download token
            if (!in_array($item['available_type'], ['digital', 'both'], true)) {
                continue;
            }

            $token       = bin2hex(random_bytes(32));
            $dlLimit     = max(1, (int) $item['dl_limit']);
            $expiryDays  = max(1, (int) $item['expiry_days']);
            $productId   = $item['item_type'] === 'product' ? (int) $item['product_id'] : null;
            $bundleId    = $item['item_type'] === 'bundle'  ? (int) $item['bundle_id']  : null;
            $filePath    = (string) $item['file_path'];
            $itemName    = (string) $item['item_name'];
            $orderItemId = (int)    $item['order_item_id'];

            $ins->bind_param(
                'iiiiisiiss',
                $orderId, $orderItemId, $userId, $productId, $bundleId,
                $token, $dlLimit, $expiryDays, $filePath, $itemName
            );
            $ins->execute();
        }
    }

    /**
     * Return download records owned by $userId for a given order.
     */
    public static function getDownloadsForOrder(int $orderId, int $userId, mysqli $conn): array
    {
        $stmt = $conn->prepare("
            SELECT id, token, item_name, download_count, download_limit,
                   expires_at, file_path,
                   (expires_at > NOW() AND download_count < download_limit) AS is_available
            FROM digital_downloads
            WHERE order_id = ? AND user_id = ?
            ORDER BY id ASC
        ");
        if ($stmt === false) {
            return [];
        }
        $stmt->bind_param('ii', $orderId, $userId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        return array_map(static function (array $row): array {
            return [
                'id'             => (int)  $row['id'],
                'token'          => $row['token'],
                'item_name'      => $row['item_name'],
                'download_count' => (int)  $row['download_count'],
                'download_limit' => (int)  $row['download_limit'],
                'expires_at'     => $row['expires_at'],
                'has_file'       => $row['file_path'] !== '',
                'is_available'   => (bool) $row['is_available'],
            ];
        }, $rows);
    }

    /**
     * Validate token ownership / expiry / limit, then stream the file.
     * Always exits — never returns.
     */
    public static function validateAndServe(string $token, int $userId, mysqli $conn): void
    {
        $conn->begin_transaction();
        $stmt = $conn->prepare("
            SELECT id, user_id, file_path, download_count, download_limit, expires_at, item_name
            FROM digital_downloads
            WHERE token = ?
            LIMIT 1
            FOR UPDATE
        ");
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if (!$row) {
            $conn->rollback();
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Download link not found.']);
            exit;
        }
        if ((int) $row['user_id'] !== $userId) {
            $conn->rollback();
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Access denied.']);
            exit;
        }
        if (strtotime($row['expires_at']) < time()) {
            $conn->rollback();
            http_response_code(410);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'This download link has expired.']);
            exit;
        }
        if ((int) $row['download_count'] >= (int) $row['download_limit']) {
            $conn->rollback();
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Download limit reached for this item.']);
            exit;
        }

        $filePath = (string) $row['file_path'];
        $absPath  = self::resolveFilePath($filePath);

        if ($filePath === '' || !is_file($absPath)) {
            $conn->rollback();
            http_response_code(503);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'File not yet available. Please contact support.']);
            exit;
        }

        $upd = $conn->prepare('UPDATE digital_downloads SET download_count = download_count + 1 WHERE id = ?');
        $upd->bind_param('i', $row['id']);
        $upd->execute();
        $conn->commit();

        $filename = basename($absPath);
        header('Content-Type: ' . self::getMime($absPath));
        header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
        header('Content-Length: ' . filesize($absPath));
        header('Cache-Control: private, no-store');
        header('X-Content-Type-Options: nosniff');
        readfile($absPath);
        exit;
    }

    private static function resolveFilePath(string $path): string
    {
        if ($path === '') {
            return '';
        }
        // Absolute path (Unix or Windows)
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
