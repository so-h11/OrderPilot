<?php
session_start();
header('Content-Type: application/json');
require_once '../db_connect.php';

// Determine the HTTP request method
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // ==========================================
        // FETCH ALL MENU ITEMS (Read)
        // ==========================================
        // Join with menu_categories to get the readable category name
        $sql = "SELECT m.item_id, m.category_id, m.item_name, m.description, m.price, m.image_path, m.is_available, c.category_name 
                FROM menu_items m 
                LEFT JOIN menu_categories c ON m.category_id = c.category_id 
                ORDER BY m.category_id ASC, m.item_name ASC";
                
        $result = $conn->query($sql);
        $items = [];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // strict type-casting ensures the Customer Menu JS filter works perfectly
                $row['item_id'] = intval($row['item_id']);
                $row['category_id'] = intval($row['category_id']);
                $row['price'] = floatval($row['price']);
                $row['is_available'] = intval($row['is_available']); 
                
                $items[] = $row;
            }
        }
        
        echo json_encode(["status" => "success", "data" => $items]);
        break;

    case 'POST':
        // ==========================================
        // ADD OR EDIT MENU ITEM (Create / Update)
        // ==========================================
        // Capture form data securely
        $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
        $item_name = $conn->real_escape_string($_POST['item_name']);
        $category_id = intval($_POST['category_id']);
        $price = floatval($_POST['price']);
        $description = $conn->real_escape_string($_POST['description']);
        $is_available = intval($_POST['is_available']);

        // Handle Image File Upload (if a file was provided)
        $image_path = null;
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/uploads/';
            
            // Create the directory if it doesn't exist yet
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate a unique filename to prevent overwriting
            $file_name = time() . '_' . basename($_FILES['image_file']['name']);
            $target_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $target_path)) {
                // Save the relative path for the database
                $image_path = 'assets/uploads/' . $file_name; 
            }
        }

        if ($item_id > 0) {
            // ID exists: UPDATE an existing record
            $sql = "UPDATE menu_items SET 
                        item_name = '$item_name', 
                        category_id = $category_id, 
                        price = $price, 
                        description = '$description', 
                        is_available = $is_available";
            
            // Only update the image path if a new image was uploaded
            if ($image_path) {
                $sql .= ", image_path = '$image_path'";
            }
            $sql .= " WHERE item_id = $item_id";

            if ($conn->query($sql) === TRUE) {
                echo json_encode(["status" => "success", "message" => "Menu item updated successfully."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Error updating item: " . $conn->error]);
            }
        } else {
            // No ID: INSERT a new record
            $img_col = $image_path ? ", image_path" : "";
            $img_val = $image_path ? ", '$image_path'" : "";
            
            $sql = "INSERT INTO menu_items (item_name, category_id, price, description, is_available $img_col) 
                    VALUES ('$item_name', $category_id, $price, '$description', $is_available $img_val)";
            
            if ($conn->query($sql) === TRUE) {
                echo json_encode(["status" => "success", "message" => "Menu item added successfully."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Error adding item: " . $conn->error]);
            }
        }
        break;

    case 'DELETE':
        // ==========================================
        // REMOVE MENU ITEM (Delete)
        // ==========================================
        // PHP does not automatically parse JSON body for DELETE requests, so we read the raw input
        $data = json_decode(file_get_contents("php://input"), true);
        $item_id = isset($data['item_id']) ? intval($data['item_id']) : 0;
        
        if ($item_id > 0) {
            $sql = "DELETE FROM menu_items WHERE item_id = $item_id";
            if ($conn->query($sql) === TRUE) {
                echo json_encode(["status" => "success", "message" => "Menu item deleted successfully."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Error deleting item: " . $conn->error]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Invalid Item ID provided."]);
        }
        break;

    default:
        // Reject unsupported request types
        echo json_encode(["status" => "error", "message" => "Invalid request method."]);
        break;
}

$conn->close();
?>