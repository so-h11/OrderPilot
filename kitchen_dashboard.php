<?php
// kitchen_dashboard.php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Kitchen Staff') {
    echo "<script>window.location.href = 'index.html';</script>";
    exit();
}

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
    <!-- Custom Kitchen CSS with Cache Biter -->
    <link rel="stylesheet" href="css/kitchen_dashboard.css?v=<?php echo time(); ?>">
</head>
<body>

    <!-- Header Navigation Bar -->
    <header class="topbar">
        <div class="brand">
            <i class="fas fa-utensils"></i> OrderPilot
        </div>
        <div class="topbar-right">
            <span class="welcome-text">Welcome, <strong><?php echo htmlspecialchars($staffName); ?></strong></span>
            <div class="logout-container">
                <button type="button" id="logoutBtn" class="btn btn-logout" onclick="logout()">
                    <i class="fas fa-sign-out-alt me-1"></i> Log Out
                </button>
            </div>
        </div>
    </header>

    <main class="dashboard-container">
        <div class="dashboard-header-row">
            <h1 class="dashboard-title">
                <i class="fas fa-fire-burner"></i> Kitchen Staff Dashboard
            </h1>
            <button type="button" class="btn btn-clear-all" onclick="clearAllCompleted(this)">
                <i class="fas fa-broom me-1"></i> Clear Completed
            </button>
        </div>

        <!-- Real-Time Statistic Cards -->
        <section class="stats-row">
            <div class="stat-card stat-new">
                <span class="stat-label">NEW ORDERS</span>
                <span class="stat-value" id="countNew">0</span>
            </div>
            <div class="stat-card stat-progress">
                <span class="stat-label">IN PROGRESS</span>
                <span class="stat-value" id="countProgress">0</span>
            </div>
            <div class="stat-card stat-completed">
                <span class="stat-label">COMPLETED TODAY</span>
                <span class="stat-value" id="countCompleted">0</span>
            </div>
        </section>

        <!-- Dynamic Orders Table -->
        <section class="orders-table-wrapper">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Items Summary</th>
                        <th>Status</th>
                        <th class="actions-col">Actions</th>
                    </tr>
                </thead>
                <tbody id="kitchenOrderTable">
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 40px; color: #6b7280;">
                            <i class="fas fa-spinner fa-spin"></i> Loading kitchen queue...
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>
    </main>

    <!-- SweetAlert2 for Popups -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Global JS -->
    <script src="assets/js/app.js"></script>
    
    <!-- Kitchen Logic JS with Cache Buster -->
    <script src="js/kitchen_dashboard.js?v=<?php echo time(); ?>"></script>
</body>
</html>