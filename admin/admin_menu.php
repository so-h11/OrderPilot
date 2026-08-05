<?php
session_start();
require_once 'menu_data.php';

/*
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.html');
    exit;
}
*/

$activePage = 'menu';

// #43 - View menu items
$menuItems = loadMenuItems();
$menuCategories = loadMenuCategories();
$menuMessage = $_SESSION['menu_message'] ?? '';
$menuStatus = $_SESSION['menu_status'] ?? 'error';
unset($_SESSION['menu_message'], $_SESSION['menu_status']);

// #45/#46 - Search & filter: quick lookup of category name by id, used
// to stamp each row with a searchable category name.
$menuCategoryNameById = [];
foreach ($menuCategories as $category) {
    $menuCategoryNameById[$category['id']] = $category['name'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>OrderPilot - Menu Management</title>
<link rel="stylesheet" href="../css/admin_menu.css?v=<?php echo @filemtime(__DIR__ . '/../css/admin_menu.css') ?: time(); ?>">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/app.js"></script>
</head>
<body>

<?php include 'admin_header.php'; ?>

<div class="app-body">

    <?php include 'admin_sidebar.php'; ?>

    <main class="main-content">

        <h1 class="page-title">Menu Management</h1>

        <section class="panel">
            <div class="panel-header">
                <h2>Item List</h2>
                <div class="panel-header-actions">
                    <div class="menu-search-wrap">
                        <span class="menu-search-icon">🔍</span>
                        <input type="text"
                               id="menuSearchInput"
                               class="menu-search-input"
                               placeholder="Search by name, description, or category"
                               oninput="filterMenuTable()"
                               autocomplete="off">
                    </div>

                    <div class="menu-filter-wrap">
                        <button type="button" class="btn btn-secondary btn-filter" id="menuFilterBtn" onclick="toggleFilterPanel()">
                            Filter <span class="filter-badge" id="filterBadge">0</span>
                        </button>

                        <div class="filter-panel" id="filterPanel">
                            <div class="filter-section">
                                <div class="filter-section-title">Category</div>
                                <div class="filter-options">
                                    <?php foreach ($menuCategories as $category): ?>
                                        <label class="filter-checkbox">
                                            <input type="checkbox"
                                                   class="filter-category-checkbox"
                                                   value="<?php echo (int) $category['id']; ?>"
                                                   onchange="filterMenuTable()">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="filter-section">
                                <div class="filter-section-title">Availability</div>
                                <div class="filter-options">
                                    <label class="filter-checkbox">
                                        <input type="checkbox" class="filter-availability-checkbox" value="1" onchange="filterMenuTable()">
                                        Available
                                    </label>
                                    <label class="filter-checkbox">
                                        <input type="checkbox" class="filter-availability-checkbox" value="0" onchange="filterMenuTable()">
                                        Unavailable
                                    </label>
                                </div>
                            </div>

                            <div class="filter-panel-actions">
                                <button type="button" class="btn-filter-clear" onclick="clearMenuFilters()">Clear all</button>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-primary" onclick="openAddModal()">+ Add New Item</button>
                </div>
            </div>

            <table class="panel-table">
                <thead>
                    <tr>
                        <th class="menu-image-col">Image</th>
                        <th>Food Item</th>
                        <th>Price (RM)</th>
                        <th class="actions-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($menuItems)): ?>
                    <tr>
                        <td colspan="4" class="empty-row">No menu items yet. Click "+ Add New Item" to create one.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($menuItems as $item): ?>
                    <tr class="menu-row"
                        data-name="<?php echo htmlspecialchars(mb_strtolower($item['name']), ENT_QUOTES); ?>"
                        data-description="<?php echo htmlspecialchars(mb_strtolower($item['description'] ?? ''), ENT_QUOTES); ?>"
                        data-category-id="<?php echo (int) $item['category_id']; ?>"
                        data-category-name="<?php echo htmlspecialchars(mb_strtolower($menuCategoryNameById[$item['category_id']] ?? ''), ENT_QUOTES); ?>"
                        data-available="<?php echo $item['available'] ? '1' : '0'; ?>">
                        <td class="menu-image-cell">
                            <?php if (!empty($item['image'])): ?>
                                <img src="../<?php echo htmlspecialchars($item['image']); ?>" alt="" class="menu-thumb">
                            <?php else: ?>
                                <div class="menu-thumb menu-thumb-placeholder">🍽️</div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="cell-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <?php if (!empty($item['description'])): ?>
                                <div class="cell-subtitle"><?php echo htmlspecialchars($item['description']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo number_format((float) $item['price'], 2); ?></td>
                        <td class="actions-col">
                            <span class="status-badge <?php echo $item['available'] ? 'status-available' : 'status-unavailable'; ?>">
                                <?php echo $item['available'] ? 'Available' : 'Unavailable'; ?>
                            </span>

                            <button type="button"
                                    class="icon-btn icon-btn-edit"
                                    title="Edit"
                                    onclick="openEditModal(this)"
                                    data-id="<?php echo (int) $item['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>"
                                    data-description="<?php echo htmlspecialchars($item['description'], ENT_QUOTES); ?>"
                                    data-price="<?php echo htmlspecialchars((string) $item['price'], ENT_QUOTES); ?>"
                                    data-available="<?php echo $item['available'] ? '1' : '0'; ?>"
                                    data-category-id="<?php echo (int) $item['category_id']; ?>"
                                    data-image="<?php echo htmlspecialchars($item['image'] ?? '', ENT_QUOTES); ?>">✎</button>

                            <form method="POST" action="menu_action.php" class="inline-form" id="deleteForm<?php echo (int) $item['id']; ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>">
                                <button type="button"
                                        class="icon-btn icon-btn-delete"
                                        title="Delete"
                                        onclick="confirmDeleteItem(this)"
                                        data-form-id="deleteForm<?php echo (int) $item['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>">🗑</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <tr id="noResultsRow" class="empty-row-tr" style="display: none;">
                    <td colspan="4" class="empty-row">No menu items match your search or filter.</td>
                </tr>
                </tbody>
            </table>
        </section>

    </main>
</div>

<!-- Add / Edit item modal (shared by #42 Add and #44 Edit) -->
<div class="modal-overlay" id="menuModalOverlay">
    <div class="modal">
        <div class="modal-header">
            <h3 id="menuModalTitle">Add New Item</h3>
            <button type="button" class="modal-close" onclick="closeMenuModal()">&times;</button>
        </div>

        <form id="menuItemForm" method="POST" action="menu_action.php" enctype="multipart/form-data">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="formId" value="">
            <input type="hidden" name="current_image" id="formCurrentImage" value="">

            <div class="form-group">
                <label for="formName">Food Item Name</label>
                <input type="text" name="name" id="formName" required>
            </div>

            <div class="form-group">
                <label for="formDescription">Description</label>
                <textarea name="description" id="formDescription"></textarea>
            </div>

            <div class="form-group">
                <label for="formCategoryId">Category</label>
                <select name="category_id" id="formCategoryId" required>
                    <?php foreach ($menuCategories as $category): ?>
                        <option value="<?php echo (int) $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="formPrice">Price (RM)</label>
                <input type="number" name="price" id="formPrice" step="0.01" min="0" required>
            </div>

            <div class="form-group">
                <label>Food Image</label>
                <div class="image-upload-row">
                    <div class="image-upload-preview">
                        <div id="formImagePlaceholder" class="image-upload-placeholder">
                            <span class="placeholder-icon">🍽️</span>
                        </div>
                        <img id="formImagePreview" class="image-preview-img" src="" alt="Selected image preview">
                    </div>
                    <div class="image-upload-actions">
                        <label class="btn btn-secondary btn-upload" for="formImage">Upload an Image</label>
                        <input type="file" name="image" id="formImage" accept="image/*">
                        <button type="button" class="btn btn-danger btn-remove-image" id="formRemoveImageBtn" onclick="removeCurrentImage()" disabled>Remove Image</button>
                    </div>
                </div>
                <p id="formNoImageNote" class="no-image-note">This item has no image yet.</p>
                <input type="hidden" name="remove_image" id="formRemoveImage" value="0">
            </div>

            <div class="form-group">
                <label class="form-checkbox">
                    <input type="checkbox" name="available" id="formAvailable" checked>
                    Available
                </label>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeMenuModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Item</button>
            </div>
        </form>
    </div>
</div>

<script src="admin_menu.js?v=<?php echo @filemtime(__DIR__ . '/admin_menu.js') ?: time(); ?>"></script>

<?php if ($menuMessage !== ''): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    Swal.fire({
        icon: <?php echo json_encode($menuStatus === 'success' ? 'success' : 'error'); ?>,
        title: <?php echo json_encode($menuStatus === 'success' ? 'Success' : 'Something went wrong'); ?>,
        text: <?php echo json_encode($menuMessage); ?>,
        confirmButtonColor: '#6c5ce7'
    });
});
</script>
<?php endif; ?>
</body>
</html>