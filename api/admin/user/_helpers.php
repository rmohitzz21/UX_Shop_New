<?php

function adminUserPrivilegedRoles(): array {
    return ['admin', 'super_admin', 'superadmin'];
}

function adminUserIsPrivileged(?string $role): bool {
    return in_array(strtolower(trim((string) $role)), adminUserPrivilegedRoles(), true);
}

function adminUserFetchById(mysqli $conn, int $id): ?array {
    $stmt = $conn->prepare('SELECT id, email, role, is_blocked FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}

function adminUserGuardSelfAction(int $targetId): void {
    if ($targetId === (int) ($_SESSION['admin_id'] ?? 0)) {
        sendResponse('error', 'You cannot perform this action on your own account.', null, 422);
    }
}

function adminUserGuardPrivileged(?array $user, string $action = 'modify'): void {
    if ($user && adminUserIsPrivileged($user['role'] ?? '')) {
        sendResponse('error', "Admin accounts cannot be {$action} from the customer users panel.", null, 403);
    }
}
