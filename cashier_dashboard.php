<?php 
// cashier_dashboard.php
include 'includes/header.php'; 
include 'includes/navbar.php'; 

// Basic Security Check: Kick out anyone who isn't logged in as a Cashier or Admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Cashier' && $_SESSION['role'] !== 'Administrator')) {
    echo "<script>window.location.href = 'index.html';</script>";
    exit();
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="fas fa-desktop me-2 text-primary"></i> Cashier POS Dashboard</h4>
    </div>
    
    <div class="row mb-4">
        <!-- Payment Summary -->
        <div class="col-md-4 mb-3">
            <div class="card text-white bg-success shadow-sm h-100 border-0">
                <div class="card-body">
                    <h6 class="card-title text-uppercase fw-bold opacity-75">Today's Sales</h6>
                    <h2 class="mb-0 fw-bold">RM 0.00</h2>
                </div>
            </div>
        </div>
        <!-- Pending Orders -->
        <div class="col-md-4 mb-3">
            <div class="card text-white bg-warning shadow-sm h-100 border-0">
                <div class="card-body">
                    <h6 class="card-title text-uppercase fw-bold opacity-75 text-dark">Pending Orders</h6>
                    <h2 class="mb-0 fw-bold text-dark">0</h2>
                </div>
            </div>
        </div>
        <!-- Completed Orders -->
        <div class="col-md-4 mb-3">
            <div class="card text-white bg-info shadow-sm h-100 border-0">
                <div class="card-body">
                    <h6 class="card-title text-uppercase fw-bold opacity-75">Completed Orders</h6>
                    <h2 class="mb-0 fw-bold">0</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Orders Table -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom-0">
            <h5 class="mb-0 fw-bold text-secondary">Active Customer Orders</h5>
            <!-- We will build this functionality in Sprint 2 -->
            <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" disabled>
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
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Total</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody id="cashierOrderTable">
                        <!-- Placeholder until we build the Sprint 2 API -->
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-clipboard-list fa-3x mb-3 opacity-25"></i>
                                <h5>No active orders yet</h5>
                                <p class="mb-0">Orders placed by customers will appear here.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>