// js/kitchen_dashboard.js

document.addEventListener('DOMContentLoaded', () => {
    // Initial load
    fetchKitchenOrders();

    // Auto-refresh the kitchen display every 5 seconds
    setInterval(fetchKitchenOrders, 5000);
});

function fetchKitchenOrders() {
    fetch('api/kitchen_handler.php')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                renderKitchenOrders(data.data);
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

    if (orders.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 40px; color: #9ca3af;">No active orders. Kitchen is clear!</td></tr>';
    }

    orders.forEach(order => {
        // Tally up the stats
        if (order.status === 'new') countNew++;
        if (order.status === 'in_progress') countProgress++;
        if (order.status === 'completed') countCompleted++;

        // Build the HTML for the food items inside this order
        let itemsHtml = '';
        order.items.forEach(item => {
            let remarkHtml = item.remarks ? `<div class="item-subtitle text-danger">Note: ${item.remarks}</div>` : '';
            itemsHtml += `
                <div style="margin-bottom: 8px;">
                    <div class="item-title">${item.qty}x ${item.name}</div>
                    ${remarkHtml}
                </div>
            `;
        });

        // Determine Status Label
        let statusLabel = '';
        if (order.status === 'new') statusLabel = 'New Order';
        else if (order.status === 'in_progress') statusLabel = 'In Progress';
        else if (order.status === 'completed') statusLabel = 'Completed';

        // Build Action Buttons based on status
        let actionButtons = '';
        if (order.status === 'new') {
            actionButtons = `<button class="btn btn-start" onclick="updateOrderStatus(${order.id}, 'in_progress')">Start Order</button>`;
        } else if (order.status === 'in_progress') {
            actionButtons = `
                <button class="icon-btn icon-btn-complete" onclick="updateOrderStatus(${order.id}, 'completed')" title="Mark Completed"><i class="fas fa-check"></i></button>
                <button class="icon-btn icon-btn-refresh" onclick="updateOrderStatus(${order.id}, 'new')" title="Revert to New"><i class="fas fa-undo"></i></button>
                <button class="icon-btn icon-btn-delete" onclick="deleteOrder(${order.id})" title="Cancel Order"><i class="fas fa-trash"></i></button>
            `;
        } else {
            actionButtons = `<span style="color: #9ca3af; font-size: 13px;">Done</span>`;
        }

        const row = `
            <tr>
                <td class="order-id">
                    #${order.id}<br>
                    <small style="color: #9ca3af; font-weight: normal;">${order.time}</small>
                </td>
                <td class="order-items">${itemsHtml}</td>
                <td><span class="status-badge status-${order.status}">${statusLabel}</span></td>
                <td class="actions-col">${actionButtons}</td>
            </tr>
        `;
        tbody.innerHTML += row;
    });

    // Update Dashboard Counters
    document.getElementById('countNew').innerText = countNew;
    document.getElementById('countProgress').innerText = countProgress;
    document.getElementById('countCompleted').innerText = countCompleted;
}

function updateOrderStatus(orderId, newStatus) {
    fetch('api/kitchen_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order_id: orderId, status: newStatus })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            fetchKitchenOrders(); // Refresh instantly
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    });
}

function deleteOrder(orderId) {
    Swal.fire({
        title: 'Cancel Order?',
        text: "This will remove the order from the kitchen queue.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e5484d',
        confirmButtonText: 'Yes, cancel it'
    }).then((result) => {
        if (result.isConfirmed) {
            updateOrderStatus(orderId, 'cancelled');
        }
    });
}