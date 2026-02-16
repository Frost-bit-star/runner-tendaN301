<?php
// -----------------------
// billWorker.php
// Continuously updates user billing and revokes expired access
// -----------------------

// Run indefinitely
set_time_limit(0);

// Path to SQLite DB
$dbPath = __DIR__ . '/../db/billing_vps.db'; // VPS DB for billing only

// Connect to DB
try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    echo "Failed to connect to DB: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// Check interval (seconds)
$CHECK_INTERVAL = 1; // 1 second for real-time countdown

// Helper to format seconds into readable time
function formatTime($seconds) {
    $d = floor($seconds / 86400);
    $h = floor(($seconds % 86400) / 3600);
    $m = floor(($seconds % 3600) / 60);
    $s = $seconds % 60;
    return "{$d}d {$h}h {$m}m {$s}s";
}

echo "[" . date('Y-m-d H:i:s') . "] Billing worker started." . PHP_EOL;

// -----------------------
// Main loop
// -----------------------
while (true) {
    try {
        // Fetch all active billing entries
        $stmt = $db->query("SELECT * FROM billing");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($users as $user) {
            $remainingTime = (int)$user['remaining_time'];

            // Deduct interval seconds
            $remainingTime -= $CHECK_INTERVAL;

            if ($remainingTime <= 0) {
                $remainingTime = 0;

                // Revoke user access
                $updateUser = $db->prepare("UPDATE users SET internet_access = 0 WHERE mac = ? AND router_id = ?");
                $updateUser->execute([$user['mac'], $user['router_id']]);

                echo "[" . date('Y-m-d H:i:s') . "] User '{$user['name']}' expired, access revoked." . PHP_EOL;
            }

            // Update remaining_time in billing table
            $updateBilling = $db->prepare("UPDATE billing SET remaining_time = ? WHERE id = ?");
            $updateBilling->execute([$remainingTime, $user['id']]);

            // Optional logging
            echo "[" . date('Y-m-d H:i:s') . "] User '{$user['name']}' remaining: " . formatTime($remainingTime) . PHP_EOL;
        }

    } catch (Exception $e) {
        echo "[" . date('Y-m-d H:i:s') . "] Error: " . $e->getMessage() . PHP_EOL;
    }

    // Wait for next tick
    sleep($CHECK_INTERVAL);
}
