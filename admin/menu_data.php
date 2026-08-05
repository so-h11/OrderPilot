<?php
// Try both common locations for db_connect.php: right here in admin/,
// or one folder up at the site root. Whichever exists first wins.
$dbConnectCandidates = [
    __DIR__ . '/db_connect.php',
    __DIR__ . '/../db_connect.php',
];

$dbConnectFound = null;
foreach ($dbConnectCandidates as $candidate) {
    if (file_exists($candidate)) {
        $dbConnectFound = $candidate;
        break;
    }
}

if ($dbConnectFound === null) {
    die(
        'menu_data.php: could not find db_connect.php. Checked these locations: ' .
        implode(', ', $dbConnectCandidates) .
        '. Edit the $dbConnectCandidates list at the top of menu_data.php if it lives somewhere else.'
    );
}

require_once $dbConnectFound;

// If db_connect.php didn't produce a working mysqli connection in $conn,
// say so clearly instead of letting the page die with no output at all.
if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    $reason = (isset($conn) && $conn instanceof mysqli)
        ? $conn->connect_error
        : '$conn was not set to a mysqli connection by db_connect.php.';
    die('menu_data.php: database connection failed - ' . $reason);
}

function loadMenuItems(): array
{
    global $conn;

    $items = [];
        // Prefer either `image` or `image_path` depending on which column holds the upload.
        $sql = "SELECT item_id AS id, category_id, item_name AS name, description, price, is_available AS available,
               COALESCE(NULLIF(image, ''), NULLIF(image_path, '')) AS image
            FROM menu_items
            ORDER BY item_id ASC";

    $result = $conn->query($sql);

    if ($result === false) {
        die('loadMenuItems() query failed: ' . $conn->error);
    }

    while ($row = $result->fetch_assoc()) {
        $items[] = [
            'id' => (int) $row['id'],
            'category_id' => (int) $row['category_id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'price' => (float) $row['price'],
            'available' => (bool) $row['available'],
            'image' => $row['image'], // relative path from the site root, or null
        ];
    }

    return $items;
}

function loadMenuCategories(): array
{
    global $conn;

    $categories = [];
    $sql = "SELECT category_id, category_name FROM menu_categories ORDER BY category_id ASC";
    $result = $conn->query($sql);

    if ($result === false) {
        die('loadMenuCategories() query failed: ' . $conn->error);
    }

    while ($row = $result->fetch_assoc()) {
        $categories[] = [
            'id' => (int) $row['category_id'],
            'name' => $row['category_name'],
        ];
    }

    return $categories;
}

function saveMenuItems(array $items): void
{
    // The database is the source of truth for menu items.
    // This function is kept for compatibility with the existing flow.
}

function nextMenuItemId(array $items): int
{
    $max = 0;
    foreach ($items as $item) {
        $max = max($max, (int) $item['id']);
    }
    return $max + 1;
}

/**
 * Handles one uploaded image file for a menu item.
 *
 * If no file was chosen, this is NOT an error - it just means "keep
 * whatever's already there" and returns success with a null path.
 *
 * @param array $file One entry from $_FILES, e.g. $_FILES['image']
 * @return array{success: bool, path: ?string, error: ?string}
 */
function saveUploadedMenuImage(array $file): array
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'path' => null, 'error' => null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'path' => null, 'error' => 'Image upload failed (error code ' . $file['error'] . ').'];
    }

    $maxBytes = 2 * 1024 * 1024; // 2MB
    if ($file['size'] > $maxBytes) {
        return ['success' => false, 'path' => null, 'error' => 'Image must be 2MB or smaller.'];
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExtensions, true)) {
        return ['success' => false, 'path' => null, 'error' => 'Only JPG, PNG, GIF, or WEBP images are allowed.'];
    }

    // Basic sanity check that this is really an image file, not just
    // something renamed to end in .jpg.
    if (@getimagesize($file['tmp_name']) === false) {
        return ['success' => false, 'path' => null, 'error' => 'The uploaded file is not a valid image.'];
    }

    $uploadDir = __DIR__ . '/../uploads/menu/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0775, true);
    }

    if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
        return ['success' => false, 'path' => null, 'error' => 'Upload folder is missing or not writable: ' . $uploadDir];
    }

    $filename = uniqid('menu_', true) . '.' . $ext;
    $destination = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'path' => null, 'error' => 'Failed to save the uploaded image to disk.'];
    }

    // Stored relative to the site root, e.g. "uploads/menu/menu_xxx.jpg"
    return ['success' => true, 'path' => 'uploads/menu/' . $filename, 'error' => null];
}

/**
 * Deletes a menu item's image file from disk, given the relative path
 * stored in the database. Safe to call with null/empty - does nothing.
 */
function deleteMenuImageFile(?string $relativePath): void
{
    if (empty($relativePath)) {
        return;
    }

    $fullPath = __DIR__ . '/../' . $relativePath;
    if (is_file($fullPath)) {
        @unlink($fullPath);
    }
}