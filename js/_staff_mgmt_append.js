
// ========== STAFF MANAGEMENT ==========
let staffData = [];
let userModal;
let staffSearchTimer;

function initStaffManagement() {
    const modalEl = document.getElementById('userFormModal');
    if (modalEl) {
        userModal = new bootstrap.Modal(modalEl);
        document.getElementById('userAccountForm').addEventListener('submit', saveUserAccount);
        document.getElementById('staffSearchInput')?.addEventListener('input', () => {
            clearTimeout(staffSearchTimer);
            staffSearchTimer = setTimeout(renderStaffTable, 300);
        });
        document.getElementById('roleFilterSelect')?.addEventListener('change', renderStaffTable);
        loadStaffAccounts();
    }
}

function loadStaffAccounts() {
    fetch('../api/staff_handler.php?action=fetch')
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                staffData = data.data;
                renderStaffTable();
                updateStaffCounters();
            } else {
                document.getElementById('staffTableBody').innerHTML = '<tr><td colspan="7" class="text-center py-4 text-danger">Failed to load accounts: ' + (data.message || 'Unknown error') + '</td></tr>';
            }
        })
        .catch(err => {
            console.error('Account loading error:', err);
            document.getElementById('staffTableBody').innerHTML = '<tr><td colspan="7" class="text-center py-4 text-danger">Network error loading accounts.</td></tr>';
        });
}

function updateStaffCounters() {
    const total = staffData.length;
    const cashier = staffData.filter(u => u.role === 'Cashier').length;
    const kitchen = staffData.filter(u => u.role === 'Kitchen Staff').length;
    const customer = staffData.filter(u => u.role === 'Customer').length;
    const countTotalEl = document.getElementById('countTotal');
    const countCashierEl = document.getElementById('countCashier');
    const countKitchenEl = document.getElementById('countKitchen');
    const countCustomerEl = document.getElementById('countCustomer');
    if (countTotalEl) countTotalEl.textContent = total;
    if (countCashierEl) countCashierEl.textContent = cashier;
    if (countKitchenEl) countKitchenEl.textContent = kitchen;
    if (countCustomerEl) countCustomerEl.textContent = customer;
}

function renderStaffTable() {
    const tbody = document.getElementById('staffTableBody');
    if (!tbody) return;
    const search = (document.getElementById('staffSearchInput')?.value || '').toLowerCase();
    const roleFilter = document.getElementById('roleFilterSelect')?.value || 'All';

    const filtered = staffData.filter(u => {
        const matchRole = roleFilter === 'All' || u.role === roleFilter;
        const matchSearch = !search ||
            (u.full_name || '').toLowerCase().includes(search) ||
            (u.username || '').toLowerCase().includes(search) ||
            (u.role || '').toLowerCase().includes(search);
        return matchRole && matchSearch;
    });

    if (!filtered.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">No accounts found matching your search.</td></tr>';
        return;
    }

    const roleColors = {
        'Administrator': 'danger',
        'Cashier': 'primary',
        'Kitchen Staff': 'warning',
        'Customer': 'info'
    };

    tbody.innerHTML = filtered.map(u => {
        const color = roleColors[u.role] || 'secondary';
        const statusBadge = u.status === 'Active'
            ? '<span class="status-badge status-available">Active</span>'
            : '<span class="status-badge status-unavailable">Inactive</span>';
        const createdAt = u.created_at ? new Date(u.created_at.replace(' ', 'T') + '+08:00').toLocaleDateString('en-MY', { timeZone: 'Asia/Kuala_Lumpur' }) : '-';
        return `<tr>
            <td><strong>#${u.user_id}</strong></td>
            <td>${escapeHtmlStaff(u.full_name || '-')}</td>
            <td><code>${escapeHtmlStaff(u.username)}</code></td>
            <td><span class="badge bg-${color} text-white">${escapeHtmlStaff(u.role)}</span></td>
            <td>${statusBadge}</td>
            <td>${createdAt}</td>
            <td class="actions-col">
                <button class="btn btn-sm btn-outline-primary fw-bold me-1" onclick="openEditUserModal(${u.user_id})" title="Edit">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger fw-bold" onclick="deleteUserAccount(${u.user_id}, '${escapeHtmlStaff(u.username)}')" title="Delete">
                    <i class="fas fa-trash-can"></i>
                </button>
            </td>
        </tr>`;
    }).join('');
}

window.openCreateUserModal = function() {
    document.getElementById('userModalTitle').innerHTML = '<i class="fas fa-user-plus text-primary me-2"></i>Add New Account';
    document.getElementById('formUserId').value = '0';
    document.getElementById('formFullName').value = '';
    document.getElementById('formUsername').value = '';
    document.getElementById('formPassword').value = '';
    document.getElementById('formRole').value = 'Cashier';
    document.getElementById('formStatus').value = 'Active';
    document.getElementById('formPassword').required = true;
    document.getElementById('lblPassword').innerHTML = 'Password <span class="text-danger">*</span>';
    document.getElementById('pwdHelpText').classList.add('d-none');
    if (userModal) userModal.show();
};

window.openEditUserModal = function(userId) {
    const user = staffData.find(u => u.user_id === userId);
    if (!user) return;
    document.getElementById('userModalTitle').innerHTML = '<i class="fas fa-user-edit text-warning me-2"></i>Edit Account';
    document.getElementById('formUserId').value = user.user_id;
    document.getElementById('formFullName').value = user.full_name || '';
    document.getElementById('formUsername').value = user.username;
    document.getElementById('formPassword').value = '';
    document.getElementById('formRole').value = user.role;
    document.getElementById('formStatus').value = user.status || 'Active';
    document.getElementById('formPassword').required = false;
    document.getElementById('lblPassword').innerHTML = 'New Password';
    document.getElementById('pwdHelpText').classList.remove('d-none');
    if (userModal) userModal.show();
};

function saveUserAccount(e) {
    e.preventDefault();
    const userId = parseInt(document.getElementById('formUserId').value) || 0;
    const action = userId > 0 ? 'update' : 'create';
    const btn = document.getElementById('btnSaveUser');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Saving...';

    const payload = {
        action,
        user_id: userId,
        full_name: document.getElementById('formFullName').value.trim(),
        username: document.getElementById('formUsername').value.trim(),
        password: document.getElementById('formPassword').value,
        role: document.getElementById('formRole').value,
        status: document.getElementById('formStatus').value
    };

    fetch('../api/staff_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-1"></i> Save Account';
        if (data.status === 'success') {
            if (userModal) userModal.hide();
            Swal.fire({ icon: 'success', title: 'Saved!', text: data.message, timer: 1500, showConfirmButton: false });
            loadStaffAccounts();
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message });
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-1"></i> Save Account';
        Swal.fire({ icon: 'error', title: 'Network Error', text: 'Could not save account.' });
    });
}

window.deleteUserAccount = function(userId, username) {
    Swal.fire({
        title: `Delete account "${username}"?`,
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, Delete'
    }).then(result => {
        if (result.isConfirmed) {
            fetch('../api/staff_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete', user_id: userId })
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Deleted!', timer: 1200, showConfirmButton: false });
                    loadStaffAccounts();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                }
            });
        }
    });
};

function escapeHtmlStaff(str) {
    const div = document.createElement('div');
    div.textContent = str || '';
    return div.innerHTML;
}

// Initialize staff management if on that page
if (document.getElementById('staffTableBody')) {
    initStaffManagement();
}
