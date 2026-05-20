<?php
header('Content-Type: application/json');

require_once '../../../includes/config.php';

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    exit;
}

// Authentication Check
requireAdmin();

// CSRF Protection
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Invalid CSRF token"]);
    exit;
}

// Get ID
$id = $_POST['id'] ?? null;
if (!$id) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Product ID is required"]);
    exit;
}

// Get other fields
$name = $_POST['name'] ?? '';
$category = $_POST['category'] ?? '';
$description = $_POST['description'] ?? '';
$price = $_POST['price'] ?? 0;
// $old_price = ... (optional, let's include it if posted)
$old_price = !empty($_POST['old_price']) ? $_POST['old_price'] : NULL;
$stock = $_POST['stock'] ?? 0;
$rating = $_POST['rating'] ?? 0; // Optional update
$related_products = $_POST['related_products'] ?? '';
$whats_included = $_POST['whats_included'] ?? '';
$file_specification = $_POST['file_specification'] ?? '';
$available_type = $_POST['available_type'] ?? 'physical';
$commercial_price = !empty($_POST['commercial_price']) ? $_POST['commercial_price'] : NULL;
$is_featured = isset($_POST['featured']) ? 1 : 0;
$updated_at = date('Y-m-d H:i:s');

// Handle Images
$new_images = [];
// 1. Process uploaded files
if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
    $uploadDir = '../../../img/products/';
    if (!file_exists($uploadDir)) mkdir($uploadDir, 0755, true);

    $files             = $_FILES['images'];
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $allowedMimeTypes  = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $fileCount         = count($files['name']);

    for ($i = 0; $i < $fileCount; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $fileTmpPath   = $files['tmp_name'][$i];
            $fileName      = $files['name'][$i];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $newFileName   = bin2hex(random_bytes(16)) . '.' . $fileExtension;

            // Validate MIME type via finfo
            $finfo    = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($fileTmpPath);

            if (in_array($fileExtension, $allowedExtensions) && in_array($mimeType, $allowedMimeTypes)) {
                $dest_path = $uploadDir . $newFileName;
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $new_images[] = 'img/products/' . $newFileName;
                }
            }
        }
    }
}

// 2. Process existing images (passed as JSON string)
$kept_images = [];
if (isset($_POST['existing_images'])) {
    $decoded = json_decode($_POST['existing_images'], true);
    if (is_array($decoded)) {
        $kept_images = $decoded;
    }
} else {
    // If not set, it might mean we are not using the new frontend logic yet?
    // Or it means ALL existing images were removed?
    // Let's assume if it's not set, we don't change existing images UNLESS new images are uploaded (legacy behavior)
    // BUT, the user wants removal support.
    // If 'existing_images' param is present (even empty json "[]"), we respect it.
    // If it is completely missing from POST, maybe we should default to 'fetch current from DB'?
    // No, frontend sends it.
}

// Combine: Kept images first, then new images
// The FINAL list of images for the product
$final_images = array_merge($kept_images, $new_images);

// Main image is the first one
$imagePath = !empty($final_images) ? $final_images[0] : ''; // If no images, empty string or null?
$additional_images_json = !empty($final_images) ? json_encode($final_images) : '[]'; // Store all in additional_json for simplicity?
// The DB schema usually has 'image' (main) and 'additional_images' (all or extras).
// Let's stick to the convention: 'image' is primary. 'additional_images' contains ALL (or just extras).
// Previous logic: 'additional_images' stored ALL including main.
// So:
$imagePath = !empty($final_images) ? $final_images[0] : '';
$additional_images_json = json_encode($final_images);

// Logic:
// If we have final_images (changed or not), we update the DB fields.
// If final_images is empty, it means user removed everything?

$update_images = true;
// Only skip update if we didn't receive 'existing_images' AND didn't upload any new ones (Legacy fallback)
if (!isset($_POST['existing_images']) && empty($new_images)) {
    $update_images = false;
}
// Actually, if user removes all images, existing_images will be "[]". 
// So update_images should be true.

// Construct SQL
$sql = "";
$types = "";
$params = [$name, $description, $category, $available_type, $price, $commercial_price, $old_price, $stock, $rating, $is_featured, $related_products, $whats_included, $file_specification, $updated_at];
$baseTypes = "ssssdddidissss"; 

if ($update_images) {
    // Update with images
    $sql = "UPDATE products SET name=?, description=?, category=?, available_type=?, price=?, commercial_price=?, old_price=?, stock=?, rating=?, is_featured=?, related_products=?, whats_included=?, file_specification=?, updated_at=?, image=?, additional_images=? WHERE id=?";
    $types = $baseTypes . "ssi"; 
    $params[] = $imagePath;
    $params[] = $additional_images_json;
    $params[] = $id;
} else {
    // Update without changing images
    $sql = "UPDATE products SET name=?, description=?, category=?, available_type=?, price=?, commercial_price=?, old_price=?, stock=?, rating=?, is_featured=?, related_products=?, whats_included=?, file_specification=?, updated_at=? WHERE id=?";
    $types = $baseTypes . "i";
    $params[] = $id;
}

$stmt = $conn->prepare($sql);

if ($stmt) {
    // ...$params for bind_param is cleaner in PHP 8+, but for older PHP we usually use call_user_func_array with references.
    // However, if we assume PHP 8 or recent 7, spread operator might work.
    // But safely, let's use spread if environment supports it, or manual.
    // Note: mysqli::bind_param needs variables passed by reference?
    // Actually `...$params` works if they are values, but bind_param expects references? 
    // No, bind_param expects variables.
    // `bind_param($types, $var1, $var2...)`
    
    // In strict PHP < 8, spread operator for bind_param can be tricky if not by ref.
    // But let's assume standard modern environment.
    // Actually, `...$params` UNPACKS params.
    // The issue is `bind_param` wants REFERENCES.
    // So `$stmt->bind_param($types, ...$params)` might fail if $params are values.
    
    // Let's use a small helper or manual binding?
    // Or just `execute($params)`? get_result?
    // MySQLi `execute` accepting params is only available in PHP 8.1+.
    // If user has older PHP, this will fail.
    
    // The previous code used manual binding for `create.php`.
    // For `update.php`, the array length varies (14 or 16).
    
    // Let's use `call_user_func_array` with references.
    $bind_params = [];
    $bind_params[] = & $types;
    foreach ($params as $key => $value) {
        $bind_params[] = & $params[$key];
    }
    
    call_user_func_array(array($stmt, 'bind_param'), $bind_params);
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Product updated successfully"]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Failed to update product"]);
    }
    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to prepare product update"]);
}

$conn->close();
?>
