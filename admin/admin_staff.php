<?php
// admin/admin_staff.php
session_start();

if (($_SESSION['role'] ?? '') !== 'Administrator') {
    header('Location: ../index.html');
    exit();
}

$activePage = 'staff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>OrderPilot - Staff Management</title>
<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<!-- Custom Admin CSS -->
<link rel="stylesheet" href="../css/admin.css?v=<?php echo time(); ?>">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/app.js?v=<?php echo @filemtime(__DIR__ . '/../assets/js/app.js') ?: time(); ?>"></script>
</head>
<body>

<?php include __DIR__ . '/admin_header.php'; ?>

<div class="app-body">

    <?php include __DIR__ . '/admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="page-title mb-0">Staff & Account Management</h1>
            <button class="btn btn-primary fw-bold" onclick="openCreateUserModal()">
                <i class="fas fa-user-plus me-1"></i> Add New Account
            </button>
        </div>

        <!-- Role Counters -->
        <section class="stats-row mb-4">
            <div class="stat-card stat-sales">
                <span class="stat-label">TOTAL ACCOUNTS</span>
                <span class="stat-value" id="countTotal">0</span>
            </div>
            <div class="stat-card stat-staff">
                <span class="stat-label">CASHIERS</span>
                <span class="stat-value" id="countCashier">0</span>
            </div>
            <div class="stat-card stat-pending">
                <span class="stat-label">KITCHEN STAFF</span>
                <span class="stat-value" id="countKitchen">0</span>
            </div>
            <div class="stat-card stat-completed">
                <span class="stat-label">CUSTOMERS / DEVICES</span>
                <span class="stat-value" id="countCustomer">0</span>
            </div>
        </section>

        <!-- Main User Accounts Table Panel -->
        <section class="panel">
            <div class="panel-header d-flex flex-wrap justify-content-between align-items-center gap-3">
                <h2>User Accounts Directory</h2>
                <div class="d-flex gap-2">
                    <input type="text" id="staffSearchInput" class="form-control form-control-sm" placeholder="Search accounts..." style="width: 220px;">
                    <select id="roleFilterSelect" class="form-select form-select-sm" style="width: 160px;">
                        <option value="All">All Roles</option>
                        <option value="Administrator">Administrator</option>
                        <option value="Cashier">Cashier</option>
                        <option value="Kitchen Staff">Kitchen Staff</option>
                        <option value="Customer">Customer</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive mt-3">
                <table class="panel-table">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Full Name</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th class="actions-col">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="staffTableBody">
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="fas fa-spinner fa-spin me-2"></i>Loading accounts directory...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<!-- ======================= CREATE / EDIT USER MODAL ======================= -->
<div class="modal fade" id="userFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-0">
                <h5 class="modal-title fw-bold" id="userModalTitle"><i class="fas fa-user-plus text-primary me-2"></i>Add New Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="userAccountForm">
                <div class="modal-body p-4">
                    <input type="hidden" id="formUserId" value="0">

                    <div class="mb-3">
                        <label for="formFullName" class="form-label fw-bold">Full Name / Label <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="formFullName" placeholder="e.g. Sarah Ahmad or Main Register" required>
                    </div>

                    <div class="mb-3">
                        <label for="formUsername" class="form-label fw-bold">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="formUsername" placeholder="e.g. sarah123" required>
                    </div>

                    <div class="mb-3">
                        <label for="formPassword" class="form-label fw-bold" id="lblPassword">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="formPassword" placeholder="Enter account password">
                        <small class="text-muted d-none" id="pwdHelpText">Leave empty to keep existing password.</small>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label for="formRole" class="form-label fw-bold">Assigned Role <span class="text-danger">*</span></label>
                            <select class="form-select" id="formRole" required>
                                <option value="Cashier">Cashier</option>
                                <option value="Kitchen Staff">Kitchen Staff</option>
                                <option value="Administrator">Administrator</option>
                                <option value="Customer">Customer</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="formStatus" class="form-label fw-bold">Account Status</label>
                            <select class="form-select" id="formStatus">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold" id="btnSaveUser">
                        <i class="fas fa-save me-1"></i> Save Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/_staff_mgmt_append.js?v=<?php echo @filemtime(__DIR__ . '/../js/_staff_mgmt_append.js') ?: time(); ?>"></script>
</body>
</html>