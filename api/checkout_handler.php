<?php
// checkout_handler.php
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
        echo json_encode(['status' => 'error', 'message' => 'db_connect.php not found']);
        exit();
    }

    require_once $dbConnectFound;

    if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
        exit();
    }

    $rawInput = json_decode(file_get_contents('php://input'), true);
    $paymentMethod = trim($rawInput['payment_method'] ?? $_POST['payment_method'] ?? 'Cash');
    $items = $rawInput['items'] ?? $_POST['items'] ?? [];

    if (empty($items)) {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Cart is empty.']);
        exit();
    }

    // Calculate total amount
    $totalAmount = 0.00;
    $processedItems = [];

    foreach ($items as $item) {
        $itemId = intval($item['item_id'] ?? 0);
        $qty = intval($item['quantity'] ?? $item['qty'] ?? 1);
        $remarks = trim($item['remarks'] ?? '');

        if ($itemId > 0 && $qty > 0) {
            $stmt = $conn->prepare("SELECT price FROM menu_items WHERE item_id = ?");
            $stmt->bind_param("i", $itemId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $price = (float) $row['price'];
                $subtotal = $price * $qty;
                $totalAmount += $subtotal;
                $processedItems[] = [
                    'item_id' => $itemId,
                    'qty' => $qty,
                    'subtotal' => $subtotal,
                    'remarks' => $remarks
                ];
            }
            $stmt->close();
        }
    }

    if (empty($processedItems)) {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'No valid menu items in cart.']);
        exit();
    }

    // Create Order: Unpaid orders default to 'pending_approval'
    $paymentStatus = ($paymentMethod === 'Walk-in Paid') ? 'Paid' : 'unpaid';
    $orderStatus   = ($paymentStatus === 'Paid') ? 'new' : 'pending_approval';

    $stmt = $conn->prepare("INSERT INTO orders (total_amount, payment_status, payment_method, order_status, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("dsss", $totalAmount, $paymentStatus, $paymentMethod, $orderStatus);

    if ($stmt->execute()) {
        $orderId = $stmt->insert_id;
        $stmt->close();

        // Insert Order Details
        $detailStmt = $conn->prepare("INSERT INTO order_details (order_id, item_id, quantity, subtotal, remarks) VALUES (?, ?, ?, ?, ?)");
        foreach ($processedItems as $pItem) {
            $detailStmt->bind_param("iiids", $orderId, $pItem['item_id'], $pItem['qty'], $pItem['subtotal'], $pItem['remarks']);
            $detailStmt->execute();
        }
        $detailStmt->close();

        ob_end_clean();
        echo json_encode([
            'status' => 'success',
            'order_id' => $orderId,
            'message' => 'Order submitted successfully!'
        ]);
    } else {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Failed to create order: ' . $conn->error]);
    }

    $conn->close();

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => 'PHP Error: ' . $e->getMessage()]);
}