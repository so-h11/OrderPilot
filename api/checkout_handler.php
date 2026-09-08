<?php
// api/checkout_handler.php
session_start();
header('Content-Type: application/json');
date_default_timezone_set('Asia/Kuala_Lumpur');

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
if (!$dbConnectFound) { echo json_encode(['status' => 'error', 'message' => 'db_connect.php not found']); exit; }
require_once $dbConnectFound;

$conn->query("SET time_zone = '+08:00'");

try {
    $deviceId = !empty($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    $userRole = $_SESSION['role'] ?? '';
    $input = json_decode(file_get_contents('php://input'), true);
    $cartItems = $input['items'] ?? [];
    $paymentMethod = trim($input['payment_method'] ?? 'Cash');
    $orderType = trim($input['order_type'] ?? 'customer');

    if (!is_array($cartItems) || count($cartItems) === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Your cart is empty.']);
        exit;
    }

    $total = 0.0;
    $validatedItems = [];

    foreach ($cartItems as $line) {
        $itemId = (int) ($line['item_id'] ?? $line['id'] ?? 0);
        $quantity = (int) ($line['quantity'] ?? $line['qty'] ?? 0);
        $remarks = trim((string) ($line['remarks'] ?? ''));

        $stmt = $conn->prepare('SELECT price, is_available FROM menu_items WHERE item_id = ?');
        $stmt->bind_param('i', $itemId);
        $stmt->execute();
        $stmt->bind_result($price, $isAvailable);
        $found = $stmt->fetch();
        $stmt->close();

        if (!$found || !$isAvailable) {
            echo json_encode(['status' => 'error', 'message' => 'One or more items in the cart are unavailable.']);
            exit;
        }

        $subtotal = round(((float) $price) * $quantity, 2);
        $total += $subtotal;
        $validatedItems[] = ['item_id' => $itemId, 'quantity' => $quantity, 'subtotal' => $subtotal, 'remarks' => $remarks];
    }

    $conn->begin_transaction();

    // STRICT CHECK: Only Walk-in POS orders placed directly from Cashier Terminal are auto-paid.
    // Regular Customer orders ALWAYS default to 'pending' + 'unpaid' for Cashier Approval.
    $normalizedRole = strtolower($userRole);
    $isCashierUser = in_array($normalizedRole, ['cashier', 'administrator', 'admin']);
    $isWalkInPOS = ($orderType === 'walkin' && $isCashierUser);

    $orderStatus = $isWalkInPOS ? 'new' : 'pending';
    $paymentStatus = $isWalkInPOS ? 'Paid' : 'unpaid';
    $now = date('Y-m-d H:i:s');

    $orderStmt = $conn->prepare(
        'INSERT INTO orders (device_id, total_amount, order_status, payment_status, payment_method, created_at) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $orderStmt->bind_param('idssss', $deviceId, $total, $orderStatus, $paymentStatus, $paymentMethod, $now);
    $orderStmt->execute();
    $orderId = $orderStmt->insert_id;
    $orderStmt->close();

    $detailStmt = $conn->prepare('INSERT INTO order_details (order_id, item_id, quantity, subtotal, remarks) VALUES (?, ?, ?, ?, ?)');
    foreach ($validatedItems as $line) {
        $detailStmt->bind_param('iiids', $orderId, $line['item_id'], $line['quantity'], $line['subtotal'], $line['remarks']);
        $detailStmt->execute();
    }
    $detailStmt->close();

    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'order_id' => $orderId,
        'total_amount' => $total,
        'order_status' => $orderStatus,
        'payment_status' => $paymentStatus,
        'payment_method' => $paymentMethod,
        'created_at' => $now,
    ]);

} catch (Throwable $e) {
    if (isset($conn)) { @$conn->rollback(); }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>