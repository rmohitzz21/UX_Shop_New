<?php
require_once __DIR__ . '/../_admin.php';

validateCsrf();
$input = adminInput();
$id = (int) ($input['id'] ?? 0);
if ($id <= 0) sendResponse('error', 'Message ID is required.', null, 422);

$action = trim((string) ($input['action'] ?? ''));

if ($action === 'delete') {
    $stmt = $conn->prepare('DELETE FROM contact_messages WHERE id = ?');
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) sendResponse('error', 'Could not delete message.', null, 500);
    sendResponse('success', 'Message deleted.');
}

if ($action === 'archive') {
    $stmt = $conn->prepare('UPDATE contact_messages SET archived = 1 WHERE id = ?');
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) sendResponse('error', 'Could not archive message.', null, 500);
    sendResponse('success', 'Message archived.');
}

if ($action === 'read') {
    $stmt = $conn->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = ?');
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) sendResponse('error', 'Could not mark message as read.', null, 500);
    sendResponse('success', 'Message marked as read.');
}

sendResponse('error', 'Unknown action.', null, 422);
