<?php
session_start();

/*
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.html');
    exit;
}
*/

$activePage = 'orders';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>OrderPilot - Orders</title>
<link rel="stylesheet" href="../css/admin.css">
</head>
<body>

<?php include 'includes/admin_header.php'; ?>

<div class="app-body">

    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <h1 class="page-title">Orders Management</h1>

        <section class="panel">
            <p>This is the <strong>Orders Management</strong> page. Build out its content here.</p>
        </section>
    </main>
</div>

<script src="js/admin.js"></script>
</body>
</html>