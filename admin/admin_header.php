<?php
// Expects $adminName to be set by the including page (falls back below).
$adminName = $adminName ?? ($_SESSION['staff_name'] ?? 'System Admin');
$adminRole = $adminRole ?? ($_SESSION['role_label'] ?? 'Administrator');
?>
<header class="topbar">
    <div class="brand">
        <span class="brand-icon">🍴</span>
        <span class="brand-name">OrderPilot</span>
    </div>
    <div class="topbar-right">
        <span class="welcome-text">
            Welcome, <strong><?php echo htmlspecialchars($adminName); ?></strong>
            (<?php echo htmlspecialchars($adminRole); ?>)
        </span>
        <button type="button" id="logoutBtn" class="btn btn-logout" onclick="logout()">Log Out</button>
    </div>
</header>