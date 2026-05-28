<?php
require_once __DIR__ . '/../_admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { sendResponse('error', 'Method not allowed.', null, 405); }
validateCsrf();
$input = adminInput();
$id = (int) ($input['id'] ?? 0);
if ($id <= 0) sendResponse('error', 'Review ID is required.', null, 422);

if (!empty($input['delete'])) {
    $stmt = $conn->prepare('DELETE FROM reviews WHERE id = ?');
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) sendResponse('error', 'Could not delete review.', null, 500);
    sendResponse('success', 'Review deleted.');
}

$approve = (int) ($input['approve'] ?? 0);
$stmt = $conn->prepare('UPDATE reviews SET is_approved = ? WHERE id = ?');
$stmt->bind_param('ii', $approve, $id);
if (!$stmt->execute()) sendResponse('error', 'Could not update review.', null, 500);
sendResponse('success', $approve ? 'Review approved.' : 'Review unapproved.');
