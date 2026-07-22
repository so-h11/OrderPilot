// js/cashier_dashboard.js

let cashierOrders = [];
let orderModal = null;

document.addEventListener('DOMContentLoaded', () => {
    // Initialize the Bootstrap Modal
    orderModal = new bootstrap.Modal(document.getElementById('orderModal'));
    
    // Initial fetch
    fetchCashierOrders();

    // Auto-refresh every 5 seconds to get new orders from customers
    setInterval(fetchCashierOrders, 5000);
});

function fetchCashierOrders() {
    fetch('api/cashier_orders.php')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                cashierOrders = data.data;
                renderCashierDashboard(data.data, data.stats);
            }
        })
        .catch(error => console.error('Error fetching cashier orders:', error));
}

function renderCashierDashboard(orders, stats) {
    // Update Stats
    document.getElementById('statSales').innerText = stats.totalSales.toFixed(2);
    document.getElementById('statPending').innerText = stats.pending;
    document.getElementById('statCompleted').innerText = stats.completed;

    const tbody = document.getElementById('cashierOrderTable');
    tbody.innerHTML = '';

    if (orders.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-5 text-muted">
                    <i class="fas fa-clipboard-list fa-3x mb-3 opacity-25"></i>
                    <h5>No active orders yet</h5>
                    <p class="mb-0">Orders placed by customers will appear here.</p>
                </td>
            </tr>`;
        return;
    }

    orders.forEach(order => {
        // Badges for Kitchen Status
        let kitchenBadge = '';
        if (order.status === 'new') kitchenBadge = '<span class="badge bg-secondary">New</span>';
        else if (order.status === 'in_progress') kitchenBadge = '<span class="badge bg-warning text-dark">Preparing</span>';
        else if (order.status === 'completed') kitchenBadge = '<span class="badge bg-success">Ready</span>';

        // Badges for Payment
        let paymentBadge = order.payment_status === 'Paid' 
            ? '<span class="badge bg-success">Paid</span>' 
            : '<span class="badge bg-danger">Unpaid</span>';

        const row = `
            <tr>
                <td class="ps-4 fw-bold">#${order.id}</td>
                <td><small class="text-muted">${order.time}</small></td>
                <td>${kitchenBadge}</td>
                <td>${paymentBadge}</td>
                <td class="fw-bold">RM ${order.total.toFixed(2)}</td>
                <td class="text-end pe-4">
                    <button class="btn btn-sm btn-outline-primary shadow-sm" onclick="viewOrderDetails(${order.id})">
                        <i class="fas fa-eye"></i> View
                    </button>
                </td>
            </tr>
        `;
        tbody.innerHTML += row;
    });
}

function viewOrderDetails(orderId) {
    // Find the specific order in our array
    const order = cashierOrders.find(o => o.id === orderId);
    if (!order) return;

    // Populate Modal Header & Info
    document.getElementById('modalOrderTitle').innerText = `Order #${order.id}`;
    document.getElementById('modalKitchenStatus').innerText = order.status.toUpperCase();
    document.getElementById('modalPaymentStatus').innerText = order.payment_status.toUpperCase();
    document.getElementById('modalTotalAmount').innerText = order.total.toFixed(2);
    document.getElementById('modalOrderId').value = order.id;

    // Populate Item List
    const itemList = document.getElementById('modalItemList');
    itemList.innerHTML = '';
    order.items.forEach(item => {
        const remarks = item.remarks ? `<br><small class="text-danger fst-italic">Note: ${item.remarks}</small>` : '';
        itemList.innerHTML += `
            <li class="list-group-item px-0 d-flex justify-content-between align-items-start border-light">
                <div class="ms-2 me-auto">
                    <div class="fw-bold">${item.qty}x ${item.name}</div>
                    ${remarks}
                </div>
                <span class="fw-bold text-muted">RM ${item.subtotal.toFixed(2)}</span>
            </li>
        `;
    });

    // Populate Footer Buttons based on payment status
    const footer = document.getElementById('modalFooterActions');
    if (order.payment_status === 'Unpaid') {
        footer.innerHTML = `
            <button type="button" class="btn btn-success w-100 py-2 fw-bold fs-5" onclick="processPayment(${order.id})">
                <i class="fas fa-money-bill-wave me-2"></i> Process Cash Payment
            </button>
        `;
    } else {
        footer.innerHTML = `
            <button type="button" class="btn btn-secondary w-100 py-2 fw-bold" onclick="printReceipt(${order.id})">
                <i class="fas fa-print me-2"></i> Print Receipt
            </button>
        `;
    }

    orderModal.show();
}

// Add these to the bottom of js/cashier_dashboard.js

function processPayment(orderId) {
    Swal.fire({
        title: 'Confirm Payment',
        text: `Process cash payment for Order #${orderId}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Mark as Paid'
    }).then((result) => {
        if (result.isConfirmed) {
            
            // Send POST request to update payment status
            fetch('api/payment_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: orderId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Close the modal
                    orderModal.hide();
                    
                    // Refresh the dashboard immediately
                    fetchCashierOrders();
                    
                    // Show success popup with option to print receipt immediately
                    Swal.fire({
                        title: 'Payment Successful!',
                        text: `Order #${orderId} is now paid.`,
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonColor: '#0d6efd',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: '<i class="fas fa-print"></i> Print Receipt',
                        cancelButtonText: 'Close'
                    }).then((printResult) => {
                        if (printResult.isConfirmed) {
                            printReceipt(orderId);
                        }
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => console.error('Payment Error:', error));
        }
    });
}

function printReceipt(orderId) {
    // Opens the GET route of the API in a new popup window sized for a receipt
    const receiptWindow = window.open(`api/payment_handler.php?order_id=${orderId}`, 'Receipt', 'width=400,height=600');
    if (!receiptWindow) {
        Swal.fire('Popup Blocked', 'Please allow popups to print receipts.', 'warning');
    }
}