<?php
// getorder.php & admin/getorder.php
ob_start();
session_start();
header('Content-Type: application/json');

ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    $dbConnectCandidates = [
        __DIR__ . '/db_connect.php',
        __DIR__ . '/../db_connect.php',
        __DIR__ . '/includes/db_connect.php',
        __DIR__ . '/../includes/db_connect.php',
        __DIR__ . '/../api/db_connect.php'
    ];

    $dbConnectFound = null;
    foreach ($dbConnectCandidates as $candidate) {
        if (file_exists($candidate)) { 
            $dbConnectFound = $candidate; 
            break; 
        }
    }

    if (!$dbConnectFound) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'db_connect.php file could not be found']);
        exit();
    }

    require_once $dbConnectFound;

    if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit();
    }

    $type = $_GET['type'] ?? 'active';

    if ($type === 'completed') {
        $whereClause = "WHERE o.order_status = 'completed' AND DATE(o.created_at) = CURDATE()";
    } elseif ($type === 'all') {
        // Exclude cleared orders from live Master Order Control
        $whereClause = "WHERE o.order_status != 'cleared'";
    } else {
        $whereClause = "WHERE o.order_status IN ('pending', 'pending_approval', 'new', 'in_progress', 'preparing')";
    }

    $sql = "SELECT o.order_id, o.created_at, o.order_status, o.payment_status, o.payment_method, o.total_amount,
                   od.item_id, od.quantity AS qty, od.subtotal, od.remarks, m.item_name AS name
            FROM orders o
            LEFT JOIN order_details od ON o.order_id = od.order_id
            LEFT JOIN menu_items m ON od.item_id = m.item_id
            $whereClause
            ORDER BY o.order_id DESC";

    $result = $conn->query($sql);
    $orders = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $id = $row['order_id'];
            if (!isset($orders[$id])) {
                $orders[$id] = [
                    'order_id' => $id,
                    'order_time' => $row['created_at'],
                    'kitchen_status' => $row['order_status'],
                    'payment_status' => $row['payment_status'],
                    'payment_method' => $row['payment_method'] ?? 'Cash',
                    'total_amount' => (float) $row['total_amount'],
                    'items' => []
                ];
            }
            if (!empty($row['name'])) {
                $orders[$id]['items'][] = [
                    'item_id' => (int) $row['item_id'],
                    'name' => $row['name'],
                    'qty' => (int) $row['qty'],
                    'subtotal' => (float) $row['subtotal'],
                    'remarks' => $row['remarks']
                ];
            }
        }
    }

    // Revenue calculation retains all Paid orders regardless of cleared status for Report accuracy
    $statsQuery = "SELECT 
        COALESCE(SUM(CASE WHEN payment_status = 'Paid' AND LOWER(COALESCE(order_status, '')) <> 'cancelled' AND DATE(created_at) = CURDATE() THEN total_amount ELSE 0 END), 0) AS sales,
        COUNT(CASE WHEN order_status IN ('pending', 'pending_approval') OR payment_status = 'unpaid' THEN 1 END) AS pending,
        COUNT(CASE WHEN order_status IN ('new', 'in_progress', 'preparing') AND payment_status = 'Paid' THEN 1 END) AS kitchen_active,
        COUNT(CASE WHEN order_status = 'completed' AND DATE(created_at) = CURDATE() THEN 1 END) AS completed
        FROM orders WHERE DATE(created_at) = CURDATE() OR payment_status = 'unpaid'";

    $statsResult = $conn->query($statsQuery);
    $statsRow = $statsResult ? $statsResult->fetch_assoc() : ['sales' => 0, 'pending' => 0, 'kitchen_active' => 0, 'completed' => 0];

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'orders' => array_values($orders),
        'stats' => [
            'sales' => (float) $statsRow['sales'],
            'pending' => (int) $statsRow['pending'],
            'kitchen_active' => (int) $statsRow['kitchen_active'],
            'completed' => (int) $statsRow['completed'],
        ],
    ]);

    $conn->close();

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'PHP Error: ' . $e->getMessage()]);
}