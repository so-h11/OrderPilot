<?php
// getorder.php
session_start();
header('Content-Type: application/json');
date_default_timezone_set('Asia/Kuala_Lumpur');

$dbConnectCandidates = [
    __DIR__ . '/db_connect.php',
    __DIR__ . '/../db_connect.php',
    __DIR__ . '/includes/db_connect.php',
    __DIR__ . '/../includes/db_connect.php'
];

$dbConnectFound = null;
foreach ($dbConnectCandidates as $candidate) {
    if (file_exists($candidate)) { $dbConnectFound = $candidate; break; }
}

if (!$dbConnectFound) {
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'db_connect.php not found']);
    exit();
}

require_once $dbConnectFound;
$conn->query("SET time_zone = '+08:00'");

$isCompletedType = isset($_GET['type']) && $_GET['type'] === 'completed';

// Fetch today's orders (all active orders including pending approval for cashier)
$sql = "SELECT o.order_id, o.created_at, o.order_status, o.payment_status, 
               COALESCE(o.payment_method, 'Cash') AS payment_method, o.total_amount,
               od.quantity AS qty, od.subtotal, od.remarks, m.item_name AS name
        FROM orders o
        LEFT JOIN order_details od ON o.order_id = od.order_id
        LEFT JOIN menu_items m ON od.item_id = m.item_id
        WHERE o.order_status != 'cancelled'
          AND DATE(o.created_at) = CURDATE()
        ORDER BY o.order_id DESC";

$result = $conn->query($sql);

$ordersMap = [];
$totalSales = 0.00;
$pendingCount = 0;
$completedCount = 0;

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $id = $row['order_id'];
        $pStatus = ucfirst(strtolower($row['payment_status'] ?? 'Unpaid'));
        $oStatus = $row['order_status'];

        // Map kitchen_status for cashier UI expectations
        $kitchenStatus = $oStatus;
        if ($oStatus === 'pending' || $pStatus === 'Unpaid') {
            $kitchenStatus = 'pending_approval';
        }

        if (!isset($ordersMap[$id])) {
            $ordersMap[$id] = [
                'order_id' => (int) $id,
                'order_time' => $row['created_at'],
                'kitchen_status' => $kitchenStatus,
                'payment_status' => $pStatus,
                'payment_method' => $row['payment_method'],
                'total_amount' => (float) $row['total_amount'],
                'items' => []
            ];

            if ($pStatus === 'Paid') {
                $totalSales += (float) $row['total_amount'];
            }
            if ($kitchenStatus === 'pending_approval' || $oStatus === 'new' || $oStatus === 'in_progress') {
                $pendingCount++;
            }
            if ($oStatus === 'completed') {
                $completedCount++;
            }
        }

        if (!empty($row['name'])) {
            $ordersMap[$id]['items'][] = [
                'name' => $row['name'],
                'qty' => (int) $row['qty'],
                'subtotal' => (float) $row['subtotal'],
                'remarks' => $row['remarks'] ?? ''
            ];
        }
    }
}

// Filter orders based on table request type
$filteredOrders = [];
foreach ($ordersMap as $order) {
    if ($isCompletedType) {
        if ($order['kitchen_status'] === 'completed') {
            $filteredOrders[] = $order;
        }
    } else {
        if ($order['kitchen_status'] !== 'completed') {
            $filteredOrders[] = $order;
        }
    }
}

echo json_encode([
    'success' => true,
    'status' => 'success',
    'orders' => array_values($filteredOrders),
    'stats' => [
        'sales' => $totalSales,
        'pending' => $pendingCount,
        'completed' => $completedCount
    ]
]);

$conn->close();
?>