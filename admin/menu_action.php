<?php
session_start();
require_once 'menu_data.php';

/*
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.html');
    exit;
}
*/

global $conn;

$action = $_POST['action'] ?? '';
$status = 'error';
$message = 'No action was performed.';

switch ($action) {
    case 'add':
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = (float) ($_POST['price'] ?? 0);
        $categoryId = (int) ($_POST['category_id'] ?? 1);
        $available = isset($_POST['available']) ? 1 : 0;

        if ($name !== '') {
            $stmt = $conn->prepare('INSERT INTO menu_items (category_id, item_name, description, price, is_available) VALUES (?, ?, ?, ?, ?)');
            if ($stmt) {
                $stmt->bind_param('issdi', $categoryId, $name, $description, $price, $available);
                $success = $stmt->execute();
                $stmt->close();
                $status = $success ? 'success' : 'error';
                $message = $success ? 'Menu item added successfully.' : 'Failed to add menu item.';
            }
        }
        break;

    case 'edit':
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = (float) ($_POST['price'] ?? 0);
        $categoryId = (int) ($_POST['category_id'] ?? 1);
        $available = isset($_POST['available']) ? 1 : 0;

        if ($id > 0 && $name !== '') {
            $stmt = $conn->prepare('UPDATE menu_items SET category_id = ?, item_name = ?, description = ?, price = ?, is_available = ? WHERE item_id = ?');
            if ($stmt) {
                $stmt->bind_param('issdii', $categoryId, $name, $description, $price, $available, $id);
                $success = $stmt->execute();
                $stmt->close();
                $status = $success ? 'success' : 'error';
                $message = $success ? 'Menu item updated successfully.' : 'Failed to update menu item.';
            }
        }
        break;

    case 'delete':
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $stmt = $conn->prepare('DELETE FROM menu_items WHERE item_id = ?');
            if ($stmt) {
                $stmt->bind_param('i', $id);
                $success = $stmt->execute();
                $stmt->close();
                $status = $success ? 'success' : 'error';
                $message = $success ? 'Menu item deleted successfully.' : 'Failed to delete menu item.';
            }
        }
        break;
}

$_SESSION['menu_message'] = $message;
$_SESSION['menu_status'] = $status;

header('Location: admin_menu.php');
exit;