<?php

function addressInput(): array {
    $input = apiInput();
    if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) && !empty($input['csrf_token'])) {
        $_SERVER['HTTP_X_CSRF_TOKEN'] = (string) $input['csrf_token'];
    }
    return $input;
}

function cleanAddressText(array $input, string $key, int $max = 255): string {
    $value = trim((string) ($input[$key] ?? ''));
    return substr($value, 0, $max);
}

function normalizeAddressPayload(array $input): array {
    $required = ['firstName', 'lastName', 'address', 'city', 'state', 'zip', 'country', 'phone'];
    foreach ($required as $field) {
        if (trim((string) ($input[$field] ?? '')) === '') {
            sendResponse('error', 'Missing field: ' . $field, null, 422);
        }
    }

    return [
        'first_name' => cleanAddressText($input, 'firstName', 100),
        'last_name' => cleanAddressText($input, 'lastName', 100),
        'address_line1' => cleanAddressText($input, 'address', 255),
        'address_line2' => cleanAddressText($input, 'address2', 255),
        'city' => cleanAddressText($input, 'city', 120),
        'state' => cleanAddressText($input, 'state', 120),
        'zip_code' => cleanAddressText($input, 'zip', 30),
        'country' => cleanAddressText($input, 'country', 80),
        'phone' => preg_replace('/[^\d+\-\s()]/', '', cleanAddressText($input, 'phone', 40)),
        'is_default' => !empty($input['isDefault']) ? 1 : 0,
    ];
}

function ensureAddressOwner(mysqli $conn, int $addressId, int $userId): void {
    $stmt = $conn->prepare('SELECT id FROM addresses WHERE id = ? AND user_id = ? LIMIT 1');
    $stmt->bind_param('ii', $addressId, $userId);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        sendResponse('error', 'Address not found.', null, 404);
    }
}
