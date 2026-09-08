<?php
// edit_order_handler.php
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
    echo json_encode(['success' => false, 'message' => 'db_connect.php not found']);
    exit();
}

require_once $dbConnectFound;

$input = json_decode(file_get_contents('php://input'), true);
$orderId = isset($input['order_id']) ? (int) $input['order_id'] : 0;
$items = isset($input['items']) && is_array($input['items']) ? $input['items'] : [];

if ($orderId <= 0 || empty($items)) {
    echo json_encode(['success' => false, 'message' => 'Order must have at least one valid item.']);
    exit();
}

try {
    $conn->begin_transaction();

    // 1. Fetch current order items with item prices
    $sqlExisting = "SELECT od.item_id, m.item_name, m.price
                    FROM order_details od
                    JOIN menu_items m ON od.item_id = m.item_id
                    WHERE od.order_id = ?";
    $stmtExist = $conn->prepare($sqlExisting);
    $stmtExist->bind_param("i", $orderId);
    $stmtExist->execute();
    $resExist = $stmtExist->get_result();
    
    $itemMap = [];
    while ($row = $resExist->fetch_assoc()) {
        $itemMap[$row['item_name']] = [
            'item_id' => (int) $row['item_id'],
            'price' => (float) $row['price']
        ];
    }
    $stmtExist->close();

    // 2. Clear previous order details
    $stmtDelete = $conn->prepare("DELETE FROM order_details WHERE order_id = ?");
    $stmtDelete->bind_param("i", $orderId);
    $stmtDelete->execute();
    $stmtDelete->close();

    // 3. Re-insert updated items & compute new total
    $newTotal = 0.00;
    $stmtInsert = $conn->prepare("INSERT INTO order_details (order_id, item_id, quantity, subtotal, remarks) VALUES (?, ?, ?, ?, ?)");

    foreach ($items as $item) {
        $itemName = $item['name'] ?? '';
        $qty = (int) ($item['qty'] ?? 1);
        $remarks = trim((string) ($item['remarks'] ?? ''));

        if (!isset($itemMap[$itemName])) {
            // Fallback lookup if item_id was passed directly
            $stmtFallback = $conn->prepare("SELECT item_id, price FROM menu_items WHERE item_name = ? LIMIT 1");
            $stmtFallback->bind_param("s", $itemName);
            $stmtFallback->execute();
            $resFb = $stmtFallback->get_result();
            if ($rowFb = $resFb->fetch_assoc()) {
                $itemMap[$itemName] = ['item_id' => (int)$rowFb['item_id'], 'price' => (float)$rowFb['price']];
            }
            $stmtFallback->close();
        }

        if (isset($itemMap[$itemName]) && $qty > 0) {
            $itemId = $itemMap[$itemName]['item_id'];
            $unitPrice = $itemMap[$itemName]['price'];
            $subtotal = round($unitPrice * $qty, 2);
            $newTotal += $subtotal;

            $stmtInsert->bind_param("iiids", $orderId, $itemId, $qty, $subtotal, $remarks);
            $stmtInsert->execute();
        }
    }
    $stmtInsert->close();

    // 4. Update parent order grand total
    $stmtUpdateOrder = $conn->prepare("UPDATE orders SET total_amount = ?, updated_at = NOW() WHERE order_id = ?");
    $stmtUpdateOrder->bind_param("di", $newTotal, $orderId);
    $stmtUpdateOrder->execute();
    $stmtUpdateOrder->close();

    $conn->commit();
    echo json_encode(['success' => true, 'status' => 'success', 'message' => 'Order updated successfully', 'total_amount' => $newTotal]);

} catch (Throwable $e) {
    if (isset($conn)) { @$conn->rollback(); }
    echo json_encode(['success' => false, 'message' => 'Failed to save changes: ' . $e->getMessage()]);
}

$conn->close();
?>