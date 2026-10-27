<?php
// api/staff_handler.php
session_start();
header('Content-Type: application/json');

if (($_SESSION['role'] ?? '') !== 'Administrator') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Administrator access is required.']);
    exit();
}

$dbConnectCandidates = [
    __DIR__ . '/db_connect.php',
    __DIR__ . '/../db_connect.php',
    __DIR__ . '/includes/db_connect.php',
    __DIR__ . '/../includes/db_connect.php'
];

$dbConnectFound = null;
foreach ($dbConnectCandidates as $candidate) {
    if (file_exists($candidate)) {
        $dbConnectFound = $candidate;
        break;
    }
}

if (!$dbConnectFound) {
    echo json_encode(['status' => 'error', 'message' => 'db_connect.php file not found']);
    exit();
}

require_once $dbConnectFound;

if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Read raw JSON if sent
$rawInput = json_decode(file_get_contents('php://input'), true);
if (!empty($rawInput['action'])) {
    $action = $rawInput['action'];
}

switch ($action) {

    // ==========================================
    // 1. FETCH ALL USERS
    // ==========================================
    case 'fetch':
        $sql = "SELECT user_id, full_name, username, role, status, created_at, updated_at 
                FROM users 
                ORDER BY user_id DESC";
        $result = $conn->query($sql);
        $users = [];

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $users[] = [
                    'user_id'    => (int) $row['user_id'],
                    'full_name'  => $row['full_name'],
                    'username'   => $row['username'],
                    'role'       => $row['role'],
                    'status'     => $row['status'] ?? 'Active',
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at']
                ];
            }
        }
        echo json_encode(['status' => 'success', 'data' => $users]);
        break;

    // ==========================================
    // 2. CREATE NEW USER (#38)
    // ==========================================
    case 'create':
        $fullName = trim($rawInput['full_name'] ?? $_POST['full_name'] ?? '');
        $username = trim($rawInput['username'] ?? $_POST['username'] ?? '');
        $password = trim($rawInput['password'] ?? $_POST['password'] ?? '');
        $role     = trim($rawInput['role'] ?? $_POST['role'] ?? 'Cashier');
        $status   = trim($rawInput['status'] ?? $_POST['status'] ?? 'Active');

        if (empty($fullName) || empty($username) || empty($password) || empty($role)) {
            echo json_encode(['status' => 'error', 'message' => 'All required fields must be filled.']);
            exit();
        }
        if (!in_array($role, ['Administrator', 'Cashier', 'Kitchen Staff', 'Customer'], true) ||
            !in_array($status, ['Active', 'Inactive'], true)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid role or account status.']);
            exit();
        }

        // Check duplicate username
        $checkStmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $checkStmt->bind_param("s", $username);
        $checkStmt->execute();
        $checkStmt->store_result();
        if ($checkStmt->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Username already exists. Please choose another.']);
            $checkStmt->close();
            exit();
        }
        $checkStmt->close();

        // Direct password saving for compatibility
        $now = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO users (full_name, username, password_hash, role, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $fullName, $username, $password, $role, $status, $now, $now);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'User created successfully!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database insert error: ' . $conn->error]);
        }
        $stmt->close();
        break;

    // ==========================================
    // 3. EDIT / UPDATE USER (#40)
    // ==========================================
    case 'update':
        $userId   = intval($rawInput['user_id'] ?? $_POST['user_id'] ?? 0);
        $fullName = trim($rawInput['full_name'] ?? $_POST['full_name'] ?? '');
        $username = trim($rawInput['username'] ?? $_POST['username'] ?? '');
        $password = trim($rawInput['password'] ?? $_POST['password'] ?? '');
        $role     = trim($rawInput['role'] ?? $_POST['role'] ?? 'Cashier');
        $status   = trim($rawInput['status'] ?? $_POST['status'] ?? 'Active');

        if ($userId <= 0 || empty($fullName) || empty($username) || empty($role)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid data provided for update.']);
            exit();
        }
        if (!in_array($role, ['Administrator', 'Cashier', 'Kitchen Staff', 'Customer'], true) ||
            !in_array($status, ['Active', 'Inactive'], true)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid role or account status.']);
            exit();
        }

        // Check if username is taken by another account
        $checkStmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
        $checkStmt->bind_param("si", $username, $userId);
        $checkStmt->execute();
        $checkStmt->store_result();
        if ($checkStmt->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Username is already taken by another user.']);
            $checkStmt->close();
            exit();
        }
        $checkStmt->close();

        $now = date('Y-m-d H:i:s');

        if (!empty($password)) {
            // Update with new password
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, username = ?, password_hash = ?, role = ?, status = ?, updated_at = ? WHERE user_id = ?");
            $stmt->bind_param("ssssssi", $fullName, $username, $password, $role, $status, $now, $userId);
        } else {
            // Keep existing password
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, username = ?, role = ?, status = ?, updated_at = ? WHERE user_id = ?");
            $stmt->bind_param("sssssi", $fullName, $username, $role, $status, $now, $userId);
        }

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'User account updated successfully!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database update error: ' . $conn->error]);
        }
        $stmt->close();
        break;

    // ==========================================
    // 4. DELETE USER (#41)
    // ==========================================
    case 'delete':
        $userId = intval($rawInput['user_id'] ?? $_POST['user_id'] ?? 0);

        if ($userId <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid user ID.']);
            exit();
        }

        // Prevent self-deletion if logged in
        if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === $userId) {
            echo json_encode(['status' => 'error', 'message' => 'You cannot delete your own active administrator account.']);
            exit();
        }

        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $userId);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'User account permanently removed.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete account: ' . $conn->error]);
        }
        $stmt->close();
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action specified.']);
        break;
}

$conn->close();