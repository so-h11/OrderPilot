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

        if ($name === '') {
            $message = 'Food item name is required.';
            break;
        }

        $imageResult = saveUploadedMenuImage($_FILES['image'] ?? []);
        if (!$imageResult['success']) {
            $message = $imageResult['error'];
            break;
        }
        $imagePath = $imageResult['path']; // null if no image was chosen

        $stmt = $conn->prepare('INSERT INTO menu_items (category_id, item_name, description, price, is_available, image) VALUES (?, ?, ?, ?, ?, ?)');
        if (!$stmt) {
            $message = 'Failed to prepare add statement: ' . $conn->error;
            deleteMenuImageFile($imagePath); // don't leave an orphaned file
            break;
        }

        $stmt->bind_param('issdis', $categoryId, $name, $description, $price, $available, $imagePath);
        $success = $stmt->execute();

        if ($success) {
            $status = 'success';
            $message = 'Menu item added successfully.';
        } else {
            $message = 'Failed to add menu item: ' . $stmt->error;
            deleteMenuImageFile($imagePath); // roll back the uploaded file too
        }
        $stmt->close();
        break;

    case 'edit':
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = (float) ($_POST['price'] ?? 0);
        $categoryId = (int) ($_POST['category_id'] ?? 1);
        $available = isset($_POST['available']) ? 1 : 0;
        $currentImage = $_POST['current_image'] ?? null;
        $removeImage = ($_POST['remove_image'] ?? '0') === '1';

        if ($id <= 0 || $name === '') {
            $message = 'A valid item and food item name are required.';
            break;
        }

        $imageResult = saveUploadedMenuImage($_FILES['image'] ?? []);
        if (!$imageResult['success']) {
            $message = $imageResult['error'];
            break;
        }

        if ($imageResult['path'] !== null) {
            // A new image was uploaded - it replaces whatever was there.
            $newImagePath = $imageResult['path'];
            $imageToDeleteOnSuccess = $currentImage;
        } elseif ($removeImage) {
            // No new upload, but the admin checked "remove current image".
            $newImagePath = null;
            $imageToDeleteOnSuccess = $currentImage;
        } else {
            // No change to the image.
            $newImagePath = $currentImage;
            $imageToDeleteOnSuccess = null;
        }

        $stmt = $conn->prepare('UPDATE menu_items SET category_id = ?, item_name = ?, description = ?, price = ?, is_available = ?, image = ? WHERE item_id = ?');
        if (!$stmt) {
            $message = 'Failed to prepare edit statement: ' . $conn->error;
            deleteMenuImageFile($imageResult['path']); // don't leave an orphaned upload
            break;
        }

        $stmt->bind_param('issdisi', $categoryId, $name, $description, $price, $available, $newImagePath, $id);
        $success = $stmt->execute();

        if ($success) {
            $status = 'success';
            $message = 'Menu item updated successfully.';
            deleteMenuImageFile($imageToDeleteOnSuccess);
        } else {
            $message = 'Failed to update menu item: ' . $stmt->error;
            deleteMenuImageFile($imageResult['path']); // roll back the new upload
        }
        $stmt->close();
        break;

    case 'delete':
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            $message = 'A valid item is required to delete.';
            break;
        }

        // Look up the image path first so we can clean up the file too.
        $existingImage = null;
        $imgStmt = $conn->prepare('SELECT image FROM menu_items WHERE item_id = ?');
        if ($imgStmt) {
            $imgStmt->bind_param('i', $id);
            $imgStmt->execute();
            $imgStmt->bind_result($existingImage);
            $imgStmt->fetch();
            $imgStmt->close();
        }

        $stmt = $conn->prepare('DELETE FROM menu_items WHERE item_id = ?');
        if (!$stmt) {
            $message = 'Failed to prepare delete statement: ' . $conn->error;
            break;
        }

        $stmt->bind_param('i', $id);
        $success = $stmt->execute();

        if ($success) {
            $status = 'success';
            $message = 'Menu item deleted successfully.';
            deleteMenuImageFile($existingImage);
        } else {
            $message = 'Failed to delete menu item: ' . $stmt->error;
        }
        $stmt->close();
        break;
}

$_SESSION['menu_message'] = $message;
$_SESSION['menu_status'] = $status;

header('Location: admin_menu.php');
exit;