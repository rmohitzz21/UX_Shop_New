<?php
require_once __DIR__ . '/../_bootstrap.php';

$user = apiRequireUser();
$input = apiInput();
validateCsrf();

$first = trim((string) ($input['first_name'] ?? ''));
$last = trim((string) ($input['last_name'] ?? ''));
$phone = preg_replace('/[^\d+\-\s()]/', '', (string) ($input['phone'] ?? ''));
if ($first === '' || $last === '') sendResponse('error', 'First and last name are required.', null, 422);

$stmt = $conn->prepare('UPDATE users SET first_name = ?, last_name = ?, phone = ? WHERE id = ?');
$stmt->bind_param('sssi', $first, $last, $phone, $user['id']);
$stmt->execute();
$_SESSION['first_name'] = $first;
$_SESSION['last_name'] = $last;
sendResponse('success', 'Profile saved.');
