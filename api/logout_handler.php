<?php
// api/logout_handler.php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
if (($_SESSION['role'] ?? '') === 'Customer' && ($input['pin'] ?? '') !== '0000') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid logout passcode.']);
    exit();
}

$_SESSION = [];
session_destroy();

echo json_encode(['status' => 'success', 'message' => 'Logged out successfully']);
?>