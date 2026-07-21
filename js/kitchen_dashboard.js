document.addEventListener('DOMContentLoaded', () => {

    // ---- Order action buttons --------------------------------------
    // Hook these up to your real backend endpoints (e.g. update_order.php)
    document.querySelectorAll('[data-action]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const row = btn.closest('tr');
            const orderId = row?.dataset.orderId;
            const action = btn.dataset.action;

            switch (action) {
                case 'start':
                    updateOrderStatus(orderId, 'in_progress');
                    break;
                case 'complete':
                    updateOrderStatus(orderId, 'completed');
                    break;
                case 'refresh':
                    updateOrderStatus(orderId, 'new');
                    break;
                case 'delete':
                    if (confirm(`Cancel order ${orderId}?`)) {
                        deleteOrder(orderId);
                    }
                    break;
                case 'more':
                    console.log('Show more options for', orderId);
                    break;
            }
        });
    });

    function updateOrderStatus(orderId, newStatus) {
        // Example AJAX call — replace url with your real endpoint.
        fetch('update_order_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order_id: orderId, status: newStatus }),
        })
            .then((res) => res.json())
            .then((data) => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to update order.');
                }
            })
            .catch((err) => console.error('Update order error:', err));
    }

    function deleteOrder(orderId) {
        fetch('delete_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order_id: orderId }),
        })
            .then((res) => res.json())
            .then((data) => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to delete order.');
                }
            })
            .catch((err) => console.error('Delete order error:', err));
    }

});