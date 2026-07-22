<?php
// kitchen_dashboard.php
session_start();

// Basic Security Check: Kick out anyone who isn't logged in as Kitchen Staff
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Kitchen Staff') {
    echo "<script>window.location.href = 'index.html';</script>";
    exit();
}

// Fetch the logged-in staff member's name
$staffName = $_SESSION['full_name'] ?? 'Kitchen Staff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OrderPilot - Kitchen Staff Dashboard</title>
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom Kitchen CSS -->
    <link rel="stylesheet" href="css/kitchen_dashboard.css">
</head>
<body>

    <!-- Top Navigation Bar -->
    <header class="topbar">
        <div class="brand">
            <span class="brand-icon"><i class="fas fa-utensils"></i></span>
            <span class="brand-name">OrderPilot</span>
        </div>
        <div class="topbar-right">
            <span class="welcome-text">Welcome, <strong><?php echo htmlspecialchars($staffName); ?></strong></span>
            <button type="button" id="logoutBtn" class="btn btn-logout" onclick="logout()">Log Out</button>
        </div>
    </header>

    <main class="dashboard-container">
        <h1 class="dashboard-title">
            <span class="title-icon"><i class="fas fa-fire-burner"></i></span> Kitchen Staff Dashboard
        </h1>

        <!-- Real-Time Statistic Cards -->
        <section class="stats-row">
            <div class="stat-card stat-new">
                <span class="stat-label">NEW ORDERS</span>
                <!-- JS will inject the count here -->
                <span class="stat-value" id="countNew">0</span>
            </div>
            <div class="stat-card stat-progress">
                <span class="stat-label">IN PROGRESS</span>
                <!-- JS will inject the count here -->
                <span class="stat-value" id="countProgress">0</span>
            </div>
            <div class="stat-card stat-completed">
                <span class="stat-label">COMPLETED TODAY</span>
                <!-- JS will inject the count here -->
                <span class="stat-value" id="countCompleted">0</span>
            </div>
        </section>

        <!-- Dynamic Orders Table -->
        <section class="orders-table-wrapper">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Items</th>
                        <th>Status</th>
                        <th class="actions-col">Actions</th>
                    </tr>
                </thead>
                <!-- JS will inject the rows here -->
                <tbody id="kitchenOrderTable">
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 40px; color: #9ca3af;">
                            <i class="fas fa-spinner fa-spin"></i> Loading kitchen queue...
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>
    </main>

    <!-- SweetAlert2 for beautiful popups -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Global JS (handles logout) -->
    <script src="assets/js/app.js"></script>
    
    <!-- Kitchen Logic JS (handles the real-time fetching) -->
    <script src="js/kitchen_dashboard.js"></script>
</body>
</html>