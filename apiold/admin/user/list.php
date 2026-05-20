<?php


header('Content-Type: application/json');

require_once '../../../includes/config.php';

// Enforce Admin Access
requireAdmin();

if($conn->connect_error){
    sendResponse("error", "Database connection failed", null, 500);
}

// Adjust table/column name if needed

$sql  = "SELECT id, first_name, last_name, email, phone, role, created_at, is_blocked,
          (SELECT COUNT(*) FROM orders WHERE user_id = users.id) as order_count 
          FROM users 
          ORDER BY created_at DESC";
$result = $conn->query($sql);

$users = [];

if($result && $result->num_rows > 0){
    while($row = $result->fetch_assoc()){
        $users[] = $row;
    }
}

sendResponse("success", "Users fetched successfully", $users);

$conn->close();

?>