<?php
require_once __DIR__ . '/../_admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { sendResponse('error', 'Method not allowed.', null, 405); }
validateCsrf();
$input = adminInput();
$id    = (int) ($input['id'] ?? 0);
if ($id <= 0) sendResponse('error', 'Freebie ID is required.', null, 422);

$stmt = $conn->prepare('SELECT name FROM freebies WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$freebie = $stmt->get_result()->fetch_assoc();
if (!$freebie) sendResponse('error', 'Freebie not found.', null, 404);

$delete = $conn->prepare('DELETE FROM freebies WHERE id = ?');
$delete->bind_param('i', $id);
$delete->execute();
sendResponse('success', 'Freebie deleted.');
