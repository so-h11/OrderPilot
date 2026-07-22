<?php
// api/kitchen_handler.php
session_start();
header('Content-Type: application/json');
require_once '../db_connect.php';

$method = $_SERVER['REQUEST_METHOD'];

// ---------------------------------------------------------
// FETCH ACTIVE ORDERS (GET)
// ---------------------------------------------------------
if ($method === 'GET') {
    // Fetch orders from today that are not cancelled
    $sql = "SELECT o.order_id, o.order_status, o.created_at, 
                   od.quantity, od.remarks, m.item_name
            FROM orders o
            JOIN order_details od ON o.order_id = od.order_id
            JOIN menu_items m ON od.item_id = m.item_id
            WHERE o.order_status IN ('new', 'in_progress', 'completed')
            AND DATE(o.created_at) = CURDATE()
            ORDER BY o.order_id ASC"; // Oldest orders first
            
    $result = $conn->query($sql);
    $orders = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $id = $row['order_id'];
            
            // Group the items by Order ID
            if (!isset($orders[$id])) {
                $orders[$id] = [
                    'id' => $id,
                    'status' => $row['order_status'],
                    'time' => date('h:i A', strtotime($row['created_at'])),
                    'items' => []
                ];
            }
            $orders[$id]['items'][] = [
                'name' => $row['item_name'],
                'qty' => $row['quantity'],
                'remarks' => $row['remarks']
            ];
        }
    }
    
    // Convert associative array to a simple indexed array for JavaScript
    echo json_encode(["status" => "success", "data" => array_values($orders)]);
} 
// ---------------------------------------------------------
// UPDATE ORDER STATUS (POST)
// ---------------------------------------------------------
elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['order_id']) || !isset($data['status'])) {
        echo json_encode(["status" => "error", "message" => "Missing order ID or status."]);
        exit();
    }

    $orderId = intval($data['order_id']);
    $newStatus = $conn->real_escape_string($data['status']);

    // Validate against the ENUM values in the database
    $allowedStatuses = ['new', 'in_progress', 'completed', 'cancelled'];
    if (!in_array($newStatus, $allowedStatuses)) {
        echo json_encode(["status" => "error", "message" => "Invalid status update."]);
        exit();
    }

    $sql = "UPDATE orders SET order_status = '$newStatus' WHERE order_id = $orderId";
    
    if ($conn->query($sql) === TRUE) {
        echo json_encode(["status" => "success", "message" => "Order updated!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
}

$conn->close();
?>