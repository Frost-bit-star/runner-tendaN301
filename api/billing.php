<?php
header('Content-Type: application/json');
$dbFile = __DIR__ . '/../db/billing_vps.db';

try {
    $db = new PDO("sqlite:$dbFile");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ==========================
    // HANDLE GET (VIEW DATA)
    // ==========================
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        $query = "SELECT * FROM billing";
        $conditions = [];
        $params = [];

        // Filter by MAC
        if (!empty($_GET['mac'])) {
            $conditions[] = "mac = ?";
            $params[] = strtoupper($_GET['mac']);
        }

        // Filter by router_id
        if (!empty($_GET['router_id'])) {
            $conditions[] = "router_id = ?";
            $params[] = $_GET['router_id'];
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $query .= " ORDER BY created_at DESC";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'count' => count($rows),
            'data' => $rows
        ]);
        exit;
    }

    // ==========================
    // HANDLE POST (INSERT/UPDATE)
    // ==========================
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data ||
            !isset(
                $data['router_id'],
                $data['mac'],
                $data['plan_id'],
                $data['name'],
                $data['phone_number'],
                $data['remaining_time']
            )
        ) {
            echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
            exit;
        }

        $stmt = $db->prepare("
            INSERT INTO billing (router_id, mac, plan_id, name, phone_number, remaining_time)
            VALUES (?, ?, ?, ?, ?, ?)
            ON CONFLICT(router_id, mac) DO UPDATE SET
                plan_id = excluded.plan_id,
                name = excluded.name,
                phone_number = excluded.phone_number,
                remaining_time = excluded.remaining_time,
                created_at = CURRENT_TIMESTAMP
        ");

        $stmt->execute([
            $data['router_id'],
            strtoupper($data['mac']),
            $data['plan_id'],
            $data['name'],
            $data['phone_number'],
            $data['remaining_time']
        ]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Billing saved'
        ]);
        exit;
    }

    // ==========================
    // INVALID METHOD
    // ==========================
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
