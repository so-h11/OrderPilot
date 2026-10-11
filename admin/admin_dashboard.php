<?php
// admin/admin_dashboard.php
session_start();

// Dynamic db_connect.php path discovery
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

if ($dbConnectFound) {
    require_once $dbConnectFound;
}

$activePage = 'dashboard';

// Default Fallback Metrics
$todaysSales     = 0.00;
$pendingOrders   = 0;
$completedOrders = 0;
$totalStaff      = 0;
$staffList       = [];
$menuList        = [];
$salesOverview   = [];

if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {

    // 1. Today's Total Revenue (All 'Paid' orders today, even if cleared from cashier view)
    $salesSql = "SELECT COALESCE(SUM(total_amount), 0) AS total 
                 FROM orders 
                 WHERE payment_status = 'Paid' AND DATE(created_at) = CURDATE()";
    $salesRes = $conn->query($salesSql);
    if ($salesRes) {
        $todaysSales = (float) ($salesRes->fetch_assoc()['total'] ?? 0);
    }

    // 2. Pending & Active Kitchen Orders
    $pendingSql = "SELECT COUNT(*) AS total 
                   FROM orders 
                   WHERE order_status IN ('pending', 'pending_approval', 'new', 'in_progress', 'preparing')";
    $pendingRes = $conn->query($pendingSql);
    if ($pendingRes) {
        $pendingOrders = (int) ($pendingRes->fetch_assoc()['total'] ?? 0);
    }

    // 3. Completed Orders Today
    $completedSql = "SELECT COUNT(*) AS total 
                      FROM orders 
                      WHERE order_status = 'completed' AND DATE(created_at) = CURDATE()";
    $completedRes = $conn->query($completedSql);
    if ($completedRes) {
        $completedOrders = (int) ($completedRes->fetch_assoc()['total'] ?? 0);
    }

    // 4. Total Active Staff Accounts
    $staffCountSql = "SELECT COUNT(*) AS total 
                      FROM users 
                      WHERE role IN ('Administrator', 'Cashier', 'Kitchen Staff') AND (status IS NULL OR status = 'Active')";
    $staffCountRes = $conn->query($staffCountSql);
    if ($staffCountRes) {
        $totalStaff = (int) ($staffCountRes->fetch_assoc()['total'] ?? 0);
    }

    // 5. Staff Accounts Preview (Latest 5)
    $staffSql = "SELECT full_name, username, role, status 
                 FROM users 
                 WHERE role IN ('Administrator', 'Cashier', 'Kitchen Staff')
                 ORDER BY user_id DESC LIMIT 5";
    $staffRes = $conn->query($staffSql);
    if ($staffRes && $staffRes->num_rows > 0) {
        while ($row = $staffRes->fetch_assoc()) {
            $staffList[] = [
                'name'   => $row['full_name'] ?: $row['username'],
                'role'   => $row['role'],
                'status' => $row['status'] ?? 'Active'
            ];
        }
    }

    // 6. Menu Items Preview (Latest 5)
    $menuSql = "SELECT item_name, description, price, is_available 
                FROM menu_items 
                ORDER BY item_id DESC LIMIT 5";
    $menuRes = $conn->query($menuSql);
    if ($menuRes && $menuRes->num_rows > 0) {
        while ($row = $menuRes->fetch_assoc()) {
            $menuList[] = [
                'name'        => $row['item_name'],
                'description' => $row['description'] ?? '',
                'price'       => (float) $row['price'],
                'available'   => (bool) $row['is_available']
            ];
        }
    }

    // 7. Last 7 Days Revenue Trend (Dynamic Chart)
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $dayLabel = date('D', strtotime($date));

        $chartSql = "SELECT COALESCE(SUM(total_amount), 0) AS daily_total 
                     FROM orders 
                     WHERE payment_status = 'Paid' AND DATE(created_at) = '$date'";
        $chartRes = $conn->query($chartSql);
        $val = 0.00;
        if ($chartRes) {
            $val = (float) ($chartRes->fetch_assoc()['daily_total'] ?? 0);
        }

        $salesOverview[] = [
            'label' => $dayLabel,
            'date'  => date('d M', strtotime($date)),
            'value' => $val
        ];
    }
}

// Ensure chart height calculation avoids division by zero
$maxSalesValue = max(array_column($salesOverview, 'value') ?: [0]);
if ($maxSalesValue <= 0) {
    $maxSalesValue = 100; // Default baseline scale
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>OrderPilot - Admin Dashboard</title>
<link rel="stylesheet" href="../css/admin.css?v=<?php echo time(); ?>">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/app.js"></script>
</head>
<body>

<?php include 'admin_header.php'; ?>

<div class="app-body">

    <?php include 'admin_sidebar.php'; ?>

    <main class="main-content">

        <h1 class="page-title">Admin Dashboard</h1>

        <!-- Real-Time Metrics Row -->
        <section class="stats-row">
            <div class="stat-card stat-sales">
                <span class="stat-label">TODAY'S SALES</span>
                <span class="stat-value">RM <?php echo number_format($todaysSales, 2); ?></span>
            </div>
            <div class="stat-card stat-pending">
                <span class="stat-label">PENDING ORDERS</span>
                <span class="stat-value"><?php echo $pendingOrders; ?></span>
            </div>
            <div class="stat-card stat-completed">
                <span class="stat-label">COMPLETED ORDERS</span>
                <span class="stat-value"><?php echo $completedOrders; ?></span>
            </div>
            <div class="stat-card stat-staff">
                <span class="stat-label">TOTAL STAFF</span>
                <span class="stat-value"><?php echo $totalStaff; ?></span>
            </div>
        </section>

        <section class="panels-grid">

            <!-- Staff Management Overview -->
            <div class="panel">
                <div class="panel-header">
                    <h2>Staff Management</h2>
                    <a href="admin_staff.php" class="btn btn-primary">+ Manage Staff</a>
                </div>
                <table class="panel-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Role</th>
                            <th class="actions-col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($staffList)): ?>
                        <?php foreach ($staffList as $staff): ?>
                            <tr>
                                <td class="cell-name"><?php echo htmlspecialchars($staff['name']); ?></td>
                                <td><?php echo htmlspecialchars($staff['role']); ?></td>
                                <td class="actions-col">
                                    <span class="status-badge <?php echo ($staff['status'] === 'Active') ? 'status-available' : 'status-unavailable'; ?>">
                                        <?php echo htmlspecialchars($staff['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3" class="cell-subtitle" style="text-align: center; padding: 20px;">No staff members found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Menu Management Overview -->
            <div class="panel">
                <div class="panel-header">
                    <h2>Menu Management</h2>
                    <a href="admin_menu.php" class="btn btn-primary">+ Manage Menu</a>
                </div>
                <table class="panel-table">
                    <thead>
                        <tr>
                            <th>Food Item</th>
                            <th>Price (RM)</th>
                            <th class="actions-col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($menuList)): ?>
                        <?php foreach ($menuList as $item): ?>
                            <tr>
                                <td>
                                    <div class="cell-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="cell-subtitle"><?php echo htmlspecialchars($item['description']); ?></div>
                                </td>
                                <td>RM <?php echo number_format($item['price'], 2); ?></td>
                                <td class="actions-col">
                                    <span class="status-badge <?php echo $item['available'] ? 'status-available' : 'status-unavailable'; ?>">
                                        <?php echo $item['available'] ? 'Available' : 'Unavailable'; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3" class="cell-subtitle" style="text-align: center; padding: 20px;">No menu items found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Dynamic 7-Day Revenue Trend Panel -->
            <div class="panel" style="grid-column: span 2;">
                <div class="panel-header">
                    <h2>Sales Overview <span class="panel-subheading">(Last 7 Days - Paid Orders)</span></h2>
                </div>
                <div class="chart-wrapper">
                    <div class="chart-y-axis">
                        <span>RM <?php echo number_format($maxSalesValue); ?></span>
                        <span>RM <?php echo number_format($maxSalesValue * 0.75); ?></span>
                        <span>RM <?php echo number_format($maxSalesValue * 0.5); ?></span>
                        <span>RM <?php echo number_format($maxSalesValue * 0.25); ?></span>
                        <span>RM 0</span>
                    </div>
                    <div class="chart-bars">
                        <?php foreach ($salesOverview as $day): ?>
                            <?php $heightPct = $maxSalesValue > 0 ? ($day['value'] / $maxSalesValue) * 100 : 0; ?>
                            <div class="chart-bar-col">
                                <div class="chart-bar"
                                     style="height: <?php echo max($heightPct, 4); ?>%;"
                                     title="<?php echo $day['date']; ?>: RM <?php echo number_format($day['value'], 2); ?>"></div>
                                <span class="chart-bar-label"><?php echo $day['label']; ?><br><small><?php echo $day['date']; ?></small></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </section>

    </main>
</div>

<script src="../js/admin.js?v=<?php echo time(); ?>"></script>
</body>
</html>