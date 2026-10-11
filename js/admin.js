// js/admin.js

document.addEventListener('DOMContentLoaded', () => {
    "use strict";

    let adminOrdersCache = [];
    let adminPOSMenuItems = [];
    let adminPOSCart = [];
    let currentAdminPOSCategory = 'All';

    let adminOrderModal, adminPOSModal, adminPOSItemModal, adminPOSCheckoutModal;

    if (document.getElementById('adminOrderModal')) adminOrderModal = new bootstrap.Modal(document.getElementById('adminOrderModal'));
    if (document.getElementById('adminPOSModal')) adminPOSModal = new bootstrap.Modal(document.getElementById('adminPOSModal'));
    if (document.getElementById('adminPOSItemModal')) adminPOSItemModal = new bootstrap.Modal(document.getElementById('adminPOSItemModal'));
    if (document.getElementById('adminPOSCheckoutModal')) adminPOSCheckoutModal = new bootstrap.Modal(document.getElementById('adminPOSCheckoutModal'));

    // Initialize Orders Management view
    if (document.getElementById('adminOrderTableBody')) {
        loadAdminOrders();
        setInterval(loadAdminOrders, 3000);

        document.getElementById('adminOrderSearch')?.addEventListener('input', renderAdminOrderTable);
        document.getElementById('adminStatusFilter')?.addEventListener('change', renderAdminOrderTable);
        document.getElementById('btnAdminClearCompleted')?.addEventListener('click', clearAllCompletedAdminOrders);
        document.getElementById('btnAdminTakeOrder')?.addEventListener('click', openAdminPOS);
        document.getElementById('adminPOSMenuSearch')?.addEventListener('input', renderAdminPOSMenu);
    }

    function loadAdminOrders() {
        const endpoints = [
            'getorder.php?type=all',
            '../getorder.php?type=all',
            '../api/getorder.php?type=all',
            '../cashier/getorder.php?type=all',
            'api/getorder.php?type=all'
        ];

        fetchWithFallback(endpoints)
            .then(data => {
                if (data.success || data.status === 'success') {
                    adminOrdersCache = data.orders || data.data || [];
                    renderAdminStats(data.stats || { sales: 0, pending: 0, kitchen_active: 0, completed: 0 });
                    renderAdminOrderTable();
                } else {
                    throw new Error(data.message || "Failed to fetch orders");
                }
            })
            .catch(err => {
                console.error("Admin Orders Fetch Error:", err);
                const tbody = document.getElementById('adminOrderTableBody');
                if (tbody && adminOrdersCache.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="6" class="text-center py-4 text-danger fw-bold">
                                <i class="fas fa-exclamation-triangle me-2"></i> ${err.message}
                            </td>
                        </tr>`;
                }
            });
    }

    function renderAdminStats(stats) {
        if (document.getElementById('adminStatSales')) document.getElementById('adminStatSales').textContent = Number(stats.sales || 0).toFixed(2);
        if (document.getElementById('adminStatPending')) document.getElementById('adminStatPending').textContent = stats.pending || 0;
        if (document.getElementById('adminStatKitchen')) document.getElementById('adminStatKitchen').textContent = stats.kitchen_active || 0;
        if (document.getElementById('adminStatCompleted')) document.getElementById('adminStatCompleted').textContent = stats.completed || 0;
    }

    function renderAdminOrderTable() {
        const tbody = document.getElementById('adminOrderTableBody');
        if (!tbody) return;

        const search = (document.getElementById('adminOrderSearch')?.value || '').toLowerCase();
        const filter = document.getElementById('adminStatusFilter')?.value || 'All';

        const filtered = adminOrdersCache.filter(o => {
            const matchesFilter = (filter === 'All') || (o.kitchen_status === filter);
            const matchesSearch = !search || String(o.order_id).includes(search);
            return matchesFilter && matchesSearch;
        });

        if (!filtered.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No orders found matching criteria.</td></tr>';
            return;
        }

        tbody.innerHTML = filtered.map(order => {
            const isPaid = (order.payment_status === 'Paid');
            const time = order.order_time ? new Date(order.order_time.replace(' ', 'T')).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '-';

            return `
                <tr>
                    <td><strong>#${order.order_id}</strong></td>
                    <td>${time}</td>
                    <td>
                        <select class="form-select form-select-sm fw-bold" style="width: 140px;" onchange="window.updateAdminKitchenStatus(${order.order_id}, this.value)">
                            <option value="pending" ${order.kitchen_status === 'pending' ? 'selected' : ''}>Pending</option>
                            <option value="new" ${order.kitchen_status === 'new' ? 'selected' : ''}>In Queue</option>
                            <option value="in_progress" ${order.kitchen_status === 'in_progress' ? 'selected' : ''}>In Progress</option>
                            <option value="completed" ${order.kitchen_status === 'completed' ? 'selected' : ''}>Completed</option>
                            <option value="cancelled" ${order.kitchen_status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                        </select>
                    </td>
                    <td>
                        <span class="status-badge ${isPaid ? 'status-available' : 'status-unavailable'}">
                            ${escapeHtml(order.payment_status)} (${escapeHtml(order.payment_method)})
                        </span>
                    </td>
                    <td><strong>RM ${Number(order.total_amount).toFixed(2)}</strong></td>
                    <td class="actions-col">
                        ${!isPaid ? `
                            <button class="btn btn-sm btn-success fw-bold me-1" onclick="window.approveAdminOrder(${order.order_id})">
                                <i class="fas fa-check me-1"></i> Approve
                            </button>` : ''}
                        <button class="btn btn-sm btn-outline-primary fw-bold me-1" onclick="window.viewAdminOrderDetails(${order.order_id})">
                            <i class="fas fa-receipt me-1"></i> Details
                        </button>
                        <button class="btn btn-sm btn-outline-danger fw-bold" onclick="window.cancelAdminOrder(${order.order_id})">
                            <i class="fas fa-xmark"></i>
                        </button>
                    </td>
                </tr>`;
        }).join('');
    }

    window.updateAdminKitchenStatus = function (orderId, newStatus) {
        const endpoints = [
            'update_order_status.php',
            '../update_order_status.php',
            '../api/update_order_status.php',
            'api/update_order_status.php'
        ];
        fetchWithFallback(endpoints, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order_id: orderId, kitchen_status: newStatus })
        })
            .then(data => {
                if (data.success || data.status === 'success') {
                    loadAdminOrders();
                } else {
                    Swal.fire({ icon: 'error', title: 'Update Failed', text: data.message });
                }
            })
            .catch(err => Swal.fire({ icon: 'error', title: 'Error', text: err.message }));
    };

    window.approveAdminOrder = function (orderId) {
        const order = adminOrdersCache.find(o => o.order_id == orderId);
        const method = order ? order.payment_method : 'Cash';

        const endpoints = [
            'process_payment.php',
            '../process_payment.php',
            '../api/process_payment.php',
            'api/process_payment.php'
        ];
        fetchWithFallback(endpoints, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order_id: orderId, payment_method: method })
        })
            .then(data => {
                if (data.success || data.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Approved!', text: 'Order sent to kitchen.', timer: 1200, showConfirmButton: false });
                    loadAdminOrders();
                } else {
                    Swal.fire({ icon: 'error', title: 'Approval Failed', text: data.message });
                }
            })
            .catch(err => Swal.fire({ icon: 'error', title: 'Error', text: err.message }));
    };

    window.viewAdminOrderDetails = function (orderId) {
        const order = adminOrdersCache.find(o => o.order_id == orderId);
        if (!order) return;

        document.getElementById('adminModalOrderTitle').textContent = `Order #${order.order_id} Details`;
        document.getElementById('adminModalKitchenStatus').textContent = order.kitchen_status;
        document.getElementById('adminModalPaymentStatus').textContent = `${order.payment_status} (${order.payment_method})`;
        document.getElementById('adminModalTotalAmount').textContent = Number(order.total_amount).toFixed(2);

        const list = document.getElementById('adminModalItemList');
        const items = order.items || [];
        list.innerHTML = items.length
            ? items.map(item => `
                <li class="list-group-item d-flex justify-content-between align-items-start py-2">
                    <div>
                        <strong class="d-block">${item.qty}x ${escapeHtml(item.name)}</strong>
                        ${item.remarks ? `<small class="text-danger">Note: ${escapeHtml(item.remarks)}</small>` : ''}
                    </div>
                    <span class="fw-bold">RM ${Number(item.subtotal).toFixed(2)}</span>
                </li>`).join('')
            : '<li class="list-group-item text-muted">No line details available.</li>';

        document.getElementById('adminModalFooterActions').innerHTML = `
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary fw-bold" onclick="window.print()">
                <i class="fas fa-print me-1"></i> Print Receipt
            </button>`;

        if (adminOrderModal) adminOrderModal.show();
    };

    window.cancelAdminOrder = function (orderId) {
        Swal.fire({
            title: `Cancel Order #${orderId}?`,
            text: "This will set the order status to Cancelled.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, Cancel Order'
        }).then((result) => {
            if (result.isConfirmed) {
                window.updateAdminKitchenStatus(orderId, 'cancelled');
            }
        });
    };

    function clearAllCompletedAdminOrders() {
        Swal.fire({
            title: 'Clear All Completed Orders?',
            text: "This will clear completed orders from the active kitchen view.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, Clear All'
        }).then((result) => {
            if (result.isConfirmed) {
                const endpoints = [
                    'update_order_status.php',
                    '../update_order_status.php',
                    '../api/update_order_status.php',
                    'api/update_order_status.php'
                ];
                fetchWithFallback(endpoints, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'clear_all_completed' })
                })
                    .then(data => {
                        if (data.success || data.status === 'success') {
                            Swal.fire({ icon: 'success', title: 'Cleared!', timer: 1200, showConfirmButton: false });
                            loadAdminOrders();
                        }
                    });
            }
        });
    }

    function openAdminPOS() {
        adminPOSCart = [];
        updateAdminPOSCartUI();
        fetchAdminPOSMenu();
        if (adminPOSModal) adminPOSModal.show();
    }

    function fetchAdminPOSMenu() {
        const grid = document.getElementById('adminPOSMenuGrid');
        grid.innerHTML = '<div class="col-12 text-center py-5 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Loading Menu...</div>';

        const endpoints = [
            '../api/menu_handler.php',
            'menu_handler.php',
            '../menu_handler.php',
            'api/menu_handler.php'
        ];
        fetchWithFallback(endpoints)
            .then(data => {
                if (data.status === 'success') {
                    adminPOSMenuItems = data.data || [];
                    renderAdminPOSCategories();
                    renderAdminPOSMenu();
                }
            });
    }

    function renderAdminPOSCategories() {
        const filterContainer = document.getElementById('adminPOSCategoryFilters');
        const categories = [...new Set(adminPOSMenuItems.map(i => i.category_name))].filter(Boolean);

        filterContainer.innerHTML = `<button class="btn btn-sm btn-primary rounded-pill px-3 active" onclick="window.filterAdminPOSMenu('All', this)">All</button>`;
        categories.forEach(cat => {
            filterContainer.innerHTML += `<button class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="window.filterAdminPOSMenu('${escapeHtml(cat)}', this)">${escapeHtml(cat)}</button>`;
        });
    }

    window.filterAdminPOSMenu = function (category, btnElement) {
        currentAdminPOSCategory = category;
        document.querySelectorAll('#adminPOSCategoryFilters button').forEach(b => {
            b.classList.remove('btn-primary', 'active');
            b.classList.add('btn-outline-secondary');
        });
        if (btnElement) {
            btnElement.classList.remove('btn-outline-secondary');
            btnElement.classList.add('btn-primary', 'active');
        }
        renderAdminPOSMenu();
    };

    function renderAdminPOSMenu() {
        const grid = document.getElementById('adminPOSMenuGrid');
        const search = (document.getElementById('adminPOSMenuSearch')?.value || '').toLowerCase();
        grid.innerHTML = '';

        const filtered = adminPOSMenuItems.filter(i => {
            if (i.is_available != 1) return false;
            if (currentAdminPOSCategory !== 'All' && i.category_name !== currentAdminPOSCategory) return false;
            if (search && !i.item_name.toLowerCase().includes(search)) return false;
            return true;
        });

        if (!filtered.length) {
            grid.innerHTML = '<div class="col-12 text-center py-5 text-muted">No items available.</div>';
            return;
        }

        filtered.forEach(item => {
            const rawImg = item.image_path || item.image;
            const imgSrc = rawImg ? '../' + rawImg : 'https://via.placeholder.com/150?text=No+Image';

            grid.innerHTML += `
                <div class="col-sm-6 col-md-4 col-xl-3">
                    <div class="card h-100 border-0 shadow-sm rounded-3 overflow-hidden" onclick="window.openAdminPOSItemModal(${item.item_id})" style="cursor: pointer;">
                        <img src="${imgSrc}" class="card-img-top" style="height: 120px; object-fit: cover;" alt="${escapeHtml(item.item_name)}">
                        <div class="card-body p-2 d-flex flex-column justify-content-between">
                            <h6 class="fw-bold mb-1 text-truncate">${escapeHtml(item.item_name)}</h6>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <span class="fw-bold text-success">RM ${parseFloat(item.price).toFixed(2)}</span>
                                <button type="button" class="btn btn-sm btn-primary rounded-circle"><i class="fas fa-plus"></i></button>
                            </div>
                        </div>
                    </div>
                </div>`;
        });
    }

    window.openAdminPOSItemModal = function (id) {
        const item = adminPOSMenuItems.find(i => i.item_id == id);
        if (!item) return;

        document.getElementById('adminPOSModalItemId').value = item.item_id;
        document.getElementById('adminPOSModalItemName').textContent = item.item_name;
        document.getElementById('adminPOSModalItemPrice').textContent = parseFloat(item.price).toFixed(2);
        document.getElementById('adminPOSModalItemQty').value = '1';
        document.getElementById('adminPOSModalItemRemarks').value = '';

        if (adminPOSItemModal) adminPOSItemModal.show();
    };

    window.changeAdminPOSModalQty = function (change) {
        const input = document.getElementById('adminPOSModalItemQty');
        let current = parseInt(input.value) || 1;
        let next = current + change;
        if (next >= 1 && next <= 20) input.value = next;
    };

    window.confirmAdminPOSAddToCart = function () {
        const id = document.getElementById('adminPOSModalItemId').value;
        const item = adminPOSMenuItems.find(i => i.item_id == id);
        const qty = parseInt(document.getElementById('adminPOSModalItemQty').value) || 1;
        const remarks = document.getElementById('adminPOSModalItemRemarks').value.trim();

        const index = adminPOSCart.findIndex(c => c.id == id && c.remarks === remarks);
        if (index > -1) {
            adminPOSCart[index].qty += qty;
        } else {
            adminPOSCart.push({ id: item.item_id, name: item.item_name, price: parseFloat(item.price), qty: qty, remarks: remarks });
        }

        if (adminPOSItemModal) adminPOSItemModal.hide();
        updateAdminPOSCartUI();
    };

    function updateAdminPOSCartUI() {
        const list = document.getElementById('adminPOSCartList');
        const totalEl = document.getElementById('adminPOSCartTotal');
        const btn = document.getElementById('btnAdminPOSCheckout');

        list.innerHTML = '';
        let total = 0;

        if (!adminPOSCart.length) {
            list.innerHTML = '<li class="list-group-item text-center text-muted py-4">Cart is empty</li>';
            totalEl.textContent = '0.00';
            btn.disabled = true;
            return;
        }

        adminPOSCart.forEach((item, idx) => {
            const subtotal = item.price * item.qty;
            total += subtotal;

            list.innerHTML += `
                <li class="list-group-item d-flex justify-content-between align-items-start py-2">
                    <div style="flex: 1;">
                        <strong class="d-block">${item.qty}x ${escapeHtml(item.name)}</strong>
                        <small class="text-muted">RM ${item.price.toFixed(2)} each</small>
                        ${item.remarks ? `<br><small class="text-danger">Note: ${escapeHtml(item.remarks)}</small>` : ''}
                    </div>
                    <div class="text-end">
                        <span class="fw-bold d-block">RM ${subtotal.toFixed(2)}</span>
                        <button class="btn btn-sm text-danger p-0 border-0" onclick="window.removeAdminPOSItem(${idx})"><i class="fas fa-trash-can"></i></button>
                    </div>
                </li>`;
        });

        totalEl.textContent = total.toFixed(2);
        btn.disabled = false;
    }

    window.removeAdminPOSItem = function (idx) {
        adminPOSCart.splice(idx, 1);
        updateAdminPOSCartUI();
    };

    window.clearAdminPOSCart = function () {
        adminPOSCart = [];
        updateAdminPOSCartUI();
    };

    window.openAdminPOSCheckout = function () {
        if (!adminPOSCart.length) return;

        const list = document.getElementById('adminPOSCheckoutSummaryList');
        const totalEl = document.getElementById('adminPOSCheckoutTotal');
        list.innerHTML = '';
        let total = 0;

        adminPOSCart.forEach(item => {
            const sub = item.price * item.qty;
            total += sub;
            list.innerHTML += `
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${item.qty}x ${escapeHtml(item.name)}</strong>
                        ${item.remarks ? `<br><small class="text-muted">Note: ${escapeHtml(item.remarks)}</small>` : ''}
                    </div>
                    <span class="fw-bold">RM ${sub.toFixed(2)}</span>
                </li>`;
        });

        totalEl.textContent = total.toFixed(2);
        if (adminPOSCheckoutModal) adminPOSCheckoutModal.show();
    };

    window.submitAdminPOSOrder = function () {
        const btn = document.getElementById('btnAdminPOSSubmit');
        const method = document.getElementById('adminPOSPaymentMethod').value;

        const payload = {
            payment_method: method,
            items: adminPOSCart.map(c => ({ item_id: c.id, quantity: c.qty, remarks: c.remarks }))
        };

        btn.disabled = true;
        btn.innerHTML = `<i class="fas fa-spinner fa-spin me-2"></i> Submitting...`;

        const endpoints = [
            '../api/checkout_handler.php',
            'checkout_handler.php',
            '../checkout_handler.php',
            'api/checkout_handler.php'
        ];
        fetchWithFallback(endpoints, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = `<i class="fas fa-check me-2"></i> Paid & Submit to Kitchen`;

                if (data.status === 'success') {
                    if (adminPOSCheckoutModal) adminPOSCheckoutModal.hide();
                    if (adminPOSModal) adminPOSModal.hide();
                    adminPOSCart = [];
                    updateAdminPOSCartUI();
                    loadAdminOrders();
                    Swal.fire({ icon: 'success', title: 'Submitted!', text: `Order #${data.order_id} sent to kitchen.`, timer: 1500, showConfirmButton: false });
                } else {
                    Swal.fire({ icon: 'error', title: 'Failed', text: data.message });
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = `<i class="fas fa-check me-2"></i> Paid & Submit to Kitchen`;
                Swal.fire({ icon: 'error', title: 'Network Error', text: err.message });
            });
    };

    async function fetchWithFallback(endpoints, options = {}) {
        let lastError = null;
        for (const url of endpoints) {
            try {
                const res = await fetch(url, options);
                const text = await res.text();
                if (res.ok) {
                    try {
                        return JSON.parse(text);
                    } catch (jsonErr) {
                        throw new Error(`Invalid JSON from ${url}: ${text.substring(0, 80)}`);
                    }
                } else if (res.status !== 404) {
                    throw new Error(`HTTP ${res.status} from ${url}: ${text.substring(0, 80)}`);
                } else {
                    lastError = new Error(`404 File Not Found (${url})`);
                }
            } catch (err) {
                lastError = err;
            }
        }
        throw lastError || new Error("Could not connect to API endpoint.");
    }

    function escapeHtml(str) {
        const div = document.createElement("div");
        div.textContent = str || "";
        return div.innerHTML;
    }
});