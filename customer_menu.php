<?php
// customer_menu.php
include 'includes/header.php';
?>

<link href="css/customer_menu.css" rel="stylesheet" />

<!-- ======================= HERO / ORDERING BANNER ======================= -->
<div class="menu-hero">
    <div class="menu-hero-inner container-fluid px-4">

        <!-- Brand + quick checkout shortcut -->
        <div class="menu-hero-top">
            <a href="index.html" class="menu-brand" style="text-decoration: none;">
    <i class="fas fa-utensils"></i> OrderPilot
</a>

            <div class="header-cart-widget">
                <button class="btn header-clear-btn" id="headerClearCartBtn" onclick="clearCart()" title="Clear cart">
                    <i class="fas fa-xmark"></i>
                </button>
                <button class="btn header-checkout-btn" id="headerCheckoutBtn" onclick="checkout()" disabled>
                    <span class="header-cart-badge" id="headerCartBadge">0</span>
                    Checkout
                </button>
            </div>
        </div>

        <h2 class="menu-hero-title">Welcome! Please place your order.</h2>

        <!-- Search + jump-to-cart -->
        <div class="menu-hero-search">
            <div class="search-group">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="menuSearchInput" class="menu-search-input" placeholder="Search menu..." />
                <button class="btn cart-jump-btn" onclick="scrollToCart()">
                    <i class="fas fa-shopping-basket me-1"></i> Cart
                </button>
            </div>
        </div>

        <!-- Category filters (built dynamically once the menu loads) -->
        <div class="menu-hero-filters" id="categoryFilters">
            <button class="filter-pill active" data-category="All" onclick="filterMenu('All', this)">All</button>
        </div>
    </div>
</div>

<!-- ======================= MAIN CONTENT ======================= -->
<div class="container-fluid px-4 menu-content">
    <div class="row">

        <!-- Menu grid -->
        <div class="col-lg-8 col-md-7 mb-4 order-1">
            <div class="row g-4" id="menuGrid">
                <div class="col-12 text-center mt-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Loading menu items...</p>
                </div>
            </div>
        </div>

        <!-- Cart sidebar -->
        <div class="col-lg-4 col-md-5 order-2" id="cartSidebar">
            <div class="cart-panel sticky-top">
                <div class="cart-panel-header">
                    <h5><i class="fas fa-receipt me-2"></i> Your cart</h5>
                    <span class="cart-count-badge" id="cartCount">0</span>
                </div>

                <ul class="cart-panel-body" id="cartItemsList">
                    <li class="cart-empty" id="emptyCartMsg">Your cart is empty.</li>
                </ul>

                <div class="cart-panel-footer">
                    <div class="cart-total-row">
                        <span>Total</span>
                        <span class="cart-total-amount">RM <span id="cartTotal">0.00</span></span>
                    </div>
                    <button class="btn checkout-btn w-100" id="checkoutBtn" onclick="checkout()" disabled>
                        <i class="fas fa-shopping-bag me-2"></i> Checkout
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ======================= ITEM DETAILS / ADD TO CART MODAL ======================= -->
<div class="modal fade" id="itemDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow item-modal">
            <button type="button" class="btn-close item-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>

            <img src="" class="item-modal-img" id="modalItemImg" alt="" />

            <div class="modal-body">
                <span class="badge bg-secondary mb-2" id="modalItemCategory"></span>
                <h5 class="fw-bold" id="modalItemName">Item Name</h5>
                <p class="text-muted" id="modalItemDesc"></p>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <span class="text-muted fs-6">Price</span>
                    <span class="fw-bold fs-4 item-modal-price">RM <span id="modalItemPrice">0.00</span></span>
                </div>

                <input type="hidden" id="modalItemId">

                <div class="mb-4">
                    <label class="form-label fw-bold">Quantity</label>
                    <div class="qty-stepper qty-stepper-modal">
                        <button class="qty-btn" type="button" onclick="changeModalQty(-1)"><i class="fas fa-minus"></i></button>
                        <span class="qty-value" id="modalItemQty">1</span>
                        <button class="qty-btn" type="button" onclick="changeModalQty(1)"><i class="fas fa-plus"></i></button>
                    </div>
                </div>

                <div class="mb-2">
                    <label for="modalItemRemarks" class="form-label fw-bold">Special Remarks (Optional)</label>
                    <textarea class="form-control" id="modalItemRemarks" rows="2" placeholder="e.g., Less spicy, no ice, extra sauce..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn add-to-cart-btn w-100 py-2" onclick="confirmAddToCart()">Add to Cart</button>
            </div>
        </div>
    </div>
</div>

<script src="js/customer_menu.js"></script>

<?php include 'includes/footer.php'; ?>