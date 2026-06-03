<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/InventoryReservationService.php';

try {
    $count = InventoryReservationService::releaseExpired($conn);
    echo 'Released expired reservation orders: ' . $count . PHP_EOL;
} catch (Throwable $e) {
    error_log('release-expired-reservations.php: ' . $e->getMessage());
    echo 'Reservation cleanup failed.' . PHP_EOL;
    exit(1);
}

