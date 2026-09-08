<?php
// process_payment.php
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
    if (file_exists($candidate)) { $dbConnectFound = $candidate; break; }
}

if (!$dbConnectFound) {
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'db_connect.php not found']);
    exit();
}

require_once $dbConnectFound;

$input = json_decode(file_get_contents('php://input'), true);
$orderId = isset($input['order_id']) ? intval($input['order_id']) : 0;
$method = isset($input['payment_method']) ? $conn->real_escape_string($input['payment_method']) : 'Cash';

if ($orderId <= 0) {
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Invalid Order ID']);
    exit();
}

// Approve order: set payment_status = 'Paid' and order_status = 'new' so Kitchen receives it
$sql = "UPDATE orders SET payment_status = 'Paid', order_status = 'new', payment_method = '$method' WHERE order_id = $orderId";

if ($conn->query($sql) === TRUE) {
    echo json_encode(['success' => true, 'status' => 'success', 'message' => 'Order approved and dispatched to kitchen!']);
} else {
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Database error: ' . $conn->error]);
}

$conn->close();
?>