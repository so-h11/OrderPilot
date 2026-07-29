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
    $sql = "SELECT item_id AS id, category_id, item_name AS name, description, price, is_available AS available
            FROM menu_items
            ORDER BY item_id ASC";

    $result = $conn->query($sql);

    if ($result === false) {
        // Surface the real MySQL error (e.g. wrong table/column name)
        // instead of quietly returning an empty menu list.
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