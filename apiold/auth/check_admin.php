<?php
// Admin check endpoint - requires active admin session
header('Content-Type: application/json');
require_once '../../includes/config.php';

if (isset($_SESSION['admin_id'])) {
    echo json_encode([
        'status' => 'success',
        'is_admin' => true,
        'admin_id' => $_SESSION['admin_id']
    ]);
} else {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'is_admin' => false,
        'message' => 'Not authenticated as admin'
    ]);
}
?>
