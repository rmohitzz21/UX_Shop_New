<?php
require_once __DIR__ . '/../_admin.php';
require_once __DIR__ . '/_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', 'Method not allowed.', null, 405);
}
validateCsrf();

$input  = adminInput();
$id     = (int) ($input['id'] ?? 0);
$action = trim((string) ($input['action'] ?? ''));

if ($id <= 0) {
    sendResponse('error', 'Message ID is required.', null, 422);
}

$message = adminMessageFetchById($conn, $id);
if (!$message) {
    sendResponse('error', 'Message not found.', null, 404);
}

if ($action === 'delete') {
    $stmt = $conn->prepare('DELETE FROM contact_messages WHERE id = ?');
    $stmt->bind_param('i', $id);
    if (!$stmt->execute() || $stmt->affected_rows < 1) {
        sendResponse('error', 'Could not delete message.', null, 500);
    }
    sendResponse('success', 'Message deleted.');
}

if ($action === 'archive') {
    if ((int) $message['archived'] === 1) {
        sendResponse('success', 'Message is already archived.');
    }
    $stmt = $conn->prepare('UPDATE contact_messages SET archived = 1 WHERE id = ?');
    $stmt->bind_param('i', $id);
    if (!$stmt->execute() || $stmt->affected_rows < 1) {
        sendResponse('error', 'Could not archive message.', null, 500);
    }
    sendResponse('success', 'Message archived.');
}

if ($action === 'read') {
    if ((int) $message['is_read'] === 1) {
        sendResponse('success', 'Message is already marked as read.');
    }
    $stmt = $conn->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = ?');
    $stmt->bind_param('i', $id);
    if (!$stmt->execute() || $stmt->affected_rows < 1) {
        sendResponse('error', 'Could not mark message as read.', null, 500);
    }
    sendResponse('success', 'Message marked as read.');
}

if ($action === 'unread') {
    if ((int) $message['is_read'] === 0) {
        sendResponse('success', 'Message is already unread.');
    }
    $stmt = $conn->prepare('UPDATE contact_messages SET is_read = 0 WHERE id = ?');
    $stmt->bind_param('i', $id);
    if (!$stmt->execute() || $stmt->affected_rows < 1) {
        sendResponse('error', 'Could not mark message as unread.', null, 500);
    }
    sendResponse('success', 'Message marked as unread.');
}

sendResponse('error', 'Unknown action. Use read, unread, archive, or delete.', null, 422);
