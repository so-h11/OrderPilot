<?php
// cashier_dashboard.php
session_start();
include 'includes/header.php';
include 'includes/navbar.php';

// Basic Security Check
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Cashier' && $_SESSION['role'] !== 'Administrator')) {
    echo "<script>window.location.href = 'index.html';</script>";
    exit();
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="fas fa-desktop me-2 text-primary"></i> Cashier POS Dashboard</h4>
    </div>

    <!-- Stat Cards -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card text-white bg-success shadow-sm h-100 border-0">
                <div class="card-body">
                    <h6 class="card-title text-uppercase fw-bold opacity-75">Today's Sales</h6>
                    <h2 class="mb-0 fw-bold">RM <span id="statSales">0.00</span></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card text-white bg-warning shadow-sm h-100 border-0">
                <div class="card-body">
                    <h6 class="card-title text-uppercase fw-bold opacity-75 text-dark">Pending Orders</h6>
                    <h2 class="mb-0 fw-bold text-dark" id="statPending">0</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card text-white bg-info shadow-sm h-100 border-0">
                <div class="card-body">
                    <h6 class="card-title text-uppercase fw-bold opacity-75">Completed Orders</h6>
                    <h2 class="mb-0 fw-bold" id="statCompleted">0</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Orders Table -->
    <div class="card shadow-sm border-0 mb-5">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom-0">
            <h5 class="mb-0 fw-bold text-secondary">Active Customer Orders</h5>
            <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">
                <i class="fas fa-plus me-1"></i> New Walk-in Order
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted">
                        <tr>
                            <th class="ps-4">Order ID</th>
                            <th>Time</th>
                            <th>Kitchen Status</th>
                            <th>Payment</th>
                            <th>Total</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody id="cashierOrderTable">
                        <tr><td colspan="6" class="text-center py-4">Loading orders...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ======================= ORDER DETAILS & PAYMENT MODAL ======================= -->
<div class="modal fade" id="orderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-0">
                <h5 class="modal-title fw-bold" id="modalOrderTitle">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted">Status: <span id="modalKitchenStatus" class="fw-bold text-dark"></span></span>
                    <span class="text-muted">Payment: <span id="modalPaymentStatus" class="fw-bold text-dark"></span></span>
                </div>
                
                <hr>
                
                <!-- Order Items List -->
                <ul class="list-group list-group-flush mb-3" id="modalItemList">
                    <!-- JS injects items here -->
                </ul>

                <hr>
                
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <h5 class="fw-bold mb-0">Grand Total</h5>
                    <h3 class="fw-bold text-success mb-0">RM <span id="modalTotalAmount">0.00</span></h3>
                </div>
                
                <input type="hidden" id="modalOrderId">
            </div>
            <div class="modal-footer bg-light border-0" id="modalFooterActions">
                <!-- JS injects payment button or receipt button here depending on status -->
            </div>
        </div>
    </div>
</div>

<script src="js/cashier_dashboard.js"></script>
<?php include 'includes/footer.php'; ?>