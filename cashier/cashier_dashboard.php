<?php
// cashier/cashier_dashboard.php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Cashier' && $_SESSION['role'] !== 'Administrator')) {
    echo "<script>window.location.href = '../index.html';</script>";
    exit();
}

$cashierName = htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Cashier', ENT_QUOTES);
$cashierRole = htmlspecialchars($_SESSION['role'], ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OrderPilot - Cashier POS Dashboard</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom Cashier Dashboard CSS -->
    <link rel="stylesheet" href="../css/cashier_dashboard.css?v=<?php echo time(); ?>">
</head>
<body>

<header class="op-header">
    <div class="op-header-row">
        <div class="op-logo">
            <i class="fas fa-utensils"></i> OrderPilot
        </div>
        <div class="op-header-right">
            <span class="op-welcome">Welcome, <strong><?= $cashierName ?></strong> (<?= $cashierRole ?>)</span>
            <button type="button" onclick="logout()" class="btn btn-op-logout">
                <i class="fas fa-sign-out-alt me-1"></i> Log Out
            </button>
        </div>
    </div>
    <h1 class="op-title">Cashier POS <span class="op-title-light">Dashboard</span></h1>
</header>

<div class="op-body">
    <!-- Stat Cards -->
    <div class="op-stats">
        <div class="stat-card stat-sales">
            <div class="stat-label">Today's Sales</div>
            <div class="stat-value">RM <span id="statSales">0.00</span></div>
        </div>
        <div class="stat-card stat-pending">
            <div class="stat-label">Pending Orders</div>
            <div class="stat-value" id="statPending">0</div>
        </div>
        <div class="stat-card stat-completed" id="statCompletedCard" style="cursor: pointer;" title="Click to view history">
            <div class="stat-label">Completed Orders <i class="fas fa-external-link-alt ms-1"></i></div>
            <div class="stat-value" id="statCompleted">0</div>
        </div>
    </div>

    <!-- Active Orders Table Section -->
    <div class="op-card mb-5">
        <div class="op-card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Active Customer Orders</h5>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <!-- Search Bar -->
                <div class="input-group" style="max-width: 220px;">
                    <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="orderSearchInput" class="form-control" placeholder="Search Order ID / Item...">
                </div>
                
                <!-- Filter Dropdown -->
                <select id="orderStatusFilter" class="form-select" style="max-width: 180px;">
                    <option value="ALL">All Statuses</option>
                    <option value="pending_approval">Pending Approval</option>
                    <option value="new">In Queue</option>
                    <option value="in_progress">Getting Made</option>
                    <option value="paid">Paid Only</option>
                    <option value="unpaid">Unpaid Only</option>
                </select>

                <button type="button" class="btn btn-outline-secondary fw-bold" id="btnViewCompleted">
                    <i class="fas fa-clock-rotate-left me-1"></i> Completed Orders
                </button>
                <button type="button" class="btn btn-op-primary fw-bold" id="btnNewWalkIn">
                    <i class="fas fa-plus me-1"></i> Take Walk-in Order
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table op-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Time</th>
                        <th>Kitchen Status</th>
                        <th>Payment</th>
                        <th>Total</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody id="cashierOrderTable">
                    <tr><td colspan="6" class="text-center py-4"><i class="fas fa-spinner fa-spin me-2"></i>Loading orders...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ======================= FULLSCREEN WALK-IN POS ORDERING MODAL ======================= -->
<div class="modal fade" id="walkInPOSModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content bg-light">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fw-bold"><i class="fas fa-cash-register me-2 text-warning"></i>Take Walk-in Order (Cashier Terminal)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" style="overflow-y: auto;">
                <div class="row g-4">
                    <div class="col-lg-8 col-md-7">
                        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                            <div class="input-group" style="max-width: 350px;">
                                <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" id="posMenuSearch" class="form-control" placeholder="Search menu items...">
                            </div>
                            <div class="d-flex gap-1 overflow-auto py-1" id="posCategoryFilters">
                                <button class="btn btn-sm btn-primary rounded-pill px-3 active" onclick="filterPOSMenu('All', this)">All</button>
                            </div>
                        </div>

                        <div class="row g-3" id="posMenuGrid">
                            <div class="col-12 text-center py-5 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Loading Menu...</div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-5">
                        <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 15px;">
                            <div class="card-header bg-white border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                                <h5 class="fw-bold mb-0"><i class="fas fa-shopping-cart text-primary me-2"></i>Order Cart</h5>
                                <button class="btn btn-sm btn-outline-danger" onclick="clearPOSCart()"><i class="fas fa-trash-can me-1"></i>Clear</button>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush mb-3" id="posCartList" style="max-height: 380px; overflow-y: auto;">
                                    <li class="list-group-item text-center text-muted py-4">Cart is empty</li>
                                </ul>
                                <hr>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="fs-5 fw-bold text-muted">Total</span>
                                    <span class="fs-3 fw-bold text-success">RM <span id="posCartTotal">0.00</span></span>
                                </div>
                                <button class="btn btn-success btn-lg w-100 fw-bold py-3" id="btnPOSCheckout" onclick="openPOSCheckout()" disabled>
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

<!-- ======================= ITEM OPTIONS MODAL ======================= -->
<div class="modal fade" id="posItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 bg-light">
                <h5 class="fw-bold mb-0" id="posModalItemName">Item Name</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="posModalItemId">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fs-6">Price per unit</span>
                    <span class="fw-bold fs-4 text-primary">RM <span id="posModalItemPrice">0.00</span></span>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Quantity</label>
                    <div class="input-group input-group-lg" style="width: 160px;">
                        <button class="btn btn-outline-secondary" type="button" onclick="changePOSModalQty(-1)"><i class="fas fa-minus"></i></button>
                        <input type="text" class="form-control text-center fw-bold bg-white" id="posModalItemQty" value="1" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="changePOSModalQty(1)"><i class="fas fa-plus"></i></button>
                    </div>
                </div>
                <div class="mb-2">
                    <label for="posModalItemRemarks" class="form-label fw-bold">Special Remarks (Optional)</label>
                    <textarea class="form-control" id="posModalItemRemarks" rows="2" placeholder="e.g., Less spicy, no ice, extra sauce..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 bg-light">
                <button type="button" class="btn btn-primary w-100 py-2 fw-bold" onclick="confirmPOSAddToCart()">Add to Cart</button>
            </div>
        </div>
    </div>
</div>

<!-- ======================= POS CHECKOUT & PAYMENT MODAL ======================= -->
<div class="modal fade" id="posCheckoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 bg-light">
                <h5 class="fw-bold mb-0"><i class="fas fa-money-bill-wave me-2 text-success"></i>Confirm & Collect Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <ul class="list-group list-group-flush mb-3" id="posCheckoutSummaryList"></ul>
                <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                    <h5 class="fw-bold mb-0">Total Amount</h5>
                    <h3 class="fw-bold text-success mb-0">RM <span id="posCheckoutTotal">0.00</span></h3>
                </div>
                <div class="mt-3">
                    <label for="posPaymentMethod" class="form-label fw-bold">Payment Method Received</label>
                    <select class="form-select form-select-lg" id="posPaymentMethod">
                        <option value="Cash">Cash</option>
                        <option value="Card">Card</option>
                        <option value="E-Wallet">E-Wallet</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 bg-light">
                <button type="button" class="btn btn-success w-100 py-3 fw-bold fs-5" id="btnPOSPlaceOrder" onclick="submitPOSOrder()">
                    <i class="fas fa-check me-2"></i> Paid & Submit to Kitchen
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ======================= ORDER DETAILS MODAL ======================= -->
<div class="modal fade" id="orderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-0">
                <h5 class="modal-title fw-bold" id="modalOrderTitle">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted">Kitchen Status: <span id="modalKitchenStatus" class="fw-bold text-dark"></span></span>
                    <span class="text-muted">Payment: <span id="modalPaymentStatus" class="fw-bold text-dark"></span></span>
                </div>
                <hr>
                <ul class="list-group list-group-flush mb-3" id="modalItemList"></ul>
                <hr>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <h5 class="fw-bold mb-0">Grand Total</h5>
                    <h3 class="fw-bold text-success mb-0">RM <span id="modalTotalAmount">0.00</span></h3>
                </div>
                <input type="hidden" id="modalOrderId">
            </div>
            <div class="modal-footer bg-light border-0" id="modalFooterActions"></div>
        </div>
    </div>
</div>

<!-- ======================= EDIT ORDER MODAL ======================= -->
<div class="modal fade" id="editOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-0">
                <h5 class="modal-title fw-bold" id="editOrderModalTitle">Edit Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="editOrderId">
                <div class="table-responsive mb-3">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th style="width: 140px;">Qty</th>
                                <th>Special Remarks</th>
                                <th style="text-align: right;">Subtotal</th>
                                <th style="text-align: center; width: 60px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="editOrderItemsList"></tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center border-top pt-3">
                    <h5 class="fw-bold mb-0">Updated Total</h5>
                    <h3 class="fw-bold text-success mb-0">RM <span id="editOrderGrandTotal">0.00</span></h3>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning fw-bold" id="btnSaveEditedOrder" onclick="saveOrderEdits()">
                    <i class="fas fa-save me-1"></i> Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ======================= COMPLETED ORDERS HISTORY MODAL ======================= -->
<div class="modal fade" id="completedModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-0 d-flex justify-content-between align-items-center">
                <h5 class="modal-title fw-bold text-dark"><i class="fas fa-check-circle text-success me-2"></i>Completed Orders History</h5>
                <div>
                    <button type="button" class="btn type="button" class="btn btn-sm btn-outline-danger me-2 fw-bold" id="btnClearAllCompleted">
                        <i class="fas fa-trash-can me-1"></i> Clear All Completed
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table op-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Time</th>
                                <th>Payment</th>
                                <th>Total</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="completedOrderTable">
                            <tr><td colspan="5" class="text-center py-4"><i class="fas fa-spinner fa-spin me-2"></i>Loading completed orders...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle (Includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Logout Helper Function -->
<script>
function logout() {
    fetch('../api/logout_handler.php')
        .then(() => window.location.href = '../index.html')
        .catch(() => window.location.href = '../index.html');
}
</script>

<!-- Cashier Dashboard Logic JS -->
<script src="../js/cashier_dashboard.js?v=<?php echo time(); ?>"></script>
</body>
</html>