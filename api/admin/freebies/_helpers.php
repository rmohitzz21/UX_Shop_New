<?php

function adminEnsureFreebiesTable(mysqli $conn): void {
    $conn->query("CREATE TABLE IF NOT EXISTS freebies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL,
        description TEXT,
        category VARCHAR(100) DEFAULT 'General',
        image VARCHAR(500) DEFAULT '',
        file_url VARCHAR(500) DEFAULT '',
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        is_featured TINYINT(1) NOT NULL DEFAULT 0,
        sort_order INT NOT NULL DEFAULT 0,
        download_count INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_freebies_slug (slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function adminValidateFreebieFileUrl(string $url): bool {
    if ($url === '') {
        return false;
    }
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    return in_array($scheme, ['http', 'https'], true);
}

function adminFreebieSaveError(mysqli $conn, mysqli_stmt $stmt, string $action): void {
    if ($conn->errno === 1062) {
        sendResponse('error', 'A freebie with this name or slug already exists.', null, 409);
    }
    sendResponse('error', "Could not {$action} freebie: " . $stmt->error, null, 500);
}
