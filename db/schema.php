<?php
// VPS SQLite database schema: only billing info

$dbFile = __DIR__ . '/billing_vps.db';

try {
    $db = new PDO("sqlite:$dbFile");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // -----------------------
    // CREATE BILLING TABLE
    // -----------------------
    $db->exec("
    CREATE TABLE IF NOT EXISTS billing (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        router_id INTEGER NOT NULL,
        mac TEXT NOT NULL,
        plan_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        phone_number TEXT NOT NULL,
        remaining_time INTEGER NOT NULL, -- in seconds
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(router_id, mac)
    );
    ");

    echo "VPS SQLite billing table ready ✅";

} catch (PDOException $e) {
    die("DB ERROR: " . $e->getMessage());
}
?>
