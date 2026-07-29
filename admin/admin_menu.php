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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>OrderPilot - Menu Management</title>
<link rel="stylesheet" href="../css/admin_menu.css">
</head>
<body>

<?php include 'admin_header.php'; ?>

<div class="app-body">

    <?php include 'admin_sidebar.php'; ?>

    <main class="main-content">

        <h1 class="page-title">Menu Management</h1>

        <?php if ($menuMessage !== ''): ?>
            <div class="menu-alert <?php echo $menuStatus === 'success' ? 'menu-alert-success' : 'menu-alert-error'; ?>">
                <?php echo htmlspecialchars($menuMessage); ?>
            </div>
        <?php endif; ?>

        <section class="panel">
            <div class="panel-header">
                <h2>Menu Management</h2>
                <button type="button" class="btn btn-primary" onclick="openAddModal()">+ Add New Item</button>
            </div>

            <table class="panel-table">
                <thead>
                    <tr>
                        <th>Food Item</th>
                        <th>Price (RM)</th>
                        <th class="actions-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($menuItems)): ?>
                    <tr>
                        <td colspan="3" class="empty-row">No menu items yet. Click "+ Add New Item" to create one.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($menuItems as $item): ?>
                    <tr>
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
                                    data-category-id="<?php echo (int) $item['category_id']; ?>">✎</button>

                            <form method="POST" action="menu_action.php" class="inline-form">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>">
                                <button type="submit" class="icon-btn icon-btn-delete" title="Delete">🗑</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
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

        <form id="menuItemForm" method="POST" action="menu_action.php">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="formId" value="">

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

<script src="admin_menu.js"></script>
</body>
</html>