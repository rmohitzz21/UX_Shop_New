<?php
header('Content-Type: application/json');

// Include database configuration
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

// Get form data
$name = $_POST['name'] ?? '';
$category = $_POST['category'] ?? '';
$description = $_POST['description'] ?? '';
$price = $_POST['price'] ?? 0;
$old_price = !empty($_POST['old_price']) ? $_POST['old_price'] : NULL;
$stock = $_POST['stock'] ?? 0;
$rating = $_POST['rating'] ?? 0;
$related_products = $_POST['related_products'] ?? '';
$whats_included = $_POST['whats_included'] ?? '';
$file_specification = $_POST['file_specification'] ?? '';
$available_type = $_POST['available_type'] ?? 'physical'; // physical, digital, both
$commercial_price = !empty($_POST['commercial_price']) ? $_POST['commercial_price'] : NULL;
$is_featured = isset($_POST['featured']) ? 1 : 0;
$created_at = date('Y-m-d H:i:s');
$updated_at = date('Y-m-d H:i:s');      

// Validation
if (empty($name) || empty($category) || !isset($price)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Name, category, and price are required"]);
    exit;
}
if (!is_numeric($price) || floatval($price) < 0) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Price must be a non-negative number"]);
    exit;
}
if (!is_numeric($stock) || intval($stock) < 0) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Stock must be a non-negative integer"]);
    exit;
}

// Handle Image Upload
$imagePath = '';
$uploadedImages = [];

if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
    // Define upload directory
    $uploadDir = '../../../img/products/';
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Failed to create upload directory"]);
            exit;
        }
    }

    $files = $_FILES['images'];
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $allowedMimeTypes  = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $fileCount = count($files['name']);

    for ($i = 0; $i < $fileCount; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $fileTmpPath = $files['tmp_name'][$i];
            $fileName = $files['name'][$i];

            // Validate extension
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            // Validate MIME type via finfo (cannot be spoofed by extension)
            $finfo    = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($fileTmpPath);

            // Sanitize file name
            $newFileName = bin2hex(random_bytes(16)) . '.' . $fileExtension;

            if (in_array($fileExtension, $allowedExtensions) && in_array($mimeType, $allowedMimeTypes)) {
                $dest_path = $uploadDir . $newFileName;
                
                if(move_uploaded_file($fileTmpPath, $dest_path)) {
                    // Save relative path for DB
                    $relativePath = 'img/products/' . $newFileName;
                    $uploadedImages[] = $relativePath;
                    
                    // Set first image as main image
                    if (empty($imagePath)) {
                        $imagePath = $relativePath;
                    }
                }
            }
        }
    }
    
    if (empty($imagePath)) {
         http_response_code(500);
         echo json_encode(["status" => "error", "message" => "Failed to upload any images."]);
         exit;
    }

} else {
    // If image is required in the form, fail here.
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Product image is required"]);
    exit;
}

// Serialize additional images (all uploaded images including main one or just extras? Usually all for gallery)
$additional_images = json_encode($uploadedImages);

// Insert into DB
$sql = "INSERT INTO products (name, description, category, available_type, price, commercial_price, old_price, image, stock, rating, related_products, whats_included, file_specification, additional_images, is_featured, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if ($stmt) {
    // Types: s=string, d=double, i=int
    // s - name
    // s - description
    // s - category
    // s - available_type
    // d - price
    // d - commercial_price
    // s - old_price (Assuming string for NULL/OldPrice logic, or 'd' if we force float. Let's use 's' to be safe with NULL if bind_param is strict, but usually 'd' works. Previous code used 's' for old_price in one place and 'd' in another. Let's check update.php. Update used 'd'. Let's use 'd' here but ensure it handles NULL if we pass NULL. bind_param with 'd' treats NULL as 0.0 usually? No, it allows NULL if variable is NULL. Let's use 'd' for prices.)
    // Wait, create.php had "ssssddssidssssss" -> 6th is commercial_price (d), 7th is old_price (s).
    // Let's stick to 's' for old_price if that's what was working, or change to 'd'. 
    // Usually prices are decimals. Let's use 'd'.
    // ssssddd...
    // name, desc, cat, type -> ssss
    // price, comm_price, old_price -> ddd
    // image -> s
    // stock -> i
    // rating -> d
    // related -> s
    // included -> s
    // spec -> s
    // add_images -> s
    // created -> s
    // updated -> s
    
    // Total: ssssdddsidssssss -> 16 chars.
    
    // Total: ssssdddsidssssisi -> 17 chars.
    
    $stmt->bind_param("ssssdddsidssssiss", $name, $description, $category, $available_type, $price, $commercial_price, $old_price, $imagePath, $stock, $rating, $related_products, $whats_included, $file_specification, $additional_images, $is_featured, $created_at, $updated_at);
    
    if ($stmt->execute()) {
        $product_id = $conn->insert_id;

        http_response_code(201);
        echo json_encode([
            "status" => "success",
            "message" => "Product created successfully",
            "product_id" => $conn->insert_id,
            "images_count" => count($uploadedImages)
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Failed to create product"
        ]);
    }
    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Failed to prepare product creation"
    ]);
}

$conn->close();
?>