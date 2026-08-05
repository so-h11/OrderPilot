document.addEventListener('DOMContentLoaded', () => {

    // Logout is handled by logout() in app.js (loaded in admin_menu.php's
    // <head>), which shows its own SweetAlert confirmation before
    // navigating away. Nothing extra to wire up here.

    // Close the menu modal if the user clicks the dark overlay itself.
    const menuModalOverlay = document.getElementById('menuModalOverlay');
    if (menuModalOverlay) {
        menuModalOverlay.addEventListener('click', (e) => {
            if (e.target === menuModalOverlay) {
                closeMenuModal();
            }
        });
    }

    // ---- Close the filter dropdown on outside click ----------------
    document.addEventListener('click', (e) => {
        const filterWrap = document.querySelector('.menu-filter-wrap');
        const filterPanel = document.getElementById('filterPanel');
        if (!filterWrap || !filterPanel) return;
        if (!filterWrap.contains(e.target)) {
            filterPanel.classList.remove('open');
        }
    });

    // ---- Live image preview when a new file is chosen -------------
    const formImage = document.getElementById('formImage');
    if (formImage) {
        formImage.addEventListener('change', () => {
            const file = formImage.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = (e) => {
                document.getElementById('formImagePreview').src = e.target.result;
                setImageState(true);
            };
            reader.readAsDataURL(file);

            // Picking a new file overrides any "remove image" intent.
            document.getElementById('formRemoveImage').value = '0';
        });
    }

});

// ---- Menu item Add/Edit modal (admin_menu.php) --------------------

// Single source of truth for the image preview UI. Sets both the CSS
// classes and inline styles directly so the state is never ambiguous:
// hasImage = true  -> show the preview photo, hide placeholder + note, enable Remove
// hasImage = false -> show "No image" placeholder + note, disable Remove
function setImageState(hasImage) {
    const preview = document.getElementById('formImagePreview');
    const placeholder = document.getElementById('formImagePlaceholder');
    const removeBtn = document.getElementById('formRemoveImageBtn');
    const note = document.getElementById('formNoImageNote');

    if (hasImage) {
        preview.classList.add('visible');
        preview.style.display = 'block';
        placeholder.classList.add('hidden');
        placeholder.style.display = 'none';
        removeBtn.disabled = false;
        note.classList.add('hidden');
    } else {
        preview.classList.remove('visible');
        preview.style.display = 'none';
        preview.src = '';
        placeholder.classList.remove('hidden');
        placeholder.style.display = 'flex';
        removeBtn.disabled = true;
        note.classList.remove('hidden');
    }
}

function resetImageField() {
    document.getElementById('formCurrentImage').value = '';
    document.getElementById('formImage').value = '';
    document.getElementById('formRemoveImage').value = '0';
    setImageState(false);
}

function removeCurrentImage() {
    document.getElementById('formRemoveImage').value = '1';
    document.getElementById('formCurrentImage').value = '';
    document.getElementById('formImage').value = '';
    setImageState(false);
}

function openAddModal() {
    document.getElementById('menuModalTitle').textContent = 'Add New Item';
    document.getElementById('formAction').value = 'add';
    document.getElementById('formId').value = '';
    document.getElementById('formName').value = '';
    document.getElementById('formDescription').value = '';
    document.getElementById('formCategoryId').value = '1';
    document.getElementById('formPrice').value = '';
    document.getElementById('formAvailable').checked = true;

    resetImageField();

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

    resetImageField();

    const imagePath = button.dataset.image || '';
    document.getElementById('formCurrentImage').value = imagePath;
    document.getElementById('formRemoveImage').value = '0';

    if (imagePath) {
        document.getElementById('formImagePreview').src = '../' + imagePath;
        setImageState(true);
    } else {
        setImageState(false);
    }

    document.getElementById('menuModalOverlay').classList.add('open');
    document.getElementById('formName').focus();
}

function closeMenuModal() {
    document.getElementById('menuModalOverlay').classList.remove('open');
}

// ---- Delete confirmation (styled to match logout()'s Swal popup) --

function confirmDeleteItem(button) {
    const formId = button.dataset.formId;
    const itemName = button.dataset.name;

    Swal.fire({
        title: 'Delete this item?',
        text: `Are you sure you want to delete "${itemName}"? This can't be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById(formId).submit();
        }
    });
}

// ---- Search & filter (admin_menu.php) ------------------------------
//
// Everything runs client-side against the rows already rendered by
// PHP, each of which carries data-name / data-description /
// data-category-id / data-category-name / data-available. No page
// reload needed.

function toggleFilterPanel() {
    document.getElementById('filterPanel').classList.toggle('open');
}

function clearMenuFilters() {
    document.querySelectorAll('.filter-category-checkbox, .filter-availability-checkbox')
        .forEach((box) => { box.checked = false; });
    filterMenuTable();
}

function filterMenuTable() {
    const searchInput = document.getElementById('menuSearchInput');
    const searchTerm = searchInput ? searchInput.value.trim().toLowerCase() : '';

    const selectedCategories = Array.from(
        document.querySelectorAll('.filter-category-checkbox:checked')
    ).map((box) => box.value);

    const selectedAvailability = Array.from(
        document.querySelectorAll('.filter-availability-checkbox:checked')
    ).map((box) => box.value);

    // Badge on the Filter button shows how many filters are active.
    const badge = document.getElementById('filterBadge');
    const activeFilterCount = selectedCategories.length + selectedAvailability.length;
    if (badge) {
        badge.textContent = String(activeFilterCount);
        badge.classList.toggle('active', activeFilterCount > 0);
    }

    const rows = document.querySelectorAll('.menu-row');
    let visibleCount = 0;

    rows.forEach((row) => {
        const name = row.dataset.name || '';
        const description = row.dataset.description || '';
        const categoryName = row.dataset.categoryName || '';
        const categoryId = row.dataset.categoryId || '';
        const available = row.dataset.available || '';

        const matchesSearch = searchTerm === '' ||
            name.includes(searchTerm) ||
            description.includes(searchTerm) ||
            categoryName.includes(searchTerm);

        const matchesCategory = selectedCategories.length === 0 ||
            selectedCategories.includes(categoryId);

        const matchesAvailability = selectedAvailability.length === 0 ||
            selectedAvailability.includes(available);

        const isVisible = matchesSearch && matchesCategory && matchesAvailability;
        row.style.display = isVisible ? '' : 'none';
        if (isVisible) visibleCount++;
    });

    // Only show "no results" when there were rows to begin with but
    // none of them matched - the PHP-rendered "no items yet" empty
    // state is a separate row and is left alone.
    const noResultsRow = document.getElementById('noResultsRow');
    if (noResultsRow) {
        noResultsRow.style.display = (rows.length > 0 && visibleCount === 0) ? '' : 'none';
    }
}