<?php
// get_kitchen_orders.php
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
        __DIR__ . '/api/db_connect.php'
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
        echo json_encode(['success' => false, 'message' => 'db_connect.php file not found']);
        exit();
    }

    require_once $dbConnectFound;

    if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit();
    }

    // Fetch ONLY active paid orders awaiting preparation
    $sql = "SELECT o.order_id, o.created_at, o.order_status, o.payment_status,
                   od.quantity AS qty, od.subtotal, od.remarks, m.item_name AS name
            FROM orders o
            LEFT JOIN order_details od ON o.order_id = od.order_id
            LEFT JOIN menu_items m ON od.item_id = m.item_id
            WHERE o.payment_status = 'Paid' 
              AND o.order_status IN ('new', 'in_progress', 'preparing')
            ORDER BY o.order_id ASC";

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
                    'items' => []
                ];
            }
            if (!empty($row['name'])) {
                $orders[$id]['items'][] = [
                    'name' => $row['name'],
                    'qty' => (int) $row['qty'],
                    'remarks' => $row['remarks']
                ];
            }
        }
    }

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'orders' => array_values($orders)
    ]);

    $conn->close();

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'PHP Error: ' . $e->getMessage()]);
}