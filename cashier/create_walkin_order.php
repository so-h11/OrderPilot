<?php
/**
 * api/create_walkin_order.php
 * Body: { "customer_name": "Walk-in", "total_amount": 25.00 }
 *
 * Minimal placeholder: creates a pending, unpaid order with no line items.
 * Wire this up to your actual menu/cart flow when you build the
 * "New Walk-in Order" form out further.
 */
session_start();
header('Content-Type: application/json');
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Cashier' && $_SESSION['role'] !== 'Administrator')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$customerName = trim($input['customer_name'] ?? 'Walk-in');
$total = $input['total_amount'] ?? null;

if (!is_numeric($total) || $total <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid total_amount']);
    exit();
}

$customerName = $conn->real_escape_string($customerName);
$total = floatval($total);
$userId = intval($_SESSION['user_id']);

$sql = "INSERT INTO orders (customer_name, created_at, order_status, payment_status, total_amount, created_by) VALUES ('$customerName', NOW(), 'new', 'Unpaid', $total, $userId)";

if ($conn->query($sql) === TRUE) {
    echo json_encode(['success' => true, 'order_id' => $conn->insert_id]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

$conn->close();