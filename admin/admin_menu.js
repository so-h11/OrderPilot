document.addEventListener('DOMContentLoaded', () => {

    // ---- Logout confirmation -------------------------------------
    // Session destroy + redirect to index.html happens server-side
    // in logout.php. This just confirms before navigating away.
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', (e) => {
            const confirmed = confirm('Are you sure you want to log out?');
            if (!confirmed) {
                e.preventDefault();
            }
        });
    }

    // Note: "+ Add Staff" on the dashboard/staff pages is intentionally
    // not wired up yet. The Menu page's Add/Edit/Delete buttons below
    // ARE wired up, posting to menu_actions.php.

    // Close the menu modal if the user clicks the dark overlay itself.
    const menuModalOverlay = document.getElementById('menuModalOverlay');
    if (menuModalOverlay) {
        menuModalOverlay.addEventListener('click', (e) => {
            if (e.target === menuModalOverlay) {
                closeMenuModal();
            }
        });
    }

});

// ---- Menu item Add/Edit modal (admin_menu.php) --------------------

function openAddModal() {
    document.getElementById('menuModalTitle').textContent = 'Add New Item';
    document.getElementById('formAction').value = 'add';
    document.getElementById('formId').value = '';
    document.getElementById('formName').value = '';
    document.getElementById('formDescription').value = '';
    document.getElementById('formCategoryId').value = '1';
    document.getElementById('formPrice').value = '';
    document.getElementById('formAvailable').checked = true;
    document.getElementById('menuModalOverlay').classList.add('open');
    document.getElementById('formName').focus();
}

function openEditModal(button) {
    document.getElementById('menuModalTitle').textContent = 'Edit Item';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('formId').value = button.dataset.id;
    document.getElementById('formName').value = button.dataset.name;
    document.getElementById('formDescription').value = button.dataset.description;
    document.getElementById('formPrice').value = button.dataset.price;
    document.getElementById('formCategoryId').value = button.dataset.categoryId || '1';
    document.getElementById('formAvailable').checked = button.dataset.available === '1';
    document.getElementById('menuModalOverlay').classList.add('open');
    document.getElementById('formName').focus();
}

function closeMenuModal() {
    document.getElementById('menuModalOverlay').classList.remove('open');
}