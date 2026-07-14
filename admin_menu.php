<?php 
// admin_menu.php
// Ensure session is started and user is an Admin (Basic security check)
// include 'includes/header.php'; will start the session based on our previous setup.

include 'includes/header.php'; 
include 'includes/navbar.php'; 
?>

<div class="container mt-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="fas fa-utensils me-2 text-primary"></i> Menu Management</h4>
        <button class="btn btn-primary shadow-sm" onclick="openAddModal()">
            <i class="fas fa-plus me-1"></i> Add New Item
        </button>
    </div>

    <!-- Menu Items Table -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Item Name</th>
                        <th>Category</th>
                        <th>Price (RM)</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody id="menuTableBody">
                    <!-- This will be populated dynamically by our API later -->
                    <!-- Dummy Data for visual reference -->
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold">Nasi Lemak Ayam Goreng</div>
                            <small class="text-muted">Classic coconut rice with fried chicken.</small>
                        </td>
                        <td><span class="badge bg-secondary">Food</span></td>
                        <td>12.50</td>
                        <td><span class="badge bg-success">Available</span></td>
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-outline-primary me-1" onclick="openEditModal(1, 'Nasi Lemak Ayam Goreng', 1, 12.50, 'Classic coconut rice with fried chicken.', 1)"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteMenuItem(1)"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Menu Modal -->
<div class="modal fade" id="menuModal" tabindex="-1" aria-labelledby="menuModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="menuForm">
                <div class="modal-header bg-light border-0 pb-3">
                    <h5 class="modal-title" id="menuModalLabel">Add Menu Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Hidden field to store ID when editing -->
                    <input type="hidden" id="item_id" name="item_id">
                    
                    <div class="mb-3">
                        <label for="item_name" class="form-label">Item Name</label>
                        <input type="text" class="form-control" id="item_name" name="item_name" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="category_id" class="form-label">Category</label>
                            <!-- Values match the category_id in our DB (1=Food, 2=Drinks, 3=Desserts) -->
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="1">Food</option>
                                <option value="2">Drinks</option>
                                <option value="3">Desserts</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="price" class="form-label">Price (RM)</label>
                            <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="image_file" class="form-label">Image Upload</label>
                            <input class="form-control" type="file" id="image_file" name="image_file" accept="image/*">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="is_available" class="form-label">Status</label>
                            <select class="form-select" id="is_available" name="is_available">
                                <option value="1">Available</option>
                                <option value="0">Unavailable</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveMenuBtn">Save Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Page-specific UI Scripts -->
<script>
    const menuModal = new bootstrap.Modal(document.getElementById('menuModal'));
    const menuForm = document.getElementById('menuForm');
    const modalTitle = document.getElementById('menuModalLabel');

    // 1. Fetch Menu Items on Page Load
    document.addEventListener('DOMContentLoaded', fetchAdminMenu);

    function fetchAdminMenu() {
        fetch('api/menu_handler.php')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    renderAdminMenu(data.data);
                }
            })
            .catch(error => console.error('Error fetching menu:', error));
    }

    function renderAdminMenu(items) {
        const tbody = document.getElementById('menuTableBody');
        tbody.innerHTML = ''; // Clear existing rows

        if (items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No menu items found.</td></tr>';
            return;
        }

        items.forEach(item => {
            const statusBadge = item.is_available == 1 
                ? '<span class="badge bg-success">Available</span>' 
                : '<span class="badge bg-danger">Unavailable</span>';
            
            // Escape quotes for the inline onclick function
            const safeName = item.item_name.replace(/'/g, "\\'");
            const safeDesc = (item.description || '').replace(/'/g, "\\'");

            const row = `
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold">${item.item_name}</div>
                        <small class="text-muted">${item.description || ''}</small>
                    </td>
                    <td><span class="badge bg-secondary">${item.category_name}</span></td>
                    <td>${parseFloat(item.price).toFixed(2)}</td>
                    <td>${statusBadge}</td>
                    <td class="text-end pe-4">
                        <button class="btn btn-sm btn-outline-primary me-1" 
                            onclick="openEditModal(${item.item_id}, '${safeName}', ${item.category_id}, ${item.price}, '${safeDesc}', ${item.is_available})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteMenuItem(${item.item_id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            tbody.innerHTML += row;
        });
    }

    // 2. Open Add/Edit Modals
    function openAddModal() {
        menuForm.reset();
        document.getElementById('item_id').value = '';
        modalTitle.innerText = 'Add New Menu Item';
        menuModal.show();
    }

    function openEditModal(id, name, category, price, desc, status) {
        menuForm.reset();
        document.getElementById('item_id').value = id;
        document.getElementById('item_name').value = name;
        document.getElementById('category_id').value = category;
        document.getElementById('price').value = price;
        document.getElementById('description').value = desc;
        document.getElementById('is_available').value = status;
        modalTitle.innerText = 'Edit Menu Item';
        menuModal.show();
    }

    // 3. Submit Form (Create / Update)
    menuForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(menuForm);
        
        fetch('api/menu_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                menuModal.hide();
                Swal.fire('Saved!', data.message, 'success');
                fetchAdminMenu(); // Refresh the table
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        });
    });

    // 4. Delete Item
    function deleteMenuItem(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "This will permanently remove the item from the menu.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('api/menu_handler.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ item_id: id })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire('Deleted!', data.message, 'success');
                        fetchAdminMenu(); // Refresh the table
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                });
            }
        });
    }
</script>

<?php include 'includes/footer.php'; ?>