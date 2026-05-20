<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/helpers.php';

if ($conn->connect_error) {
    sendResponse("error", "Database connection failed", null, 500);
}

// Pagination
$page     = max(1, (int) ($_GET['page']     ?? 1));
$per_page = min(48, max(4, (int) ($_GET['per_page'] ?? 12)));
$offset   = ($page - 1) * $per_page;

// Optional filters
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$search   = isset($_GET['search'])   ? trim($_GET['search'])   : '';

// Build WHERE clause
$where  = "is_active = 1 AND (stock > 0 OR available_type != 'physical')";
$params = [];
$types  = '';

if ($category !== '') {
    $where   .= " AND category = ?";
    $params[] = $category;
    $types   .= 's';
}

if ($search !== '') {
    $where   .= " AND (name LIKE ? OR description LIKE ?)";
    $like     = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $types   .= 'ss';
}

// Count total for pagination meta
$countStmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE $where");
if ($types) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$countStmt->bind_result($total);
$countStmt->fetch();
$countStmt->close();

// Fetch page — NEVER expose commercial_price publicly (PERF-02)
$sql = "
    SELECT id, name, description, price, old_price, image, additional_images,
           category, rating, stock, available_type, whats_included, created_at
    FROM products
    WHERE $where
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
";

$params[] = $per_page;
$params[] = $offset;
$types   .= 'ii';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}
$stmt->close();
$conn->close();

sendResponse("success", "Products fetched", [
    'products'   => $products,
    'total'      => (int) $total,
    'page'       => $page,
    'per_page'   => $per_page,
    'total_pages'=> (int) ceil($total / $per_page)
]);
