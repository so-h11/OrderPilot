<?php
session_start();

// Optional: guard this page once a real login system exists.
/*
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.html');
    exit;
}
*/

$activePage = 'dashboard';

// --- Demo data --------------------------------------------------------
// Replace all of this with real database queries.
$todaysSales      = 0.00;
$pendingOrders    = 0;
$completedOrders  = 0;
$totalStaff       = 2;

$staffList = [
    ['name' => 'Ali',   'role' => 'Cashier'],
    ['name' => 'Sarah', 'role' => 'Kitchen Staff'],
];

$menuList = [
    [
        'name'        => 'Nasi Lemak Ayam Goreng',
        'description' => 'Coconut rice with fried chicken.',
        'price'       => 12.50,
        'available'   => true,
    ],
];

// Last 7 days of sales for the mini bar chart (RM)
$salesOverview = [
    ['label' => 'Mon', 'value' => 320],
    ['label' => 'Tue', 'value' => 410],
    ['label' => 'Wed', 'value' => 380],
    ['label' => 'Thu', 'value' => 620],
    ['label' => 'Fri', 'value' => 500],
    ['label' => 'Sat', 'value' => 560],
    ['label' => 'Sun', 'value' => 260],
];
$maxSalesValue = max(array_column($salesOverview, 'value'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>OrderPilot - Admin Dashboard</title>
<link rel="stylesheet" href="../css/admin.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/app.js"></script>
</head>
<body>

<?php include 'admin_header.php'; ?>

<div class="app-body">

    <?php include 'admin_sidebar.php'; ?>

    <main class="main-content">

        <h1 class="page-title">Admin Dashboard</h1>

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

            <!-- Staff Management -->
            <div class="panel">
                <div class="panel-header">
                    <h2>Staff Management</h2>
                    <button class="btn btn-primary" data-action="add-staff">+ Add Staff</button>
                </div>
                <table class="panel-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Role</th>
                            <th class="actions-col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($staffList as $staff): ?>
                        <tr>
                            <td class="cell-name"><?php echo htmlspecialchars($staff['name']); ?></td>
                            <td><?php echo htmlspecialchars($staff['role']); ?></td>
                            <td class="actions-col">
                                <button class="icon-btn icon-btn-edit" title="Edit">✎</button>
                                <button class="icon-btn icon-btn-delete" title="Delete">🗑</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Menu Management -->
            <div class="panel">
                <div class="panel-header">
                    <h2>Menu Management</h2>
                    <button class="btn btn-primary" data-action="add-menu-item">+ Add New Item</button>
                </div>
                <table class="panel-table">
                    <thead>
                        <tr>
                            <th>Food Item</th>
                            <th>Price (RM)</th>
                            <th class="actions-col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($menuList as $item): ?>
                        <tr>
                            <td>
                                <div class="cell-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="cell-subtitle"><?php echo htmlspecialchars($item['description']); ?></div>
                            </td>
                            <td><?php echo number_format($item['price'], 2); ?></td>
                            <td class="actions-col">
                                <span class="status-badge <?php echo $item['available'] ? 'status-available' : 'status-unavailable'; ?>">
                                    <?php echo $item['available'] ? 'Available' : 'Unavailable'; ?>
                                </span>
                                <button class="icon-btn icon-btn-delete" title="Delete">🗑</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Menu Management (detailed pricing / class view) -->
            <div class="panel">
                <div class="panel-header">
                    <h2>Menu Management</h2>
                    <button class="btn btn-primary" data-action="add-menu-item">+ Add New Item</button>
                </div>
                <table class="panel-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th class="actions-col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($menuList as $item): ?>
                        <tr>
                            <td>
                                <div class="cell-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="cell-subtitle"><?php echo htmlspecialchars($item['description']); ?></div>
                            </td>
                            <td><?php echo number_format($item['price'], 2); ?></td>
                            <td>
                                <span class="status-badge <?php echo $item['available'] ? 'status-available' : 'status-unavailable'; ?>">
                                    <?php echo $item['available'] ? 'Available' : 'Unavailable'; ?>
                                </span>
                            </td>
                            <td class="actions-col">
                                <button class="icon-btn icon-btn-edit" title="Edit">✎</button>
                                <button class="icon-btn icon-btn-delete" title="Delete">🗑</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Sales Overview -->
            <div class="panel">
                <div class="panel-header">
                    <h2>Sales Overview <span class="panel-subheading">(Last 7 Days)</span></h2>
                </div>
                <div class="chart-wrapper">
                    <div class="chart-y-axis">
                        <span><?php echo number_format($maxSalesValue); ?></span>
                        <span><?php echo number_format($maxSalesValue * 0.75); ?></span>
                        <span><?php echo number_format($maxSalesValue * 0.5); ?></span>
                        <span><?php echo number_format($maxSalesValue * 0.25); ?></span>
                        <span>0</span>
                    </div>
                    <div class="chart-bars">
                        <?php foreach ($salesOverview as $day): ?>
                            <div class="chart-bar-col">
                                <div class="chart-bar"
                                     style="height: <?php echo ($day['value'] / $maxSalesValue) * 100; ?>%;"
                                     title="RM <?php echo number_format($day['value'], 2); ?>"></div>
                                <span class="chart-bar-label"><?php echo $day['label']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </section>

    </main>
</div>

<script src="js/admin.js"></script>
</body>
</html>