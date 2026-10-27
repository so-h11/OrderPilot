<?php
// Expects $adminName to be set by the including page (falls back below).
$adminName = $adminName ?? ($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'System Admin');
$adminRole = $adminRole ?? ($_SESSION['role'] ?? 'Administrator');
?>
<header class="topbar">
    <div class="brand">
        <img src="../assets/images/orderpilot-logo-placeholder.svg" alt="OrderPilot logo placeholder" width="180" height="50">
    </div>
    <div class="topbar-right">
        <span class="welcome-text">
            Welcome, <strong><?php echo htmlspecialchars($adminName); ?></strong>
            (<?php echo htmlspecialchars($adminRole); ?>)
        </span>
        <button type="button" id="logoutBtn" class="btn btn-logout" onclick="logout()">Log Out</button>
    </div>
</header>