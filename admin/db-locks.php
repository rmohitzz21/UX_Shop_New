<?php
/**
 * db-locks.php — show what's currently blocking DB transactions.
 *
 * Run this WHILE the delete request is hanging, OR right after a timeout.
 * Admin-only. Delete after debugging.
 */

require_once __DIR__ . '/../includes/config.php';

if (empty($_SESSION['admin_id'])) {
    http_response_code(403);
    exit('Forbidden — log in as admin first.');
}

header('Content-Type: text/plain; charset=utf-8');

$dump = static function (mysqli $conn, string $title, string $sql): void {
    echo "--- {$title} ---\n";
    $r = $conn->query($sql);
    if (!$r) {
        echo "ERROR: " . $conn->error . "\n\n";
        return;
    }
    $rows = $r->fetch_all(MYSQLI_ASSOC);
    if (!$rows) { echo "(none)\n\n"; return; }
    foreach ($rows as $i => $row) {
        echo "[" . ($i + 1) . "]\n";
        foreach ($row as $k => $v) {
            $v = $v === null ? '(null)' : (string) $v;
            if (strlen($v) > 200) $v = substr($v, 0, 200) . '…';
            printf("  %-22s %s\n", $k, $v);
        }
        echo "\n";
    }
};

echo "=== DB lock snapshot @ " . date('c') . " ===\n\n";

// Active transactions (MariaDB / InnoDB)
$dump($conn, 'INNODB_TRX (active transactions)', 'SELECT trx_id, trx_state, trx_started, trx_wait_started, trx_mysql_thread_id, trx_query FROM information_schema.INNODB_TRX');

// MariaDB has metadata_lock_info plugin; not always loaded. Fall back to PROCESSLIST.
$dump($conn, 'PROCESSLIST (active threads)', 'SELECT ID, USER, HOST, DB, COMMAND, TIME, STATE, LEFT(INFO, 200) AS INFO FROM information_schema.PROCESSLIST WHERE COMMAND != "Sleep" OR TIME > 10 ORDER BY TIME DESC');

// Sleeping connections that may be holding locks
$dump($conn, 'PROCESSLIST (sleepers > 60s — possible held transactions)', 'SELECT ID, USER, HOST, DB, COMMAND, TIME, STATE FROM information_schema.PROCESSLIST WHERE COMMAND = "Sleep" AND TIME > 60 ORDER BY TIME DESC LIMIT 20');

// FK constraint check — orders.id referenced from other tables
$dump($conn, 'Tables referencing orders.id (FK constraints)',
    "SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
     FROM information_schema.KEY_COLUMN_USAGE
     WHERE TABLE_SCHEMA = DATABASE()
       AND (REFERENCED_TABLE_NAME = 'orders' OR REFERENCED_TABLE_NAME = 'order_items')");

// Row counts for the chain
foreach (['orders','order_items','digital_downloads','inventory_reservations'] as $t) {
    $r = @$conn->query("SELECT COUNT(*) AS c FROM `{$t}`");
    if ($r) {
        $row = $r->fetch_assoc();
        printf("rows in %-22s %s\n", $t, number_format((int) $row['c']));
    }
}

echo "\n--- innodb_lock_wait_timeout ---\n";
$r = $conn->query("SHOW VARIABLES LIKE 'innodb_lock_wait_timeout'");
if ($r) { $row = $r->fetch_assoc(); echo $row['Value'] . " seconds\n"; }

echo "\n=== end ===\n";
