<?php
// api/logout_handler.php
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session completely
session_destroy();

echo json_encode(["status" => "success", "message" => "Logged out successfully"]);
?>