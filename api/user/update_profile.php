<?php
require_once __DIR__ . '/../_bootstrap.php';

apiRequirePost();
$user  = apiRequireUser();
$input = apiInput();
validateCsrf();

$first = trim((string) ($input['first_name'] ?? ''));
$last  = trim((string) ($input['last_name'] ?? ''));
$phone = preg_replace('/[^\d+\-\s()]/', '', (string) ($input['phone'] ?? ''));

if ($first === '' || $last === '') {
    sendResponse('error', 'First and last name are required.', null, 422);
}
if (strlen($first) < 2 || strlen($last) < 2) {
    sendResponse('error', 'Name fields must be at least 2 characters.', null, 422);
}
if (strlen($first) > 100 || strlen($last) > 100) {
    sendResponse('error', 'Name fields must be 100 characters or fewer.', null, 422);
}
if (strlen($phone) > 20) {
    sendResponse('error', 'Phone number is too long.', null, 422);
}

$stmt = $conn->prepare('UPDATE users SET first_name = ?, last_name = ?, phone = ? WHERE id = ?');
$stmt->bind_param('sssi', $first, $last, $phone, $user['id']);
$stmt->execute();

$_SESSION['first_name'] = $first;
$_SESSION['last_name']  = $last;
$_SESSION['phone']      = $phone;

sendResponse('success', 'Profile saved.');
