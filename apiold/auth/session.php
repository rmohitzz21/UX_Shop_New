<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';

if (isset($_SESSION['user_id'])) {
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'user'   => [
            'id'       => (int) $_SESSION['user_id'],
            'email'    => $_SESSION['email']    ?? '',
            'username' => $_SESSION['username'] ?? '',
            'role'     => $_SESSION['role']     ?? 'customer'
        ]
    ]);
} else {
    // SEC-11: must return 401, not 200
    http_response_code(401);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Not authenticated'
    ]);
}
