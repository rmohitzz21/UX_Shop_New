<?php
require_once __DIR__ . '/../includes/config.php';

function apiInput(): array {
    return apiReadJsonBody();
}

function apiUser(): ?array {
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    return [
        'id' => (int) $_SESSION['user_id'],
        'email' => $_SESSION['user_email'] ?? '',
        'firstName' => $_SESSION['first_name'] ?? '',
        'lastName' => $_SESSION['last_name'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'customer',
    ];
}

function apiRequireUser(): array {
    $user = apiUser();
    if (!$user) {
        sendResponse('error', 'Please sign in first.', null, 401);
    }
    return $user;
}

function apiRequirePost(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse('error', 'Method not allowed.', null, 405);
    }
}

function apiProductImage(?string $image): string {
    return resolvePublicImagePath($image, 'img/sticker.webp');
}

function apiProductPayload(array $row): array {
    $isFree = !empty($row['is_free']) || (float) ($row['price'] ?? 0) <= 0;
    return [
        'id' => (string) $row['id'],
        'name' => $row['name'],
        'description' => $row['description'] ?? '',
        'category' => $row['category'] ?? '',
        'is_free' => $isFree,
        'price' => $isFree ? 0.0 : (float) $row['price'],
        'old_price' => isset($row['old_price']) ? (float) $row['old_price'] : null,
        'image' => apiProductImage($row['image'] ?? ''),
        'stock' => isset($row['stock']) ? (int) $row['stock'] : 0,
        'rating' => isset($row['rating']) ? (float) $row['rating'] : 0,
        'available_type' => $row['available_type'] ?? 'physical',
    ];
}

function apiNormalizeCartItemType(string $raw): string
{
    $raw = strtolower(trim($raw));
    return in_array($raw, ['product', 'bundle', 'freebie'], true) ? $raw : 'product';
}

/**
 * Free items (price = 0) are limited to one per account, lifetime.
 * Returns true if the user has already received this product_id in any
 * non-cancelled order at price 0.
 */
function apiUserAlreadyOwnsFreeItem(mysqli $conn, int $userId, int $productId): bool
{
    if ($userId <= 0 || $productId <= 0) return false;
    $stmt = $conn->prepare(
        'SELECT 1 FROM order_items oi
         JOIN orders o ON o.id = oi.order_id
         WHERE o.user_id = ?
           AND oi.product_id = ?
           AND oi.price = 0
           AND (o.status IS NULL OR o.status NOT IN ("cancelled", "failed", "refunded"))
         LIMIT 1'
    );
    if (!$stmt) return false;
    $stmt->bind_param('ii', $userId, $productId);
    $stmt->execute();
    return (bool) $stmt->get_result()->fetch_assoc();
}

function apiFreebiePayload(array $row): array
{
    return [
        'id' => (string) $row['id'],
        'name' => $row['name'],
        'description' => $row['description'] ?? '',
        'category' => $row['category'] ?? 'General',
        'price' => 0.0,
        'old_price' => null,
        'image' => apiProductImage($row['image'] ?? ''),
        'stock' => 999,
        'rating' => 4.5,
        'available_type' => 'digital',
        'type' => 'freebie',
        'file_url' => $row['file_url'] ?? '',
        'download_count' => (int) ($row['download_count'] ?? 0),
        'is_featured' => !empty($row['is_featured']),
    ];
}

function apiEnsureProduct(mysqli $conn, array $data): int {
    $productId = $data['product_id'] ?? $data['id'] ?? null;

    if (is_numeric($productId) && (int) $productId > 0) {
        $id   = (int) $productId;
        $stmt = $conn->prepare('SELECT id FROM products WHERE id = ? AND is_active = 1 LIMIT 1');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            return $id;
        }
    }

    sendResponse('error', 'Product not found or unavailable.', null, 404);
}
