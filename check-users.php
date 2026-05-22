<?php
// Check users in database
require_once 'includes/config.php';

$stmt = $conn->prepare('SELECT id, email, first_name, last_name FROM users LIMIT 5');
$stmt->execute();
$users = $stmt->get_result();

echo "Users in database:\n\n";
while ($row = $users->fetch_assoc()) {
    echo "ID: {$row['id']}\n";
    echo "Email: {$row['email']}\n";
    echo "Name: {$row['first_name']} {$row['last_name']}\n";
    echo "---\n";
}
