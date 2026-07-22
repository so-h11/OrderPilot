<?php
// api/payment_handler.php
session_start();
require_once '../db_connect.php';

// ---------------------------------------------------------
// 1. PROCESS PAYMENT (POST Request)
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Check if the user is a Cashier or Admin
    if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'Cashier' && $_SESSION['role'] !== 'Administrator')) {
        echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
        exit();
    }

    $data = json_decode(file_get_contents("php://input"), true);
    $orderId = isset($data['order_id']) ? intval($data['order_id']) : 0;

    if ($orderId > 0) {
        // Scope strictly supports Cash, so we just flip the status to 'Paid'
        $sql = "UPDATE orders SET payment_status = 'Paid' WHERE order_id = $orderId";
        
        if ($conn->query($sql) === TRUE) {
            echo json_encode(["status" => "success", "message" => "Payment successfully processed!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid Order ID."]);
    }
    exit();
}

// ---------------------------------------------------------
// 2. GENERATE HTML RECEIPT (GET Request)
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $orderId = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

    if ($orderId <= 0) {
        die("Invalid Order ID.");
    }

    // Fetch the order and its items
    $sql = "SELECT o.order_id, o.total_amount, o.created_at, o.payment_status, 
                   od.quantity, od.subtotal, m.item_name
            FROM orders o
            JOIN order_details od ON o.order_id = od.order_id
            JOIN menu_items m ON od.item_id = m.item_id
            WHERE o.order_id = $orderId";
            
    $result = $conn->query($sql);
    
    if (!$result || $result->num_rows === 0) {
        die("Order not found.");
    }

    $items = [];
    $orderData = null;
    while ($row = $result->fetch_assoc()) {
        if (!$orderData) {
            $orderData = [
                'id' => $row['order_id'],
                'total' => $row['total_amount'],
                'date' => date('d M Y, h:i A', strtotime($row['created_at'])),
                'status' => $row['payment_status']
            ];
        }
        $items[] = $row;
    }
    $conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt #<?php echo $orderData['id']; ?></title>
    <style>
        /* Thermal Printer CSS styling (80mm width standard) */
        body {
            font-family: 'Courier New', Courier, monospace;
            width: 300px;
            margin: 0 auto;
            padding: 20px;
            color: #000;
        }
        h2, p { text-align: center; margin: 0 0 10px 0; }
        .divider { border-bottom: 1px dashed #000; margin: 10px 0; }
        table { width: 100%; font-size: 14px; }
        th { text-align: left; border-bottom: 1px solid #000; }
        .text-right { text-align: right; }
        .total-row { font-weight: bold; font-size: 16px; }
        .status { text-align: center; font-weight: bold; margin-top: 15px; border: 1px solid #000; padding: 5px; }
        @media print {
            /* Hide URL headers/footers in browser print */
            @page { margin: 0; }
            body { margin: 1cm; }
        }
    </style>
</head>
<body onload="window.print()"> <!-- Automatically opens the print dialog -->
    <h2>OrderPilot</h2>
    <p>Altitude Bistro</p>
    <p>Receipt #<?php echo $orderData['id']; ?><br><?php echo $orderData['date']; ?></p>
    
    <div class="divider"></div>
    
    <table>
        <thead>
            <tr>
                <th>Qty</th>
                <th>Item</th>
                <th class="text-right">Amt</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($items as $item): ?>
            <tr>
                <td><?php echo $item['quantity']; ?></td>
                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                <td class="text-right"><?php echo number_format($item['subtotal'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="divider"></div>
    
    <table>
        <tr class="total-row">
            <td>TOTAL</td>
            <td class="text-right">RM <?php echo number_format($orderData['total'], 2); ?></td>
        </tr>
    </table>
    
    <div class="status">
        <?php echo strtoupper($orderData['status']); ?>
    </div>
    
    <p style="margin-top: 20px; font-size: 12px;">Thank you for dining with us!</p>
</body>
</html>
<?php 
} // End of GET
?>