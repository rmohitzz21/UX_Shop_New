<?php
// Simple test to verify session persistence
require_once 'includes/config.php';

echo "=== LOGIN TEST ===\n\n";

// 1. Check if user is already logged in
if (!empty($_SESSION['user_id'])) {
    echo "✓ User is logged in!\n";
    echo "  ID: " . $_SESSION['user_id'] . "\n";
    echo "  Email: " . $_SESSION['user_email'] . "\n";
    echo "  Name: " . ($_SESSION['first_name'] ?? '') . " " . ($_SESSION['last_name'] ?? '') . "\n";
    echo "\n✓ Session persisted successfully!\n";
    exit;
}

// 2. If not logged in, show test form
echo "Session is empty. Session ID: " . session_id() . "\n\n";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login Test</title>
</head>
<body>
    <h1>Login Test Form</h1>
    <form method="POST" action="api/auth/login-process.php">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        <input type="hidden" name="redirect" value="test-login.php">
        
        <label>Email: 
            <input type="email" name="email" value="testuser@example.com" required>
        </label><br><br>
        
        <label>Password: 
            <input type="password" name="password" value="testpass123" required>
        </label><br><br>
        
        <button type="submit">Login</button>
    </form>

    <h2>Session Debug Info:</h2>
    <pre><?php var_dump($_SESSION); ?></pre>
</body>
</html>
