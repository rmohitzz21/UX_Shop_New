<?php

function adminMessageFetchById(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare(
        'SELECT id, is_read, archived FROM contact_messages WHERE id = ? LIMIT 1'
    );
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}
