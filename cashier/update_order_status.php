<?php
// update_order_status.php
session_start();
header('Content-Type: application/json');

$dbConnectCandidates = [
    __DIR__ . '/db_connect.php',
    __DIR__ . '/../db_connect.php',
    __DIR__ . '/includes/db_connect.php',
    __DIR__ . '/../includes/db_connect.php'
];

$dbConnectFound = null;
foreach ($dbConnectCandidates as $candidate) {
    if (file_exists($candidate)) {
        $dbConnectFound = $candidate;
        break;
    }
}

if (!$dbConnectFound) {
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'db_connect.php not found']);
    exit();
}

require_once $dbConnectFound;

if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Database connection failed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

// Bulk Clear All Completed Orders
if (isset($input['action']) && $input['action'] === 'clear_all_completed') {
    $stmt = $conn->prepare("UPDATE orders SET order_status = 'cancelled', updated_at = NOW() WHERE order_status = 'completed' AND DATE(created_at) = CURDATE()");
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'status' => 'success', 'message' => 'All completed orders cleared!']);
    } else {
        echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }
    $stmt->close();
    $conn->close();
    exit();
}

// Single Order Status Update
$orderId = isset($input['order_id']) ? intval($input['order_id']) : 0;
$status = isset($input['kitchen_status']) ? $conn->real_escape_string($input['kitchen_status']) : null;

$allowedStatuses = ['pending', 'new', 'in_progress', 'completed', 'cancelled'];

if ($orderId <= 0 || !in_array($status, $allowedStatuses, true)) {
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Invalid order_id or kitchen_status']);
    exit();
}

$sql = "UPDATE orders SET order_status = '$status', updated_at = NOW() WHERE order_id = $orderId";

if ($conn->query($sql) === TRUE) {
    echo json_encode(['success' => true, 'status' => 'success', 'message' => 'Order status updated']);
} else {
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Database error: ' . $conn->error]);
}

$conn->close();