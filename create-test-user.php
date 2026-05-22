<?php
// Create a test user
require_once 'includes/config.php';

$email = 'testuser@example.com';
$password = 'testpass123';
$password_hash = password_hash($password, PASSWORD_DEFAULT);
$first_name = 'Test';
$last_name = 'User';

// Check if user already exists
$stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();

if ($existing) {
    echo "User already exists with ID: " . $existing['id'] . "\n";
} else {
    // Insert new user
    $stmt = $conn->prepare('INSERT INTO users (email, password_hash, first_name, last_name, phone, role, is_blocked) VALUES (?, ?, ?, ?, ?, ?, 0)');
    $phone = '1234567890';
    $role = 'customer';
    $stmt->bind_param('ssssss', $email, $password_hash, $first_name, $last_name, $phone, $role);
    
    if ($stmt->execute()) {
        echo "✓ Test user created!\n";
        echo "Email: $email\n";
        echo "Password: $password\n";
        echo "ID: " . $conn->insert_id . "\n";
    } else {
        echo "✗ Failed to create user: " . $conn->error . "\n";
    }
}
