<?php
// Ensure session support for pages that include this header.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If this page is loaded from a subfolder such as /cashier/ or /admin/, adjust asset paths.
$assetsPrefix = '';
if (preg_match('#/(cashier|admin)/#', $_SERVER['REQUEST_URI'])) {
    $assetsPrefix = '../';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OrderPilot POS</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= $assetsPrefix ?>assets/css/style.css">
</head>
<body class="bg-light">