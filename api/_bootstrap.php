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
        $id = (int) $productId;
        $stmt = $conn->prepare('SELECT id FROM products WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            return $id;
        }
    }

    $details = $data['details'] ?? $data['product'] ?? [];
    $name = trim((string) ($details['name'] ?? $data['name'] ?? 'UX Pacific Bundle'));
    $category = trim((string) ($details['category'] ?? $data['category'] ?? 'Bundles'));
    $description = trim((string) ($details['description'] ?? $data['description'] ?? 'Curated UX Pacific product.'));
    $price = (float) ($details['price'] ?? $data['price'] ?? 0);
    $image = apiProductImage($details['image'] ?? $data['image'] ?? '');
    $availableType = trim((string) ($data['available_type'] ?? $details['available_type'] ?? 'digital'));
    if (!in_array($availableType, ['physical', 'digital', 'both'], true)) {
        $availableType = 'digital';
    }

    $stmt = $conn->prepare('SELECT id FROM products WHERE name = ? AND price = ? LIMIT 1');
    $stmt->bind_param('sd', $name, $price);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    if ($existing) {
        return (int) $existing['id'];
    }

    $stock = 999;
    $rating = (float) ($details['rating'] ?? 4.8);
    $stmt = $conn->prepare('INSERT INTO products (name, description, category, price, old_price, image, stock, rating, available_type) VALUES (?, ?, ?, ?, NULL, ?, ?, ?, ?)');
    $stmt->bind_param('sssdsids', $name, $description, $category, $price, $image, $stock, $rating, $availableType);
    if (!$stmt->execute()) {
        sendResponse('error', 'Could not add product to catalog.', null, 500);
    }

    return (int) $conn->insert_id;
}
