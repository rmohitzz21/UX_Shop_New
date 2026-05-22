<?php
require_once 'includes/config.php';

// Check session
$hasSession = !empty($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Debug</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .box { border: 1px solid #ccc; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; border-color: #28a745; }
        .error { background: #f8d7da; border-color: #721c24; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Session & Auth Debug</h1>

    <div class="box <?= $hasSession ? 'success' : 'error' ?>">
        <h2>Session Status</h2>
        <p><strong>User ID:</strong> <?= $_SESSION['user_id'] ?? 'NOT SET' ?></p>
        <p><strong>Email:</strong> <?= $_SESSION['user_email'] ?? 'NOT SET' ?></p>
        <p><strong>Name:</strong> <?= ($_SESSION['first_name'] ?? 'N/A') . ' ' . ($_SESSION['last_name'] ?? 'N/A') ?></p>
        <p><strong>Role:</strong> <?= $_SESSION['user_role'] ?? 'NOT SET' ?></p>
        <p><strong>Session ID:</strong> <?= session_id() ?></p>
    </div>

    <?php if ($hasSession): ?>
        <div class="box success">
            <h2>✓ You are logged in!</h2>
            <p>The navbar should show your profile menu and hide the Sign In button.</p>
            <p><a href="wishlist.php">Go to Wishlist</a></p>
            <p><a href="api/auth/logout.php">Logout</a></p>
        </div>
    <?php else: ?>
        <div class="box error">
            <h2>✗ You are NOT logged in</h2>
            <p>The navbar should show the Sign In button.</p>
            <p><a href="signin.php?redirect=test-session.php">Go to Sign In</a></p>
        </div>
    <?php endif; ?>

    <div class="box">
        <h2>Full Session Data</h2>
        <pre><?php var_export($_SESSION); ?></pre>
    </div>

    <?php include 'includes/header.php'; ?>
    <div class="box" style="margin-top: 50px;">
        <h2>Header Variables (from header.php)</h2>
        <p><strong>$headerUserId:</strong> <?= $headerUserId ?? 'NOT SET' ?></p>
        <p><strong>$headerUserName:</strong> <?= htmlspecialchars($headerUserName ?? 'N/A') ?></p>
    </div>
</body>
</html>
