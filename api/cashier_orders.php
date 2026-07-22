<?php
// api/cashier_orders.php
session_start();
header('Content-Type: application/json');
require_once '../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit();
}

// Fetch today's orders (excluding cancelled)
$sql = "SELECT o.order_id, o.total_amount, o.order_status, o.payment_status, o.created_at, 
               od.quantity, od.subtotal, od.remarks, m.item_name
        FROM orders o
        JOIN order_details od ON o.order_id = od.order_id
        JOIN menu_items m ON od.item_id = m.item_id
        WHERE o.order_status != 'cancelled'
        AND DATE(o.created_at) = CURDATE()
        ORDER BY o.order_id DESC"; // Newest orders at the top

$result = $conn->query($sql);
$orders = [];
$totalSales = 0.00;
$pendingCount = 0;
$completedCount = 0;

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $id = $row['order_id'];
        
        // Calculate stats on the fly
        if (!isset($orders[$id])) {
            $orders[$id] = [
                'id' => $id,
                'total' => floatval($row['total_amount']),
                'status' => $row['order_status'],
                'payment_status' => $row['payment_status'],
                'time' => date('h:i A', strtotime($row['created_at'])),
                'items' => []
            ];
            
            if ($row['payment_status'] === 'Paid') {
                $totalSales += floatval($row['total_amount']);
            }
            if ($row['order_status'] === 'new' || $row['order_status'] === 'in_progress') {
                $pendingCount++;
            }
            if ($row['order_status'] === 'completed') {
                $completedCount++;
            }
        }
        
        $orders[$id]['items'][] = [
            'name' => $row['item_name'],
            'qty' => $row['quantity'],
            'subtotal' => floatval($row['subtotal']),
            'remarks' => $row['remarks']
        ];
    }
}

echo json_encode([
    "status" => "success", 
    "data" => array_values($orders),
    "stats" => [
        "totalSales" => $totalSales,
        "pending" => $pendingCount,
        "completed" => $completedCount
    ]
]);
$conn->close();
?>