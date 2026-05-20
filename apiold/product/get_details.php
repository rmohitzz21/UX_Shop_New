<?php
header('Content-Type: application/json');

require_once '../../includes/config.php';

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

// Get raw POST data
$data = json_decode(file_get_contents("php://input"), true);
$ids = isset($data['ids']) ? $data['ids'] : [];

if (empty($ids) || !is_array($ids)) {
    echo json_encode(["status" => "success", "data" => []]);
    exit;
}

// Sanitize IDs (ensure they are integers)
$ids = array_map('intval', $ids);
$ids_string = implode(',', $ids);

if (empty($ids_string)) {
    echo json_encode(["status" => "success", "data" => []]);
    exit;
}

// Fetch products matching the IDs
$sql = "SELECT id, name, price, image, description, category, stock, available_type FROM products WHERE id IN ($ids_string) AND is_active = 1";
$result = $conn->query($sql);

$products = [];
if ($result) {
    while($row = $result->fetch_assoc()) {
        $products[$row['id']] = $row;
    }
}

echo json_encode([
    "status" => "success",
    "data" => $products
]);

$conn->close();
?>
