// js/customer_menu.js

let menuItems = [];
let currentCategory = 'All';
let cart = []; 
let itemModal; // Declare empty variable first

document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialize the modal ONLY after the HTML is fully loaded
    const modalElement = document.getElementById('itemDetailsModal');
    if (modalElement) {
        itemModal = new bootstrap.Modal(modalElement);
    }
    
    // 2. Fetch the data
    fetchMenu();
    
    // 3. Listen for search bar typing
    const searchInput = document.getElementById('menuSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', renderMenu);
    }
});
// ... (keep the rest of the code exactly the same from here down)

// 1. Fetch menu from the database
function fetchMenu() {
    fetch('api/menu_handler.php')
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                menuItems = data.data;
                renderCategories();
                renderMenu();
            }
        })
        .catch(err => console.error("Error fetching menu:", err));
}

// 2. Render the menu grid dynamically
function renderMenu() {
    const grid = document.getElementById('menuGrid');
    grid.innerHTML = '';
    const searchTerm = document.getElementById('menuSearchInput').value.toLowerCase();

    // Filter logic (Category & Search)
    const filtered = menuItems.filter(item => {
        if(item.is_available != 1) return false;
        if(currentCategory !== 'All' && item.category_name !== currentCategory) return false;
        if(searchTerm && !item.item_name.toLowerCase().includes(searchTerm)) return false;
        return true;
    });

    if(filtered.length === 0) {
        grid.innerHTML = '<div class="col-12 text-center mt-5 text-muted">No items available right now.</div>';
        return;
    }

    filtered.forEach(item => {
        const img = item.image_path ? item.image_path : 'https://via.placeholder.com/400x300?text=No+Image';
        grid.innerHTML += `
            <div class="col-sm-6 col-md-12 col-lg-6 col-xl-4 mb-4">
                <div class="menu-card">
                    <div class="menu-card-img-wrap" onclick="openItemModal(${item.item_id})">
                        <img src="${img}" class="menu-card-img" alt="${item.item_name}">
                    </div>
                    <div class="menu-card-body">
                        <h5 class="menu-card-title" onclick="openItemModal(${item.item_id})">${item.item_name}</h5>
                        <p class="menu-card-desc">${item.description || ''}</p>
                        <div class="menu-card-footer">
                            <div class="menu-card-actions">
                                <span class="menu-card-price">RM ${parseFloat(item.price).toFixed(2)}</span>
                                <button class="btn add-to-cart-btn" onclick="openItemModal(${item.item_id})">+ Add</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
}

// 3. Open the Item Details Modal
function openItemModal(id) {
    const item = menuItems.find(i => i.item_id == id);
    if(!item) return;

    document.getElementById('modalItemId').value = item.item_id;
    document.getElementById('modalItemName').innerText = item.item_name;
    document.getElementById('modalItemCategory').innerText = item.category_name;
    document.getElementById('modalItemDesc').innerText = item.description || '';
    document.getElementById('modalItemPrice').innerText = parseFloat(item.price).toFixed(2);
    document.getElementById('modalItemImg').src = item.image_path ? item.image_path : 'https://via.placeholder.com/400x300?text=No+Image';
    
    // Reset modal inputs
    document.getElementById('modalItemQty').innerText = '1';
    document.getElementById('modalItemRemarks').value = '';

    itemModal.show();
}

// 4. Modal Quantity Buttons
function changeModalQty(change) {
    const qtyEl = document.getElementById('modalItemQty');
    let current = parseInt(qtyEl.innerText);
    let newQty = current + change;
    if(newQty >= 1 && newQty <= 20) {
        qtyEl.innerText = newQty;
    }
}

// 5. Add to Cart Logic
function confirmAddToCart() {
    const id = document.getElementById('modalItemId').value;
    const item = menuItems.find(i => i.item_id == id);
    const qty = parseInt(document.getElementById('modalItemQty').innerText);
    const remarks = document.getElementById('modalItemRemarks').value.trim();

    // Check if exact same item with exact same remarks already exists in cart
    const existingIndex = cart.findIndex(c => c.id == id && c.remarks === remarks);
    
    if(existingIndex > -1) {
        cart[existingIndex].qty += qty; // Just increase quantity
    } else {
        cart.push({
            id: item.item_id,
            name: item.item_name,
            price: parseFloat(item.price),
            qty: qty,
            remarks: remarks
        });
    }

    itemModal.hide();
    updateCartUI();
    
    // Show a quick success popup
    Swal.fire({
        toast: true, position: 'top-end', showConfirmButton: false,
        timer: 1500, icon: 'success', title: 'Added to cart'
    });
}

// 6. Refresh the Cart UI
function updateCartUI() {
    const list = document.getElementById('cartItemsList');
    const totalEl = document.getElementById('cartTotal');
    const badge1 = document.getElementById('cartCount');
    const badge2 = document.getElementById('headerCartBadge');
    const checkBtn1 = document.getElementById('checkoutBtn');
    const checkBtn2 = document.getElementById('headerCheckoutBtn');

    list.innerHTML = '';
    let total = 0;
    let itemCount = 0;

    if(cart.length === 0) {
        list.innerHTML = '<li class="cart-empty" id="emptyCartMsg">Your cart is empty.</li>';
        totalEl.innerText = '0.00';
        badge1.innerText = '0';
        badge2.innerText = '0';
        checkBtn1.disabled = true;
        checkBtn2.disabled = true;
        return;
    }

    cart.forEach((cItem, index) => {
        const subtotal = cItem.price * cItem.qty;
        total += subtotal;
        itemCount += cItem.qty;

        const remarkHtml = cItem.remarks ? `<div class="cart-line-remarks">Note: ${cItem.remarks}</div>` : '';

        list.innerHTML += `
            <li class="cart-line-item">
                <div style="flex:1;">
                    <div class="cart-line-name">${cItem.name}</div>
                    <div class="cart-line-unit">${cItem.qty} x RM ${cItem.price.toFixed(2)}</div>
                    ${remarkHtml}
                </div>
                <div class="cart-line-right">
                    <div class="cart-line-subtotal">RM ${subtotal.toFixed(2)}</div>
                    <button class="cart-remove-btn" onclick="removeFromCart(${index})">
                        <i class="fas fa-trash"></i> Remove
                    </button>
                </div>
            </li>
        `;
    });

    totalEl.innerText = total.toFixed(2);
    badge1.innerText = itemCount;
    badge2.innerText = itemCount;
    checkBtn1.disabled = false;
    checkBtn2.disabled = false;
}

// 7. Remove item from cart
function removeFromCart(index) {
    cart.splice(index, 1);
    updateCartUI();
}

// 8. Clear entire cart
function clearCart() {
    if(cart.length === 0) return;
    Swal.fire({
        title: 'Clear cart?',
        text: "All items will be removed.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d1455c',
        confirmButtonText: 'Yes, clear it'
    }).then((result) => {
        if (result.isConfirmed) {
            cart = [];
            updateCartUI();
        }
    });
}

// 9. Render Categories dynamically based on database
function renderCategories() {
    const filterContainer = document.getElementById('categoryFilters');
    // Get unique categories from menu items
    const categories = [...new Set(menuItems.map(item => item.category_name))].filter(Boolean);
    
    filterContainer.innerHTML = `<button class="filter-pill active" onclick="filterMenu('All', this)">All</button>`;
    categories.forEach(cat => {
        filterContainer.innerHTML += `<button class="filter-pill" onclick="filterMenu('${cat}', this)">${cat}</button>`;
    });
}

// 10. Filter menu by category
function filterMenu(category, btnElement) {
    currentCategory = category;
    document.querySelectorAll('.filter-pill').forEach(btn => btn.classList.remove('active'));
    btnElement.classList.add('active');
    renderMenu();
}

// 11. Helper functions
function scrollToCart() {
    document.getElementById('cartSidebar').scrollIntoView({ behavior: 'smooth' });
}

function checkout() {
    Swal.fire({
        title: 'Ready to Checkout!',
        text: 'This will be connected to the database in our next session.',
        icon: 'info',
        confirmButtonColor: '#7c3aed'
    });
}