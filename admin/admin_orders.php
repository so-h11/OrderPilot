<?php
// admin/admin_orders.php
session_start();

$activePage = 'orders';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>OrderPilot - Master Orders Management</title>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="page-title mb-0">Master Orders Control</h1>
            <div>
                <button class="btn btn-outline-danger me-2 fw-bold" id="btnAdminClearCompleted">
                    <i class="fas fa-trash-can me-1"></i> Clear Completed
                </button>
                <button class="btn btn-primary fw-bold" id="btnAdminTakeOrder">
                    <i class="fas fa-plus me-1"></i> + New Walk-in Order
                </button>
            </div>
        </div>

        <!-- Real-time Order Metrics -->
        <section class="stats-row mb-4">
            <div class="stat-card stat-sales">
                <span class="stat-label">TODAY'S REVENUE</span>
                <span class="stat-value">RM <span id="adminStatSales">0.00</span></span>
            </div>
            <div class="stat-card stat-pending">
                <span class="stat-label">PENDING APPROVAL</span>
                <span class="stat-value" id="adminStatPending">0</span>
            </div>
            <div class="stat-card stat-staff">
                <span class="stat-label">KITCHEN QUEUE</span>
                <span class="stat-value" id="adminStatKitchen">0</span>
            </div>
            <div class="stat-card stat-completed">
                <span class="stat-label">COMPLETED ORDERS</span>
                <span class="stat-value" id="adminStatCompleted">0</span>
            </div>
        </section>

        <!-- Orders Table Panel -->
        <section class="panel">
            <div class="panel-header d-flex flex-wrap justify-content-between align-items-center gap-3">
                <h2>All Orders Register</h2>
                <div class="d-flex gap-2">
                    <input type="text" id="adminOrderSearch" class="form-control form-control-sm" placeholder="Search Order ID..." style="width: 200px;">
                    <select id="adminStatusFilter" class="form-select form-select-sm" style="width: 180px;">
                        <option value="All">All Statuses</option>
                        <option value="pending">Pending Approval</option>
                        <option value="new">In Queue (New)</option>
                        <option value="in_progress">Preparing</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive mt-3">
                <table class="panel-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Time</th>
                            <th>Kitchen Status</th>
                            <th>Payment Status</th>
                            <th>Total Amount</th>
                            <th class="actions-col">Actions / Controls</th>
                        </tr>
                    </thead>
                    <tbody id="adminOrderTableBody">
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="fas fa-spinner fa-spin me-2"></i>Loading live orders...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<!-- ======================= ORDER DETAILS / RECEIPT INSPECTOR MODAL ======================= -->
<div class="modal fade" id="adminOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-0">
                <h5 class="modal-title fw-bold" id="adminModalOrderTitle">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted">Kitchen Status: <span id="adminModalKitchenStatus" class="fw-bold text-dark"></span></span>
                    <span class="text-muted">Payment: <span id="adminModalPaymentStatus" class="fw-bold text-dark"></span></span>
                </div>
                <hr>
                <ul class="list-group list-group-flush mb-3" id="adminModalItemList"></ul>
                <hr>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <h5 class="fw-bold mb-0">Grand Total</h5>
                    <h3 class="fw-bold text-success mb-0">RM <span id="adminModalTotalAmount">0.00</span></h3>
                </div>
            </div>
            <div class="modal-footer bg-light border-0" id="adminModalFooterActions"></div>
        </div>
    </div>
</div>

<!-- ======================= FULLSCREEN WALK-IN POS MODAL ======================= -->
<div class="modal fade" id="adminPOSModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content bg-light">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fw-bold"><i class="fas fa-cash-register me-2 text-warning"></i>Admin Walk-in Terminal</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" style="overflow-y: auto;">
                <div class="row g-4">
                    <!-- Menu Side -->
                    <div class="col-lg-8 col-md-7">
                        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                            <div class="input-group" style="max-width: 350px;">
                                <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" id="adminPOSMenuSearch" class="form-control" placeholder="Search menu items...">
                            </div>
                            <div class="d-flex gap-1 overflow-auto py-1" id="adminPOSCategoryFilters"></div>
                        </div>
                        <div class="row g-3" id="adminPOSMenuGrid"></div>
                    </div>

                    <!-- Cart Side -->
                    <div class="col-lg-4 col-md-5">
                        <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 15px;">
                            <div class="card-header bg-white border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                                <h5 class="fw-bold mb-0"><i class="fas fa-shopping-cart text-primary me-2"></i>Order Cart</h5>
                                <button class="btn btn-sm btn-outline-danger" onclick="window.clearAdminPOSCart()"><i class="fas fa-trash-can me-1"></i>Clear</button>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush mb-3" id="adminPOSCartList" style="max-height: 380px; overflow-y: auto;"></ul>
                                <hr>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="fs-5 fw-bold text-muted">Total</span>
                                    <span class="fs-3 fw-bold text-success">RM <span id="adminPOSCartTotal">0.00</span></span>
                                </div>
                                <button class="btn btn-success btn-lg w-100 fw-bold py-3" id="btnAdminPOSCheckout" onclick="window.openAdminPOSCheckout()" disabled>
                                    <i class="fas fa-check-circle me-2"></i>Collect Payment & Submit
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ======================= POS ITEM OPTIONS MODAL ======================= -->
<div class="modal fade" id="adminPOSItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 bg-light">
                <h5 class="fw-bold mb-0" id="adminPOSModalItemName">Item Name</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="adminPOSModalItemId">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fs-6">Price per unit</span>
                    <span class="fw-bold fs-4 text-primary">RM <span id="adminPOSModalItemPrice">0.00</span></span>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Quantity</label>
                    <div class="input-group input-group-lg" style="width: 160px;">
                        <button class="btn btn-outline-secondary" type="button" onclick="window.changeAdminPOSModalQty(-1)"><i class="fas fa-minus"></i></button>
                        <input type="text" class="form-control text-center fw-bold bg-white" id="adminPOSModalItemQty" value="1" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="window.changeAdminPOSModalQty(1)"><i class="fas fa-plus"></i></button>
                    </div>
                </div>
                <div class="mb-2">
                    <label for="adminPOSModalItemRemarks" class="form-label fw-bold">Special Remarks</label>
                    <textarea class="form-control" id="adminPOSModalItemRemarks" rows="2" placeholder="e.g. Less spicy..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 bg-light">
                <button type="button" class="btn btn-primary w-100 py-2 fw-bold" onclick="window.confirmAdminPOSAddToCart()">Add to Cart</button>
            </div>
        </div>
    </div>
</div>

<!-- ======================= POS CHECKOUT MODAL ======================= -->
<div class="modal fade" id="adminPOSCheckoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 bg-light">
                <h5 class="fw-bold mb-0"><i class="fas fa-money-bill-wave me-2 text-success"></i>Confirm & Collect Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <ul class="list-group list-group-flush mb-3" id="adminPOSCheckoutSummaryList"></ul>
                <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                    <h5 class="fw-bold mb-0">Total Amount</h5>
                    <h3 class="fw-bold text-success mb-0">RM <span id="adminPOSCheckoutTotal">0.00</span></h3>
                </div>
                <div class="mt-3">
                    <label for="adminPOSPaymentMethod" class="form-label fw-bold">Payment Method Received</label>
                    <select class="form-select form-select-lg" id="adminPOSPaymentMethod">
                        <option value="Cash">Cash</option>
                        <option value="Card">Card</option>
                        <option value="E-Wallet">E-Wallet</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 bg-light">
                <button type="button" class="btn btn-success w-100 py-3 fw-bold fs-5" id="btnAdminPOSSubmit" onclick="window.submitAdminPOSOrder()">
                    <i class="fas fa-check me-2"></i> Paid & Submit to Kitchen
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/admin.js?v=<?php echo time(); ?>"></script>
</body>
</html>