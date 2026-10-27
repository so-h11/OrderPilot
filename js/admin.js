// js/admin.js

document.addEventListener('DOMContentLoaded', () => {
    "use strict";

    let adminOrdersCache = [];
    let adminOrderModal;
    let adminPOSMenuItems = [];
    let adminPOSCart = [];
    let adminPOSCategory = 'All';
    let adminPOSModal;
    let adminPOSItemModal;
    let adminPOSCheckoutModal;

    if (document.getElementById('adminOrderModal')) adminOrderModal = new bootstrap.Modal(document.getElementById('adminOrderModal'));
    if (document.getElementById('adminPOSModal')) adminPOSModal = new bootstrap.Modal(document.getElementById('adminPOSModal'));
    if (document.getElementById('adminPOSItemModal')) adminPOSItemModal = new bootstrap.Modal(document.getElementById('adminPOSItemModal'));
    if (document.getElementById('adminPOSCheckoutModal')) adminPOSCheckoutModal = new bootstrap.Modal(document.getElementById('adminPOSCheckoutModal'));

    if (document.getElementById('adminOrderTableBody')) {
        loadAdminOrders();
        setInterval(loadAdminOrders, 3000);

        document.getElementById('adminOrderSearch')?.addEventListener('input', renderAdminOrderTable);
        document.getElementById('adminStatusFilter')?.addEventListener('change', renderAdminOrderTable);
        document.getElementById('btnAdminClearCompleted')?.addEventListener('click', clearAllCompletedAdminOrders);
        document.getElementById('btnAdminTakeOrder')?.addEventListener('click', openAdminPOS);
        document.getElementById('adminPOSMenuSearch')?.addEventListener('input', renderAdminPOSMenu);
    }

    function openAdminPOS() {
        adminPOSCart = [];
        adminPOSCategory = 'All';
        const search = document.getElementById('adminPOSMenuSearch');
        if (search) search.value = '';
        updateAdminPOSCart();
        if (adminPOSModal) adminPOSModal.show();
        loadAdminPOSMenu();
    }

    async function loadAdminPOSMenu() {
        const grid = document.getElementById('adminPOSMenuGrid');
        if (!grid) return;
        grid.innerHTML = '<div class="col-12 text-center py-5 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Loading menu...</div>';

        try {
            const response = await fetch('../api/menu_handler.php');
            const data = await response.json();
            if (!response.ok || data.status !== 'success') {
                throw new Error(data.message || 'Failed to load menu.');
            }
            adminPOSMenuItems = data.data || [];
            renderAdminPOSCategories();
            renderAdminPOSMenu();
        } catch (error) {
            grid.innerHTML = `<div class="col-12 text-center py-5 text-danger">${escapeHtml(error.message || 'Could not load menu items.')}</div>`;
        }
    }

    function renderAdminPOSCategories() {
        const container = document.getElementById('adminPOSCategoryFilters');
        if (!container) return;
        container.replaceChildren();

        const categories = ['All', ...new Set(adminPOSMenuItems.map(item => item.category_name).filter(Boolean))];
        categories.forEach(category => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = `btn btn-sm rounded-pill px-3 ${category === adminPOSCategory ? 'btn-primary active' : 'btn-outline-secondary'}`;
            button.textContent = category;
            button.addEventListener('click', () => {
                adminPOSCategory = category;
                renderAdminPOSCategories();
                renderAdminPOSMenu();
            });
            container.appendChild(button);
        });
    }

    function renderAdminPOSMenu() {
        const grid = document.getElementById('adminPOSMenuGrid');
        if (!grid) return;
        const search = (document.getElementById('adminPOSMenuSearch')?.value || '').trim().toLowerCase();
        const available = adminPOSMenuItems.filter(item =>
            Number(item.is_available) === 1 &&
            (adminPOSCategory === 'All' || item.category_name === adminPOSCategory) &&
            (!search || String(item.item_name || '').toLowerCase().includes(search))
        );

        if (!available.length) {
            grid.innerHTML = '<div class="col-12 text-center py-5 text-muted">No items available.</div>';
            return;
        }

        grid.innerHTML = available.map(item => {
            const imagePath = item.image_path || item.image;
            const imageUrl = imagePath ? `../${escapeHtml(imagePath)}` : 'https://via.placeholder.com/150?text=No+Image';
            const name = escapeHtml(item.item_name);
            const price = Number(item.price);
            return `
                <div class="col-sm-6 col-md-4 col-xl-3">
                    <button type="button" class="card h-100 w-100 border-0 shadow-sm rounded-3 overflow-hidden text-start p-0" onclick="window.openAdminPOSItem(${Number(item.item_id)})">
                        <img src="${imageUrl}" class="card-img-top" style="height: 120px; object-fit: cover;" alt="${name}">
                        <span class="card-body p-2 d-flex flex-column justify-content-between">
                            <strong class="mb-1 text-truncate">${name}</strong>
                            <span class="d-flex justify-content-between align-items-center mt-2">
                                <span class="fw-bold text-success">RM ${price.toFixed(2)}</span>
                                <span class="btn btn-sm btn-primary rounded-circle"><i class="fas fa-plus"></i></span>
                            </span>
                        </span>
                    </button>
                </div>`;
        }).join('');
    }

    window.openAdminPOSItem = function (itemId) {
        const item = adminPOSMenuItems.find(menuItem => Number(menuItem.item_id) === Number(itemId));
        if (!item) return;
        document.getElementById('adminPOSModalItemId').value = item.item_id;
        document.getElementById('adminPOSModalItemName').textContent = item.item_name;
        document.getElementById('adminPOSModalItemPrice').textContent = Number(item.price).toFixed(2);
        document.getElementById('adminPOSModalItemQty').value = '1';
        document.getElementById('adminPOSModalItemRemarks').value = '';
        if (adminPOSItemModal) adminPOSItemModal.show();
    };

    window.changeAdminPOSModalQty = function (change) {
        const input = document.getElementById('adminPOSModalItemQty');
        const quantity = Number(input?.value || 1) + change;
        if (input && quantity >= 1 && quantity <= 20) input.value = String(quantity);
    };

    window.confirmAdminPOSAddToCart = function () {
        const itemId = Number(document.getElementById('adminPOSModalItemId').value);
        const item = adminPOSMenuItems.find(menuItem => Number(menuItem.item_id) === itemId);
        if (!item) return;
        const quantity = Number(document.getElementById('adminPOSModalItemQty').value) || 1;
        const remarks = document.getElementById('adminPOSModalItemRemarks').value.trim();
        const existingItem = adminPOSCart.find(cartItem => cartItem.id === itemId && cartItem.remarks === remarks);

        if (existingItem) {
            existingItem.qty += quantity;
        } else {
            adminPOSCart.push({
                id: itemId,
                name: item.item_name,
                price: Number(item.price),
                qty: quantity,
                remarks
            });
        }

        if (adminPOSItemModal) adminPOSItemModal.hide();
        updateAdminPOSCart();
    };

    function updateAdminPOSCart() {
        const list = document.getElementById('adminPOSCartList');
        const totalElement = document.getElementById('adminPOSCartTotal');
        const checkoutButton = document.getElementById('btnAdminPOSCheckout');
        if (!list || !totalElement || !checkoutButton) return;

        if (!adminPOSCart.length) {
            list.innerHTML = '<li class="list-group-item text-center text-muted py-4">Cart is empty</li>';
            totalElement.textContent = '0.00';
            checkoutButton.disabled = true;
            return;
        }

        let total = 0;
        list.innerHTML = adminPOSCart.map((item, index) => {
            const subtotal = item.price * item.qty;
            total += subtotal;
            return `
                <li class="list-group-item d-flex justify-content-between align-items-start py-2">
                    <div class="flex-grow-1">
                        <strong class="d-block">${item.qty}x ${escapeHtml(item.name)}</strong>
                        <small class="text-muted">RM ${item.price.toFixed(2)} each</small>
                        ${item.remarks ? `<br><small class="text-danger">Note: ${escapeHtml(item.remarks)}</small>` : ''}
                    </div>
                    <div class="text-end">
                        <span class="fw-bold d-block">RM ${subtotal.toFixed(2)}</span>
                        <button type="button" class="btn btn-sm text-danger p-0 border-0" onclick="window.removeAdminPOSCartItem(${index})" aria-label="Remove item">
                            <i class="fas fa-trash-can"></i>
                        </button>
                    </div>
                </li>`;
        }).join('');
        totalElement.textContent = total.toFixed(2);
        checkoutButton.disabled = false;
    }

    window.removeAdminPOSCartItem = function (index) {
        adminPOSCart.splice(index, 1);
        updateAdminPOSCart();
    };

    window.clearAdminPOSCart = function () {
        adminPOSCart = [];
        updateAdminPOSCart();
    };

    window.openAdminPOSCheckout = function () {
        if (!adminPOSCart.length) return;
        const list = document.getElementById('adminPOSCheckoutSummaryList');
        const totalElement = document.getElementById('adminPOSCheckoutTotal');
        let total = 0;
        list.innerHTML = adminPOSCart.map(item => {
            const subtotal = item.price * item.qty;
            total += subtotal;
            return `
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div><strong>${item.qty}x ${escapeHtml(item.name)}</strong>
                        ${item.remarks ? `<br><small class="text-muted">Note: ${escapeHtml(item.remarks)}</small>` : ''}
                    </div>
                    <span class="fw-bold">RM ${subtotal.toFixed(2)}</span>
                </li>`;
        }).join('');
        totalElement.textContent = total.toFixed(2);
        if (adminPOSCheckoutModal) adminPOSCheckoutModal.show();
    };

    window.submitAdminPOSOrder = async function () {
        const button = document.getElementById('btnAdminPOSSubmit');
        if (!button || !adminPOSCart.length) return;
        const originalButton = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';

        try {
            const response = await fetch('../api/checkout_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    order_type: 'walkin',
                    payment_method: document.getElementById('adminPOSPaymentMethod').value,
                    items: adminPOSCart.map(item => ({
                        item_id: item.id,
                        quantity: item.qty,
                        remarks: item.remarks
                    }))
                })
            });
            const data = await response.json();
            if (!response.ok || data.status !== 'success') {
                throw new Error(data.message || 'Failed to submit order.');
            }

            if (adminPOSCheckoutModal) adminPOSCheckoutModal.hide();
            if (adminPOSModal) adminPOSModal.hide();
            adminPOSCart = [];
            updateAdminPOSCart();
            loadAdminOrders();
            await Swal.fire({
                icon: 'success',
                title: 'Order submitted',
                text: `Order #${data.order_id} was submitted to the kitchen.`,
                timer: 1800,
                showConfirmButton: false
            });
        } catch (error) {
            await Swal.fire({ icon: 'error', title: 'Could not create order', text: error.message || 'Please try again.' });
        } finally {
            button.disabled = false;
            button.innerHTML = originalButton;
        }
    };

    function loadAdminOrders() {
        const endpoints = [
            'getorder.php?type=all',
            '../getorder.php?type=all',
            '../api/getorder.php?type=all',
            'api/getorder.php?type=all'
        ];

        fetchWithFallback(endpoints)
            .then(data => {
                if (data.success || data.status === 'success') {
                    adminOrdersCache = data.orders || data.data || [];
                    renderAdminStats(data.stats || { sales: 0, pending: 0, kitchen_active: 0, completed: 0 });
                    renderAdminOrderTable();
                }
            })
            .catch(err => console.error("Admin Fetch Error:", err));
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
            // Strictly exclude cleared orders
            if (o.kitchen_status === 'cleared') return false;

            const isCancelled = String(o.kitchen_status || '').toLowerCase() === 'cancelled';
            const isPendingStatus = !isCancelled && (
                o.kitchen_status === 'pending' ||
                o.kitchen_status === 'pending_approval' ||
                String(o.payment_status || '').toLowerCase() === 'unpaid'
            );
            const matchesFilter = (filter === 'All') ||
                (filter === 'pending' && isPendingStatus) ||
                (o.kitchen_status === filter);

            const matchesSearch = !search || String(o.order_id).includes(search);
            return matchesFilter && matchesSearch;
        });

        if (!filtered.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No active orders right now.</td></tr>';
            return;
        }

        tbody.innerHTML = filtered.map(order => {
            const isCancelled = String(order.kitchen_status || '').toLowerCase() === 'cancelled';
            const isPaid = String(order.payment_status || '').toLowerCase() === 'paid';
            const isPendingStatus = (order.kitchen_status === 'pending' || order.kitchen_status === 'pending_approval');
            const time = order.order_time ? new Date(order.order_time.replace(' ', 'T') + '+08:00').toLocaleTimeString('en-MY', { hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Kuala_Lumpur' }) : '-';
            const kitchenStatus = isCancelled
                ? '<span class="badge bg-danger">Cancelled</span>'
                : `
                    <select class="form-select form-select-sm fw-bold" style="width: 140px;" onchange="window.updateAdminKitchenStatus(${order.order_id}, this.value)">
                        ${isPendingStatus ? '<option value="" disabled selected>Pending Approval</option>' : ''}
                        <option value="new" ${order.kitchen_status === 'new' ? 'selected' : ''}>In Queue</option>
                        <option value="in_progress" ${(order.kitchen_status === 'in_progress' || order.kitchen_status === 'preparing') ? 'selected' : ''}>Preparing</option>
                        <option value="completed" ${order.kitchen_status === 'completed' ? 'selected' : ''}>Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>`;

            return `
                <tr>
                    <td><strong>#${order.order_id}</strong></td>
                    <td>${time}</td>
                    <td>${kitchenStatus}</td>
                    <td>
                        <span class="status-badge ${isCancelled ? 'bg-danger text-white' : (isPaid ? 'status-available' : 'status-unavailable')}">
                            ${escapeHtml(isCancelled ? 'Cancelled' : order.payment_status)} (${escapeHtml(order.payment_method)})
                        </span>
                    </td>
                    <td><strong>RM ${Number(order.total_amount).toFixed(2)}</strong></td>
                    <td class="actions-col">
                        ${!isPaid && !isCancelled ? `
                            <button class="btn btn-sm btn-success fw-bold me-1" onclick="window.approveAdminOrder(${order.order_id})" title="Approve Payment">
                                <i class="fas fa-check me-1"></i> Approve
                            </button>` : ''}
                        <button class="btn btn-sm btn-outline-primary fw-bold me-1" onclick="window.viewAdminOrderDetails(${order.order_id})" title="View Details">
                            <i class="fas fa-receipt"></i>
                        </button>
                        ${!isCancelled ? `<button class="btn btn-sm btn-outline-warning fw-bold me-1" onclick="window.revertAdminOrder(${order.order_id})" title="Undo / Revert to Pending">
                            <i class="fas fa-rotate-left"></i>
                        </button>` : ''}
                        <button class="btn btn-sm btn-outline-danger fw-bold" onclick="window.clearSingleAdminOrder(${order.order_id})" title="Clear Order">
                            <i class="fas fa-trash-can"></i>
                        </button>
                    </td>
                </tr>`;
        }).join('');
    }

    function clearAllCompletedAdminOrders() {
        Swal.fire({
            title: 'Clear Completed & Cancelled Orders?',
            text: "This will remove completed and cancelled orders from the Order Control view. Sales history will remain saved for Reports.",
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
                            Swal.fire({ icon: 'success', title: 'Cleared!', text: 'Completed and cancelled orders cleared.', timer: 1200, showConfirmButton: false });
                            loadAdminOrders();
                        }
                    });
            }
        });
    }

    window.clearSingleAdminOrder = function (orderId) {
        Swal.fire({
            title: `Clear Order #${orderId}?`,
            text: "This order will be removed from Order Control.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, Clear Order'
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
                    body: JSON.stringify({ order_id: orderId, kitchen_status: 'cleared' })
                })
                    .then(data => {
                        if (data.success || data.status === 'success') {
                            Swal.fire({ icon: 'success', title: 'Cleared!', timer: 1000, showConfirmButton: false });
                            loadAdminOrders();
                        }
                    });
            }
        });
    };

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
                    Swal.fire({ icon: 'success', title: 'Updated!', timer: 1000, showConfirmButton: false });
                    loadAdminOrders();
                }
            });
    };

    window.revertAdminOrder = function (orderId) {
        const endpoints = [
            'update_order_status.php',
            '../update_order_status.php',
            '../api/update_order_status.php',
            'api/update_order_status.php'
        ];
        fetchWithFallback(endpoints, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'revert', order_id: orderId })
        })
            .then(data => {
                if (data.success || data.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Reverted!', timer: 1000, showConfirmButton: false });
                    loadAdminOrders();
                }
            });
    };

    window.approveAdminOrder = function (orderId) {
        const order = adminOrdersCache.find(o => o.order_id == orderId);
        if (!order || String(order.kitchen_status || '').toLowerCase() === 'cancelled') {
            return;
        }
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
                    Swal.fire({ icon: 'success', title: 'Approved!', timer: 1000, showConfirmButton: false });
                    loadAdminOrders();
                }
            });
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
                <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                    <div>
                        <strong class="d-block">${item.qty}x ${escapeHtml(item.name)}</strong>
                        ${item.remarks ? `<small class="text-danger">Note: ${escapeHtml(item.remarks)}</small>` : ''}
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="fw-bold me-2">RM ${Number(item.subtotal).toFixed(2)}</span>
                        <button class="btn btn-sm btn-outline-danger border-0" onclick="window.deleteAdminOrderItem(${order.order_id}, ${item.item_id})" title="Delete Item">
                            <i class="fas fa-trash-can"></i>
                        </button>
                    </div>
                </li>`).join('')
            : '<li class="list-group-item text-muted">No line details available.</li>';

        if (adminOrderModal) adminOrderModal.show();
    };

    window.deleteAdminOrderItem = function (orderId, itemId) {
        const endpoints = [
            'update_order_status.php',
            '../update_order_status.php',
            '../api/update_order_status.php',
            'api/update_order_status.php'
        ];

        fetchWithFallback(endpoints, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete_item', order_id: orderId, item_id: itemId })
        })
            .then(data => {
                if (data.success || data.status === 'success') {
                    if (adminOrderModal) adminOrderModal.hide();
                    loadAdminOrders();
                }
            });
    };

    async function fetchWithFallback(endpoints, options = {}) {
        let lastError = null;
        for (const url of endpoints) {
            try {
                const res = await fetch(url, options);
                const text = await res.text();
                if (res.ok) return JSON.parse(text);
            } catch (err) {
                lastError = err;
            }
        }
        throw lastError || new Error("Could not connect to API endpoint.");
    }

    function escapeHtml(str) {
        const div = document.createElement("div");
        div.textContent = str || "";
        return div.innerHTML.replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }
});