<?php
// api/checkout_handler.php
session_start();
header('Content-Type: application/json');
require_once '../db_connect.php';

// Ensure it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit();
}

// Read the raw JSON payload from the frontend fetch() request
$jsonPayload = file_get_contents("php://input");
$data = json_decode($jsonPayload, true);

// Validate the payload
if (!$data || empty($data['cartItems'])) {
    echo json_encode(["status" => "error", "message" => "Cart is empty or invalid data received."]);
    exit();
}

$totalAmount = floatval($data['totalAmount']);
// For now, device_id is NULL. In Sprint 3, we will link this to the 4-digit pairing code.
$deviceId = isset($data['deviceId']) ? intval($data['deviceId']) : "NULL"; 

// ---------------------------------------------------------
// START TRANSACTION
// ---------------------------------------------------------
$conn->begin_transaction();

try {
    // 1. Insert the main order into the `orders` table
    // order_status defaults to 'new' and payment_status defaults to 'Unpaid'
    $sqlOrder = "INSERT INTO orders (device_id, total_amount) VALUES ($deviceId, $totalAmount)";
    
    if (!$conn->query($sqlOrder)) {
        throw new Exception("Failed to create the main order: " . $conn->error);
    }

    // Grab the newly generated Order ID
    $orderId = $conn->insert_id; 

    // 2. Prepare the statement for inserting into `order_details`
    // Prepared statements are highly secure and efficient for looping through cart items
    $stmtDetails = $conn->prepare("INSERT INTO order_details (order_id, item_id, quantity, subtotal, remarks) VALUES (?, ?, ?, ?, ?)");
    
    if (!$stmtDetails) {
        throw new Exception("Failed to prepare order details statement: " . $conn->error);
    }

    // 3. Loop through the cart items and execute the insert for each one
    foreach ($data['cartItems'] as $item) {
        $itemId = intval($item['item_id']);
        $quantity = intval($item['quantity']);
        $subtotal = floatval($item['price']) * $quantity;
        $remarks = $item['remarks'] ?? ''; // Can be empty

        // Bind the parameters (i = integer, d = double/float, s = string)
        $stmtDetails->bind_param("iiids", $orderId, $itemId, $quantity, $subtotal, $remarks);
        
        if (!$stmtDetails->execute()) {
            throw new Exception("Failed to insert item $itemId: " . $stmtDetails->error);
        }
    }

    // 4. Commit the transaction (Save everything permanently)
    $conn->commit();
    $stmtDetails->close();

    // Return the new Order ID so the frontend can show the customer their order number
    echo json_encode([
        "status" => "success", 
        "message" => "Order placed successfully!", 
        "order_id" => $orderId
    ]);

} catch (Exception $e) {
    // Something went wrong, roll back all database changes to prevent corrupted data
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

$conn->close();
?>