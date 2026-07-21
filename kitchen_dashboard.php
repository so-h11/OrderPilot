<?php
session_start();

// Optional: guard the page so only logged-in kitchen staff can view it.
// Uncomment the block below once you have a real login system in place.
/*
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'kitchen_staff') {
    header('Location: index.html');
    exit;
}
*/

$staffName = $_SESSION['staff_name'] ?? 'Kitchen Staff';

// --- Demo data -------------------------------------------------------
// Replace this with a real database query, e.g.:
// $orders = $pdo->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll();
$orders = [
    [
        'id'          => '#012',
        'title'       => 'Nasi Lemak Ayam Goreng',
        'subtitle'    => 'Omah Lemak Marvan',
        'status'      => 'new',
        'status_label'=> 'New Order',
    ],
    [
        'id'          => '#011',
        'title'       => 'Nasi Goreng',
        'subtitle'    => 'Teh Tarik',
        'status'      => 'in_progress',
        'status_label'=> 'In Progress',
    ],
    [
        'id'          => '#010',
        'title'       => 'Mee Goreng Mamak',
        'subtitle'    => 'Mee Goreng Mamak',
        'status'      => 'completed',
        'status_label'=> 'Completed',
    ],
];

// Counts for the summary cards
$newCount        = count(array_filter($orders, fn($o) => $o['status'] === 'new'));
$inProgressCount = count(array_filter($orders, fn($o) => $o['status'] === 'in_progress'));
$completedCount  = count(array_filter($orders, fn($o) => $o['status'] === 'completed'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>OrderPilot - Kitchen Staff Dashboard</title>
<link rel="stylesheet" href="css/kitchen_dashboard.css">
</head>
<body>

<header class="topbar">
    <div class="brand">
        <span class="brand-icon">🍴</span>
        <span class="brand-name">OrderPilot</span>
    </div>
    <div class="topbar-right">
        <span class="welcome-text">Welcome, <?php echo htmlspecialchars($staffName); ?></span>
        <button type="button" id="logoutBtn" class="btn btn-logout" onclick="logout()">Log Out</button>
    </div>
</header>

<main class="dashboard-container">

    <h1 class="dashboard-title">
        <span class="title-icon">🖥️</span> Kitchen Staff Dashboard
    </h1>

    <section class="stats-row">
        <div class="stat-card stat-new">
            <span class="stat-label">NEW ORDERS</span>
            <span class="stat-value"><?php echo $newCount; ?></span>
        </div>
        <div class="stat-card stat-progress">
            <span class="stat-label">IN PROGRESS</span>
            <span class="stat-value"><?php echo $inProgressCount; ?></span>
        </div>
        <div class="stat-card stat-completed">
            <span class="stat-label">COMPLETED ORDERS</span>
            <span class="stat-value"><?php echo $completedCount; ?></span>
        </div>
    </section>

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
            <tbody>
            <?php foreach ($orders as $order): ?>
                <tr data-order-id="<?php echo htmlspecialchars($order['id']); ?>">
                    <td class="order-id"><?php echo htmlspecialchars($order['id']); ?></td>
                    <td class="order-items">
                        <div class="item-title"><?php echo htmlspecialchars($order['title']); ?></div>
                        <div class="item-subtitle"><?php echo htmlspecialchars($order['subtitle']); ?></div>
                    </td>
                    <td>
                        <span class="status-badge status-<?php echo $order['status']; ?>">
                            <?php echo htmlspecialchars($order['status_label']); ?>
                        </span>
                    </td>
                    <td class="actions-col">
                        <?php if ($order['status'] === 'new'): ?>
                            <button class="btn btn-start" data-action="start">Start Order</button>

                        <?php elseif ($order['status'] === 'in_progress'): ?>
                            <button class="icon-btn icon-btn-complete" data-action="complete" title="Mark Completed">✓</button>
                            <button class="icon-btn icon-btn-refresh" data-action="refresh" title="Reset Order">⟳</button>
                            <button class="icon-btn icon-btn-delete" data-action="delete" title="Cancel Order">🗑</button>

                        <?php else: ?>
                            <button class="icon-btn icon-btn-more" data-action="more" title="More options">•••</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/app.js"></script>
<script src="js/kitchen_dashboard.js"></script>
</body>
</html>