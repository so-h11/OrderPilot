<?php
// admin/process_payment.php
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

    $rawInput = json_decode(file_get_contents('php://input'), true);
    $orderId = intval($rawInput['order_id'] ?? $_POST['order_id'] ?? 0);
    $paymentMethod = trim($rawInput['payment_method'] ?? $_POST['payment_method'] ?? 'Cash');

    if ($orderId <= 0) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid order ID provided']);
        exit();
    }

    // Update payment status to Paid and send pending orders to kitchen queue ('new')
    $stmt = $conn->prepare("UPDATE orders 
                            SET payment_status = 'Paid', 
                                payment_method = ?, 
                                order_status = CASE 
                                    WHEN order_status IN ('pending', 'pending_approval') THEN 'new' 
                                    ELSE order_status 
                                END 
                            WHERE order_id = ? AND LOWER(COALESCE(order_status, '')) <> 'cancelled'");
    $stmt->bind_param("si", $paymentMethod, $orderId);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        ob_end_clean();
        echo json_encode(['success' => true, 'message' => 'Payment approved successfully. Order sent to kitchen!']);
    } else {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Payment could not be approved. The order may already be cancelled or paid.']);
    }

    $stmt->close();
    $conn->close();

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'PHP Error: ' . $e->getMessage()]);
}