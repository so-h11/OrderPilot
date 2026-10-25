<?php
// admin/update_order_status.php
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
    $action = $rawInput['action'] ?? $_POST['action'] ?? '';

    // 1. CLEAR ALL COMPLETED & CANCELLED ORDERS
    if ($action === 'clear_all_completed') {
        $stmt = $conn->prepare("UPDATE orders SET order_status = 'cleared' WHERE order_status IN ('completed', 'cancelled') AND DATE(created_at) = CURDATE()");
        $stmt->execute();
        $stmt->close();

        ob_end_clean();
        echo json_encode(['success' => true, 'message' => 'Completed and cancelled orders cleared from live view.']);
        $conn->close();
        exit();
    }

    // 2. DELETE INDIVIDUAL ITEM FROM ORDER
    if ($action === 'delete_item') {
        $orderId = intval($rawInput['order_id'] ?? 0);
        $itemId  = intval($rawInput['item_id'] ?? 0);

        if ($orderId > 0 && $itemId > 0) {
            $delStmt = $conn->prepare("DELETE FROM order_details WHERE order_id = ? AND item_id = ?");
            $delStmt->bind_param("ii", $orderId, $itemId);
            $delStmt->execute();
            $delStmt->close();

            $recalc = $conn->prepare("UPDATE orders SET total_amount = COALESCE((SELECT SUM(subtotal) FROM order_details WHERE order_id = ?), 0) WHERE order_id = ?");
            $recalc->bind_param("ii", $orderId, $orderId);
            $recalc->execute();
            $recalc->close();

            ob_end_clean();
            echo json_encode(['success' => true, 'message' => 'Item removed from order.']);
            $conn->close();
            exit();
        }
    }

    $orderId = intval($rawInput['order_id'] ?? $_POST['order_id'] ?? 0);
    $kitchenStatus = trim($rawInput['kitchen_status'] ?? $rawInput['order_status'] ?? $_POST['kitchen_status'] ?? '');

    if ($orderId <= 0) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Missing or invalid order ID.']);
        exit();
    }

    // 3. REVERT / UNDO LOGIC
    if ($action === 'revert' || $kitchenStatus === 'revert' || $kitchenStatus === 'pending' || $kitchenStatus === 'pending_approval') {
        $stmt = $conn->prepare("UPDATE orders SET order_status = 'pending_approval', payment_status = 'unpaid' WHERE order_id = ?");
        $stmt->bind_param("i", $orderId);
        
        if ($stmt->execute()) {
            ob_end_clean();
            echo json_encode(['success' => true, 'message' => 'Order reverted to Pending Approval (Unpaid).']);
        } else {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Failed to revert order: ' . $conn->error]);
        }
        $stmt->close();
        $conn->close();
        exit();
    }

    if (empty($kitchenStatus)) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Missing kitchen status.']);
        exit();
    }

    // 4. KITCHEN SYNC / STATUS UPDATE LOGIC
    if (in_array($kitchenStatus, ['new', 'in_progress', 'preparing', 'completed'])) {
        $stmt = $conn->prepare("UPDATE orders SET order_status = ?, payment_status = 'Paid' WHERE order_id = ?");
        $stmt->bind_param("si", $kitchenStatus, $orderId);
    } else {
        $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
        $stmt->bind_param("si", $kitchenStatus, $orderId);
    }

    if ($stmt->execute()) {
        ob_end_clean();
        echo json_encode(['success' => true, 'message' => 'Order status updated.']);
    } else {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Database update error: ' . $conn->error]);
    }

    $stmt->close();
    $conn->close();

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'PHP Error: ' . $e->getMessage()]);
}