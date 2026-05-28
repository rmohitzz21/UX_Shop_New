<?php
require_once __DIR__ . '/../_bootstrap.php';

apiRequirePost();
$user  = apiRequireUser();
$input = apiInput();
validateCsrf();

$password = (string) ($input['password'] ?? '');
if ($password === '') {
    sendResponse('error', 'Password confirmation is required to delete your account.', null, 422);
}

$stmt = $conn->prepare('SELECT password_hash, is_blocked FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row || !password_verify($password, (string) $row['password_hash'])) {
    sendResponse('error', 'Incorrect password.', null, 401);
}

$conn->begin_transaction();
try {
    // Anonymise personal data but retain orders for audit history
    $anon  = $conn->prepare('UPDATE users SET email = ?, first_name = "Deleted", last_name = "User", phone = NULL, password_hash = "", is_blocked = 1 WHERE id = ?');
    $ghost = 'deleted_' . $user['id'] . '@removed.invalid';
    $anon->bind_param('si', $ghost, $user['id']);
    if (!$anon->execute()) {
        throw new RuntimeException('Could not anonymise account.');
    }

    // Remove personal data from addresses
    $del = $conn->prepare('DELETE FROM addresses WHERE user_id = ?');
    $del->bind_param('i', $user['id']);
    $del->execute();

    // Clear cart and tokens
    foreach (['cart', 'user_tokens'] as $tbl) {
        $d = $conn->prepare("DELETE FROM `{$tbl}` WHERE user_id = ?");
        $d->bind_param('i', $user['id']);
        $d->execute();
    }

    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    error_log('api/user/delete_account.php: ' . $e->getMessage());
    sendResponse('error', 'Could not delete account. Please try again.', null, 500);
}

// Destroy the session
$_SESSION = [];
session_destroy();

sendResponse('success', 'Your account has been deleted.');
