<?php
session_start();
header('Content-Type: application/json');
require_once '../db_connect.php'; 

// Ensure the request is a POST request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // FIXED: Removed the double $$ typo on $conn
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    $role = $conn->real_escape_string($_POST['role']);

    // Query the database for the staff user
    $sql = "SELECT user_id, full_name, password_hash FROM users WHERE username = '$username' AND role = '$role'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // TEMPORARY FOR TESTING: Plain text password check
        if ($password == $user['password_hash']) {
            // Set session variables - this keeps the staff logged in!
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $role;
            
            // Determine where to redirect based on STAFF role
            $redirect = 'index.html'; // Default fallback
            if($role == 'Cashier') $redirect = 'cashier_dashboard.php';
            if($role == 'Administrator') $redirect = 'admin_dashboard.php';
            if($role == 'Kitchen Staff') $redirect = 'kitchen_dashboard.php';
            
            echo json_encode(["status" => "success", "redirect" => $redirect]);
        } else {
            echo json_encode(["status" => "error", "message" => "Invalid password."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Staff member not found or incorrect role."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
}
$conn->close();
?>