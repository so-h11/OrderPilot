<?php
// db_connect.php
$host = "localhost";
$username = "root"; // Default UniServerZ username
$password = "root"; // Default UniServerZ password (change if you modified it)
$database = "orderpilot_db";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]));
}
?>