<?php
// admin/admin_reports.php
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

$activePage = 'reports';

// ------------------------------------------------------------------
// Date Range & Preset Filtering Logic
// ------------------------------------------------------------------
$preset    = $_GET['preset'] ?? 'today';
$startDate = $_GET['start_date'] ?? date('Y-m-d');
$endDate   = $_GET['end_date'] ?? date('Y-m-d');

if (isset($_GET['preset'])) {
    switch ($preset) {
        case 'yesterday':
            $startDate = date('Y-m-d', strtotime('-1 day'));
            $endDate   = $startDate;
            break;
        case 'this_week':
            $startDate = date('Y-m-d', strtotime('monday this week'));
            $endDate   = date('Y-m-d');
            break;
        case 'this_month':
            $startDate = date('Y-m-01');
            $endDate   = date('Y-m-d');
            break;
        case 'today':
            $startDate = date('Y-m-d');
            $endDate   = date('Y-m-d');
            break;
        case 'custom':
            // Uses provided $startDate and $endDate
            break;
    }
}

// Ensure proper date comparison bounds
$sqlStart = $startDate . ' 00:00:00';
$sqlEnd   = $endDate . ' 23:59:59';

// ------------------------------------------------------------------
// Real Database Report Queries
// ------------------------------------------------------------------
$totalRevenue   = 0.00;
$totalOrders    = 0;
$avgOrderValue  = 0.00;
$topCategory    = 'N/A';

$dailyBreakdown = [];
$topItems       = [];

if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {

    // 1. Overall Key Performance Indicators (KPIs)
    $kpiSql = "SELECT 
                    COALESCE(SUM(total_amount), 0) AS total_rev,
                    COUNT(*) AS total_count
               FROM orders 
               WHERE payment_status = 'Paid' 
                 AND created_at BETWEEN '$sqlStart' AND '$sqlEnd'";
    $kpiRes = $conn->query($kpiSql);
    if ($kpiRes && $row = $kpiRes->fetch_assoc()) {
        $totalRevenue = (float) $row['total_rev'];
        $totalOrders  = (int) $row['total_count'];
        if ($totalOrders > 0) {
            $avgOrderValue = $totalRevenue / $totalOrders;
        }
    }

    // 2. Identify Top Performing Category
    $catSql = "SELECT c.category_name, SUM(od.quantity) as total_qty
               FROM order_details od
               JOIN orders o ON od.order_id = o.order_id
               JOIN menu_items m ON od.item_id = m.item_id
               LEFT JOIN menu_categories c ON m.category_id = c.category_id
               WHERE o.payment_status = 'Paid'
                 AND o.created_at BETWEEN '$sqlStart' AND '$sqlEnd'
               GROUP BY c.category_id
               ORDER BY total_qty DESC
               LIMIT 1";
    $catRes = $conn->query($catSql);
    if ($catRes && $catRow = $catRes->fetch_assoc()) {
        $topCategory = $catRow['category_name'] ?: 'General';
    }

    // 3. Daily Sales & Payment Method Breakdown
    $dailySql = "SELECT 
                    DATE(created_at) as sale_date,
                    COUNT(*) as order_count,
                    SUM(CASE WHEN LOWER(payment_method) = 'cash' THEN total_amount ELSE 0 END) as cash_total,
                    SUM(CASE WHEN LOWER(payment_method) != 'cash' THEN total_amount ELSE 0 END) as digital_total,
                    SUM(total_amount) as daily_total
                 FROM orders
                 WHERE payment_status = 'Paid'
                   AND created_at BETWEEN '$sqlStart' AND '$sqlEnd'
                 GROUP BY DATE(created_at)
                 ORDER BY sale_date DESC";
    $dailyRes = $conn->query($dailySql);
    if ($dailyRes && $dailyRes->num_rows > 0) {
        while ($row = $dailyRes->fetch_assoc()) {
            $dailyBreakdown[] = $row;
        }
    }

    // 4. Best Selling Menu Items
    $topItemsSql = "SELECT 
                        m.item_name,
                        COALESCE(c.category_name, 'Uncategorized') AS category_name,
                        SUM(od.quantity) as qty_sold,
                        SUM(od.subtotal) as item_revenue
                    FROM order_details od
                    JOIN orders o ON od.order_id = o.order_id
                    JOIN menu_items m ON od.item_id = m.item_id
                    LEFT JOIN menu_categories c ON m.category_id = c.category_id
                    WHERE o.payment_status = 'Paid'
                      AND o.created_at BETWEEN '$sqlStart' AND '$sqlEnd'
                    GROUP BY m.item_id
                    ORDER BY qty_sold DESC
                    LIMIT 10";
    $topItemsRes = $conn->query($topItemsSql);
    if ($topItemsRes && $topItemsRes->num_rows > 0) {
        while ($row = $topItemsRes->fetch_assoc()) {
            $topItems[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>OrderPilot - Sales Reports</title>
<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<!-- Custom Admin CSS -->
<link rel="stylesheet" href="../css/admin.css?v=<?php echo time(); ?>">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/app.js"></script>
</head>
<body>

<?php 
if (file_exists('includes/admin_header.php')) {
    include 'includes/admin_header.php';
} else {
    include 'admin_header.php';
}
?>

<div class="app-body">

    <?php 
    if (file_exists('includes/admin_sidebar.php')) {
        include 'includes/admin_sidebar.php';
    } else {
        include 'admin_sidebar.php';
    }
    ?>

    <main class="main-content">
        <!-- Page Header & Export Controls -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h1 class="page-title mb-1">Sales & Performance Reports</h1>
                <p class="text-muted mb-0">Historical revenue analytics preserved independently from front-of-house clearing operations.</p>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary fw-bold" onclick="window.print()">
                    <i class="fas fa-file-pdf me-1 text-danger"></i> Print / Save PDF
                </button>
                <button type="button" class="btn btn-outline-success fw-bold" onclick="exportReportCSV()">
                    <i class="fas fa-file-excel me-1"></i> Export CSV
                </button>
            </div>
        </div>

        <!-- Date Range & Preset Filter Bar -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-3">
                <form id="reportFilterForm" method="GET" action="admin_reports.php" class="row g-3 align-items-center">
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted mb-1">Date Range Preset</label>
                        <select class="form-select form-select-sm" id="reportRangePreset" name="preset" onchange="handlePresetChange(this.value)">
                            <option value="today" <?php echo ($preset === 'today') ? 'selected' : ''; ?>>Today</option>
                            <option value="yesterday" <?php echo ($preset === 'yesterday') ? 'selected' : ''; ?>>Yesterday</option>
                            <option value="this_week" <?php echo ($preset === 'this_week') ? 'selected' : ''; ?>>This Week</option>
                            <option value="this_month" <?php echo ($preset === 'this_month') ? 'selected' : ''; ?>>This Month</option>
                            <option value="custom" <?php echo ($preset === 'custom') ? 'selected' : ''; ?>>Custom Range</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted mb-1">Start Date</label>
                        <input type="date" class="form-control form-control-sm" id="startDateInput" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted mb-1">End Date</label>
                        <input type="date" class="form-control form-control-sm" id="endDateInput" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold py-2">
                            <i class="fas fa-filter me-1"></i> Apply Report Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Performance Key Indicators -->
        <section class="stats-row mb-4">
            <div class="stat-card stat-sales">
                <span class="stat-label">TOTAL REVENUE</span>
                <span class="stat-value">RM <?php echo number_format($totalRevenue, 2); ?></span>
            </div>
            <div class="stat-card stat-pending">
                <span class="stat-label">ORDERS PROCESSED</span>
                <span class="stat-value"><?php echo $totalOrders; ?></span>
            </div>
            <div class="stat-card stat-completed">
                <span class="stat-label">AVG ORDER VALUE</span>
                <span class="stat-value">RM <?php echo number_format($avgOrderValue, 2); ?></span>
            </div>
            <div class="stat-card stat-staff">
                <span class="stat-label">TOP CATEGORY</span>
                <span class="stat-value"><?php echo htmlspecialchars($topCategory); ?></span>
            </div>
        </section>

        <!-- Detailed Reports Grid -->
        <section class="panels-grid">

            <!-- Daily Revenue Summary Table -->
            <div class="panel">
                <div class="panel-header">
                    <h2>Daily Revenue Breakdown</h2>
                </div>
                <div class="table-responsive">
                    <table class="panel-table" id="dailyReportTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Orders</th>
                                <th>Cash</th>
                                <th>Digital / Card</th>
                                <th style="text-align: right;">Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($dailyBreakdown)): ?>
                                <?php foreach ($dailyBreakdown as $row): ?>
                                    <tr>
                                        <td class="cell-name"><?php echo date('d M Y', strtotime($row['sale_date'])); ?></td>
                                        <td><strong><?php echo $row['order_count']; ?></strong></td>
                                        <td>RM <?php echo number_format($row['cash_total'], 2); ?></td>
                                        <td>RM <?php echo number_format($row['digital_total'], 2); ?></td>
                                        <td style="text-align: right;" class="fw-bold text-success">
                                            RM <?php echo number_format($row['daily_total'], 2); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No paid transactions found for the selected date range.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Best-Selling Items Table -->
            <div class="panel">
                <div class="panel-header">
                    <h2>Top Selling Menu Items</h2>
                </div>
                <div class="table-responsive">
                    <table class="panel-table">
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th>Qty Sold</th>
                                <th style="text-align: right;">Total Sales</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($topItems)): ?>
                                <?php foreach ($topItems as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="cell-name"><?php echo htmlspecialchars($item['item_name']); ?></div>
                                        </td>
                                        <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($item['category_name']); ?></span></td>
                                        <td><strong><?php echo (int) $item['qty_sold']; ?> units</strong></td>
                                        <td style="text-align: right;" class="fw-bold text-success">
                                            RM <?php echo number_format($item['item_revenue'], 2); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No item sales recorded for this period.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </section>

    </main>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/admin.js?v=<?php echo time(); ?>"></script>

<script>
function handlePresetChange(val) {
    if (val !== 'custom') {
        document.getElementById('reportFilterForm').submit();
    }
}

function exportReportCSV() {
    let csv = [];
    csv.push(["Report Date Range", "<?php echo $startDate; ?> to <?php echo $endDate; ?>"]);
    csv.push(["Total Revenue", "RM <?php echo number_format($totalRevenue, 2); ?>"]);
    csv.push(["Orders Processed", "<?php echo $totalOrders; ?>"]);
    csv.push([]);
    csv.push(["Date", "Orders Count", "Cash Sales (RM)", "Digital/Card Sales (RM)", "Total Daily Sales (RM)"]);

    <?php foreach ($dailyBreakdown as $row): ?>
    csv.push([
        "<?php echo $row['sale_date']; ?>",
        "<?php echo $row['order_count']; ?>",
        "<?php echo number_format($row['cash_total'], 2, '.', ''); ?>",
        "<?php echo number_format($row['digital_total'], 2, '.', ''); ?>",
        "<?php echo number_format($row['daily_total'], 2, '.', ''); ?>"
    ]);
    <?php endforeach; ?>

    let csvContent = "data:text/csv;charset=utf-8," + csv.map(e => e.join(",")).join("\n");
    let encodedUri = encodeURI(csvContent);
    let link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "OrderPilot_Sales_Report_<?php echo $startDate; ?>_to_<?php echo $endDate; ?>.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>
</body>
</html>