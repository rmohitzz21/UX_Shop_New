<?php

function adminReviewFetchById(mysqli $conn, int $id): ?array {
    $stmt = $conn->prepare('SELECT id, is_approved FROM reviews WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}
