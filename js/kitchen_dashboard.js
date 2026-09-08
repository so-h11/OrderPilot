// js/kitchen_dashboard.js

let currentOrders = [];

document.addEventListener('DOMContentLoaded', () => {
    fetchKitchenOrders();
    setInterval(fetchKitchenOrders, 3000); // Auto-refresh kitchen display every 3s
});

function fetchKitchenOrders() {
    fetch('api/kitchen_handler.php')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                currentOrders = data.data;
                renderKitchenOrders(currentOrders);
            }
        })
        .catch(error => console.error('Error fetching kitchen orders:', error));
}

function renderKitchenOrders(orders) {
    const tbody = document.getElementById('kitchenOrderTable');
    tbody.innerHTML = '';

    let countNew = 0;
    let countProgress = 0;
    let countCompleted = 0;

    if (!orders || orders.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 40px; color: #6b7280;">No active orders. Kitchen is clear!</td></tr>';
        document.getElementById('countNew').innerText = '0';
        document.getElementById('countProgress').innerText = '0';
        document.getElementById('countCompleted').innerText = '0';
        return;
    }

    orders.forEach(order => {
        if (order.status === 'new') countNew++;
        if (order.status === 'in_progress') countProgress++;
        if (order.status === 'completed') countCompleted++;

        // Summary of items for table row
        let summaryItemsHtml = '';
        order.items.forEach(item => {
            let remarkHtml = item.remarks ? `<div class="item-subtitle"><i class="fas fa-comment-dots me-1"></i>Note: ${item.remarks}</div>` : '';
            summaryItemsHtml += `
                <div class="item-line">
                    <div class="item-title">${item.qty}x ${item.name}</div>
                    ${remarkHtml}
                </div>
            `;
        });

        let statusLabel = '';
        if (order.status === 'new') statusLabel = 'In Queue';
        else if (order.status === 'in_progress') statusLabel = 'Getting Made';
        else if (order.status === 'completed') statusLabel = 'Completed';

        // Action Buttons based on status
        let actionButtons = '';
        if (order.status === 'new') {
            actionButtons = `
                <button class="btn btn-start" onclick="event.stopPropagation(); updateOrderStatus(${order.id}, 'in_progress', this)">
                    <i class="fas fa-play me-1"></i> Start Order
                </button>
            `;
        } else if (order.status === 'in_progress') {
            actionButtons = `
                <button class="icon-btn icon-btn-complete" onclick="event.stopPropagation(); updateOrderStatus(${order.id}, 'completed', this)" title="Mark Completed">
                    <i class="fas fa-check"></i>
                </button>
                <button class="icon-btn icon-btn-refresh" onclick="event.stopPropagation(); updateOrderStatus(${order.id}, 'new', this)" title="Revert to Queue">
                    <i class="fas fa-undo"></i>
                </button>
                <button class="icon-btn icon-btn-delete" onclick="event.stopPropagation(); deleteOrder(${order.id}, this)" title="Cancel Order">
                    <i class="fas fa-trash"></i>
                </button>
            `;
        } else if (order.status === 'completed') {
            // Individual clear/delete button for completed orders
            actionButtons = `
                <button class="btn btn-delete-single" onclick="event.stopPropagation(); deleteOrder(${order.id}, this)" title="Clear this order">
                    <i class="fas fa-trash-can me-1"></i> Clear
                </button>
            `;
        }

        const row = `
            <tr onclick="viewOrderDetails(${order.id})" class="clickable-row">
                <td class="order-id">
                    <strong>#${order.id}</strong><br>
                    <small class="text-muted">${order.time}</small>
                </td>
                <td class="order-items">${summaryItemsHtml}</td>
                <td><span class="status-badge status-${order.status}">${statusLabel}</span></td>
                <td class="actions-col">${actionButtons}</td>
            </tr>
        `;
        tbody.innerHTML += row;
    });

    document.getElementById('countNew').innerText = countNew;
    document.getElementById('countProgress').innerText = countProgress;
    document.getElementById('countCompleted').innerText = countCompleted;
}

// Order details popup when row is clicked
function viewOrderDetails(orderId) {
    const order = currentOrders.find(o => o.id == orderId);
    if (!order) return;

    let itemsListHtml = '';
    order.items.forEach(item => {
        let remarkHtml = item.remarks ? `<div style="font-size: 13px; color: #db2777; font-style: italic;">Note: ${item.remarks}</div>` : '';
        itemsListHtml += `
            <div style="display: flex; justify-content: space-between; align-items: flex-start; padding: 10px 0; border-bottom: 1px dashed #e5e7eb; text-align: left;">
                <div>
                    <strong style="color: #1f2937;">${item.qty}x ${item.name}</strong>
                    ${remarkHtml}
                </div>
                <div style="font-weight: 700; color: #1f2937;">RM ${(item.subtotal || 0).toFixed(2)}</div>
            </div>
        `;
    });

    let statusLabel = order.status === 'new' ? 'In Queue' : (order.status === 'in_progress' ? 'Getting Made' : 'Completed');

    Swal.fire({
        title: `<span style="color: #7c3aed;">Order #${order.id} Breakdown</span>`,
        html: `
            <div style="font-size: 14px; margin-bottom: 15px; color: #6b7280;">Placed at ${order.time} \u2022 Status: <strong>${statusLabel}</strong></div>
            <div style="max-height: 250px; overflow-y: auto; margin-bottom: 15px;">
                ${itemsListHtml}
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 16px; font-weight: 700; padding-top: 10px; border-top: 2px solid #1f2937;">
                <span>Total Amount</span>
                <span style="color: #7c3aed;">RM ${(order.total || 0).toFixed(2)}</span>
            </div>
            <div style="text-align: left; font-size: 13px; color: #6b7280; margin-top: 8px;">
                Payment Method: <strong>${order.payment_method}</strong>
            </div>
        `,
        confirmButtonText: 'Close',
        confirmButtonColor: '#7c3aed',
        customClass: { popup: 'rounded-16' }
    });
}

// Update status with animated loading indicator
function updateOrderStatus(orderId, newStatus, btnElement) {
    if (btnElement) {
        btnElement.disabled = true;
        btnElement.innerHTML = `<i class="fas fa-spinner fa-spin"></i>`;
    }

    fetch('api/kitchen_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order_id: orderId, status: newStatus })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            fetchKitchenOrders();
        } else {
            Swal.fire('Error', data.message, 'error');
            fetchKitchenOrders();
        }
    })
    .catch(err => {
        console.error('Error updating status:', err);
        fetchKitchenOrders();
    });
}

// Delete individual completed order
function deleteOrder(orderId, btnElement) {
    if (btnElement) {
        btnElement.disabled = true;
        btnElement.innerHTML = `<i class="fas fa-spinner fa-spin"></i>`;
    }

    fetch('api/kitchen_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order_id: orderId, status: 'cancelled' })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            fetchKitchenOrders();
        } else {
            Swal.fire('Error', data.message, 'error');
            fetchKitchenOrders();
        }
    })
    .catch(err => {
        console.error('Error removing order:', err);
        fetchKitchenOrders();
    });
}

// Clear all completed orders
function clearAllCompleted(btnElement) {
    Swal.fire({
        title: 'Clear All Completed Orders?',
        text: "This will remove all finished orders from the kitchen dashboard.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        confirmButtonText: 'Yes, clear all'
    }).then((result) => {
        if (result.isConfirmed) {
            if (btnElement) {
                btnElement.disabled = true;
                btnElement.innerHTML = `<i class="fas fa-spinner fa-spin me-1"></i> Clearing...`;
            }

            fetch('api/kitchen_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'clear_all_completed' })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    fetchKitchenOrders();
                } else {
                    Swal.fire('Error', data.message, 'error');
                    fetchKitchenOrders();
                }
            })
            .catch(err => {
                console.error('Error clearing completed orders:', err);
                fetchKitchenOrders();
            });
        }
    });
}