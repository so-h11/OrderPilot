<?php
// api/kitchen_handler.php
session_start();
header('Content-Type: application/json');
date_default_timezone_set('Asia/Kuala_Lumpur');

$dbConnectCandidates = [
    __DIR__ . '/db_connect.php',
    __DIR__ . '/../db_connect.php',
];

$dbConnectFound = null;
foreach ($dbConnectCandidates as $candidate) {
    if (file_exists($candidate)) { $dbConnectFound = $candidate; break; }
}

if (!$dbConnectFound) {
    echo json_encode(['status' => 'error', 'message' => 'Could not find db_connect.php']);
    exit;
}

require_once $dbConnectFound;
$conn->query("SET time_zone = '+08:00'");

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Strictly fetch paid orders approved by Cashier
    $sql = "SELECT o.order_id, o.order_status, o.created_at, o.total_amount, 
                   COALESCE(o.payment_method, 'Cash') AS payment_method,
                   od.quantity, od.remarks, od.subtotal, m.item_name
            FROM orders o
            JOIN order_details od ON o.order_id = od.order_id
            JOIN menu_items m ON od.item_id = m.item_id
            WHERE LOWER(o.payment_status) = 'paid'
              AND o.order_status IN ('new', 'in_progress', 'completed')
              AND DATE(o.created_at) = CURDATE()
            ORDER BY o.order_id DESC";
            
    $result = $conn->query($sql);
    $orders = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $id = $row['order_id'];
            
            if (!isset($orders[$id])) {
                $orders[$id] = [
                    'id' => (int) $id,
                    'status' => $row['order_status'],
                    'total' => (float) $row['total_amount'],
                    'payment_method' => $row['payment_method'],
                    'time' => date('h:i A', strtotime($row['created_at'])),
                    'items' => []
                ];
            }
            $orders[$id]['items'][] = [
                'name' => $row['item_name'],
                'qty' => (int) $row['quantity'],
                'subtotal' => (float) $row['subtotal'],
                'remarks' => $row['remarks'] ?? ''
            ];
        }
    }
    
    echo json_encode(["status" => "success", "data" => array_values($orders)]);
} 
elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (isset($data['action']) && $data['action'] === 'clear_all_completed') {
        $stmt = $conn->prepare("UPDATE orders SET order_status = 'cancelled', updated_at = NOW() WHERE order_status = 'completed' AND DATE(created_at) = CURDATE()");
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "All completed orders cleared!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
        }
        $stmt->close();
        $conn->close();
        exit();
    }
    
    if (!isset($data['order_id']) || !isset($data['status'])) {
        echo json_encode(["status" => "error", "message" => "Missing order ID or status."]);
        exit();
    }

    $orderId = intval($data['order_id']);
    $newStatus = trim($data['status']);

    $allowedStatuses = ['new', 'in_progress', 'completed', 'cancelled'];
    if (!in_array($newStatus, $allowedStatuses)) {
        echo json_encode(["status" => "error", "message" => "Invalid status update."]);
        exit();
    }

    $stmt = $conn->prepare("UPDATE orders SET order_status = ?, updated_at = NOW() WHERE order_id = ?");
    $stmt->bind_param("si", $newStatus, $orderId);
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Order updated!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
    }
    $stmt->close();
}

$conn->close();
?>