<?php
require_once __DIR__ . '/../includes/config.php';

function apiInput(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '[]', true);
    return is_array($data) ? $data : [];
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
    $image = trim((string) $image);
    return $image !== '' ? $image : 'img/sticker.webp';
}

function apiProductPayload(array $row): array {
    return [
        'id' => (string) $row['id'],
        'name' => $row['name'],
        'description' => $row['description'] ?? '',
        'category' => $row['category'] ?? '',
        'price' => (float) $row['price'],
        'old_price' => isset($row['old_price']) ? (float) $row['old_price'] : null,
        'image' => apiProductImage($row['image'] ?? ''),
        'stock' => isset($row['stock']) ? (int) $row['stock'] : 0,
        'rating' => isset($row['rating']) ? (float) $row['rating'] : 0,
        'available_type' => $row['available_type'] ?? 'physical',
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
