<?php 
// customer_menu.php
include 'includes/header.php'; 
include 'includes/navbar.php'; 
?>

<div class="container-fluid mt-4 px-4">
    <div class="row">
        <!-- Main Menu Area (Left Side) -->
        <div class="col-lg-8 col-md-7 mb-4">
            <h4 class="mb-3"><i class="fas fa-book-open me-2 text-primary"></i> Restaurant Menu</h4>
            
            <!-- Category Filter Buttons -->
            <div class="mb-4" id="categoryFilters">
                <button class="btn btn-primary rounded-pill me-2 mb-2" onclick="filterMenu('All')">All</button>
                <button class="btn btn-outline-primary rounded-pill me-2 mb-2" onclick="filterMenu('Food')">Food</button>
                <button class="btn btn-outline-primary rounded-pill me-2 mb-2" onclick="filterMenu('Drinks')">Drinks</button>
                <button class="btn btn-outline-primary rounded-pill mb-2" onclick="filterMenu('Desserts')">Desserts</button>
            </div>

            <!-- Menu Grid -->
            <div class="row g-4" id="menuGrid">
                <!-- Menu items will be dynamically injected here by JavaScript -->
                <div class="text-center mt-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Loading menu items...</p>
                </div>
            </div>
        </div>

        <!-- Floating Cart Sidebar (Right Side) -->
        <div class="col-lg-4 col-md-5">
            <div class="card shadow-sm border-0 sticky-top" style="top: 20px; z-index: 1020;">
                <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-shopping-cart me-2"></i> Your Cart</h5>
                    <span class="badge bg-light text-primary rounded-pill" id="cartCount">0</span>
                </div>
                <div class="card-body p-0">
                    <!-- Cart Items List -->
                    <ul class="list-group list-group-flush" id="cartItemsList" style="max-height: 50vh; overflow-y: auto;">
                        <li class="list-group-item text-center text-muted py-4" id="emptyCartMsg">
                            Your cart is empty.
                        </li>
                    </ul>
                </div>
                <div class="card-footer bg-light border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="fw-bold text-muted">Total Amount:</span>
                        <h4 class="fw-bold mb-0 text-success">RM <span id="cartTotal">0.00</span></h4>
                    </div>
                    <button class="btn btn-success w-100 py-2 fw-bold" id="checkoutBtn" onclick="checkout()" disabled>
                        Submit Order <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add to Cart Modal (Captures Remarks & Quantity) -->
<div class="modal fade" id="addToCartModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalItemName">Item Name</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <span class="text-muted fs-5">Price:</span>
                    <span class="fw-bold fs-4 text-primary">RM <span id="modalItemPrice">0.00</span></span>
                </div>
                
                <input type="hidden" id="modalItemId">
                
                <div class="mb-4">
                    <label class="form-label fw-bold">Quantity</label>
                    <div class="input-group w-50">
                        <button class="btn btn-outline-secondary" type="button" onclick="changeQty(-1)">-</button>
                        <input type="text" class="form-control text-center" id="modalItemQty" value="1" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="changeQty(1)">+</button>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="modalItemRemarks" class="form-label fw-bold">Special Remarks (Optional)</label>
                    <textarea class="form-control" id="modalItemRemarks" rows="2" placeholder="e.g., Less spicy, no ice, extra sauce..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-primary w-100 py-2" onclick="confirmAddToCart()">Add to Cart</button>
            </div>
        </div>
    </div>
</div>

<script>
    // System State
    let allMenuItems = [];
    let cart = [];
    const cartModal = new bootstrap.Modal(document.getElementById('addToCartModal'));

    // 1. Fetch Menu Items on Page Load
    document.addEventListener('DOMContentLoaded', () => {
        fetch('api/menu_handler.php')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Filter out unavailable items for the customer view
                    allMenuItems = data.data.filter(item => item.is_available == 1);
                    renderMenu(allMenuItems);
                } else {
                    Swal.fire('Error', 'Could not load menu items.', 'error');
                }
            })
            .catch(error => console.error('Error fetching menu:', error));
    });

    // 2. Render the Menu Grid
    function renderMenu(items) {
        const grid = document.getElementById('menuGrid');
        grid.innerHTML = ''; // Clear loading spinner

        if (items.length === 0) {
            grid.innerHTML = '<div class="col-12"><p class="text-muted">No menu items found for this category.</p></div>';
            return;
        }

        items.forEach(item => {
            // Determine image path or use a placeholder if none exists
            const imgPath = item.image_path ? item.image_path : 'https://via.placeholder.com/300x200?text=No+Image';
            
            const card = `
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    <div class="card h-100 shadow-sm border-0 cursor-pointer" onclick="openCartModal(${item.item_id}, '${item.item_name}', ${item.price})">
                        <img src="${imgPath}" class="card-img-top" alt="${item.item_name}" style="height: 160px; object-fit: cover;">
                        <div class="card-body d-flex flex-column">
                            <span class="badge bg-secondary mb-2 align-self-start">${item.category_name}</span>
                            <h6 class="card-title fw-bold">${item.item_name}</h6>
                            <p class="card-text text-muted small mb-3 flex-grow-1">${item.description || ''}</p>
                            <div class="d-flex justify-content-between align-items-center mt-auto">
                                <span class="fw-bold text-success">RM ${parseFloat(item.price).toFixed(2)}</span>
                                <button class="btn btn-sm btn-primary rounded-circle"><i class="fas fa-plus"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            grid.innerHTML += card;
        });
    }

    // 3. Category Filtering
    function filterMenu(category) {
        // Update active button styling
        const buttons = document.querySelectorAll('#categoryFilters button');
        buttons.forEach(btn => {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-outline-primary');
            if (btn.innerText === category) {
                btn.classList.remove('btn-outline-primary');
                btn.classList.add('btn-primary');
            }
        });

        // Filter and render array
        if (category === 'All') {
            renderMenu(allMenuItems);
        } else {
            const filtered = allMenuItems.filter(item => item.category_name === category);
            renderMenu(filtered);
        }
    }

    // 4. Modal Interactions
    function openCartModal(id, name, price) {
        document.getElementById('modalItemId').value = id;
        document.getElementById('modalItemName').innerText = name;
        document.getElementById('modalItemPrice').innerText = parseFloat(price).toFixed(2);
        document.getElementById('modalItemQty').value = 1; // Default to 1
        document.getElementById('modalItemRemarks').value = ''; // Clear previous remarks
        
        cartModal.show();
    }

    function changeQty(amount) {
        const qtyInput = document.getElementById('modalItemQty');
        let currentQty = parseInt(qtyInput.value);
        if (currentQty + amount >= 1) {
            qtyInput.value = currentQty + amount;
        }
    }

    // 5. Cart Management
    function confirmAddToCart() {
        const id = document.getElementById('modalItemId').value;
        const name = document.getElementById('modalItemName').innerText;
        const price = parseFloat(document.getElementById('modalItemPrice').innerText);
        const qty = parseInt(document.getElementById('modalItemQty').value);
        const remarks = document.getElementById('modalItemRemarks').value.trim();

        // Check if item with exact same remarks already exists in cart to stack them
        const existingItemIndex = cart.findIndex(item => item.id == id && item.remarks === remarks);
        
        if (existingItemIndex !== -1) {
            cart[existingItemIndex].qty += qty;
        } else {
            cart.push({ id, name, price, qty, remarks });
        }

        updateCartUI();
        cartModal.hide();
        
        // Brief toast notification
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Added to cart',
            showConfirmButton: false,
            timer: 1500
        });
    }

    function removeFromCart(index) {
        cart.splice(index, 1);
        updateCartUI();
    }

    // 6. Update Cart UI & Calculate Totals
    function updateCartUI() {
        const list = document.getElementById('cartItemsList');
        const emptyMsg = document.getElementById('emptyCartMsg');
        const checkoutBtn = document.getElementById('checkoutBtn');
        const countBadge = document.getElementById('cartCount');
        const totalDisplay = document.getElementById('cartTotal');

        let total = 0;
        let totalItems = 0;

        // Clear existing list elements (except the empty message template)
        list.innerHTML = '';

        if (cart.length === 0) {
            list.innerHTML = '<li class="list-group-item text-center text-muted py-4" id="emptyCartMsg">Your cart is empty.</li>';
            checkoutBtn.disabled = true;
            countBadge.innerText = '0';
            totalDisplay.innerText = '0.00';
            return;
        }

        checkoutBtn.disabled = false;

        cart.forEach((item, index) => {
            const subtotal = item.price * item.qty;
            total += subtotal;
            totalItems += item.qty;

            const remarksHtml = item.remarks ? `<div class="small text-muted mt-1 fst-italic"><i class="fas fa-comment-dots me-1"></i> ${item.remarks}</div>` : '';

            const listItem = `
                <li class="list-group-item py-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto">
                            <div class="fw-bold">${item.name}</div>
                            <span class="text-muted small">${item.qty} x RM ${item.price.toFixed(2)}</span>
                            ${remarksHtml}
                        </div>
                        <div class="text-end">
                            <div class="fw-bold mb-1">RM ${subtotal.toFixed(2)}</div>
                            <button class="btn btn-sm btn-outline-danger border-0 p-1" onclick="removeFromCart(${index})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </li>
            `;
            list.innerHTML += listItem;
        });

        countBadge.innerText = totalItems;
        totalDisplay.innerText = total.toFixed(2);
    }

    // 7. Checkout Placeholder (Will connect to api/checkout_handler.php in Sprint 2)
    function checkout() {
        Swal.fire({
            title: 'Confirm Order?',
            text: "Your order will be sent directly to the cashier and kitchen.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Submit Order!'
        }).then((result) => {
            if (result.isConfirmed) {
                // This is where we will send the `cart` array to the server via fetch() in Prompt 5
                Swal.fire('Order Submitted!', 'Please proceed to the cashier for payment.', 'success')
                .then(() => {
                    cart = [];
                    updateCartUI();
                });
            }
        });
    }
</script>

<?php include 'includes/footer.php'; ?>