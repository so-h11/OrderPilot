<?php
// Expects $activePage to be set by the including page, e.g. 'dashboard'
$activePage = $activePage ?? 'dashboard';

$navItems = [
    'dashboard' => ['label' => 'Dashboard', 'icon' => '🖥️', 'href' => 'admin_dashboard.php'],
    'staff'     => ['label' => 'Staff',     'icon' => '🗂️', 'href' => 'admin_staff.php'],
    'menu'      => ['label' => 'Menu',      'icon' => '📋', 'href' => 'admin_menu.php'],
    'orders'    => ['label' => 'Orders',    'icon' => '🔀', 'href' => 'admin_orders.php'],
    'reports'   => ['label' => 'Reports',   'icon' => '📈', 'href' => 'admin_reports.php'],
];
?>
<aside class="sidebar">
    <nav class="sidebar-nav">
        <?php foreach ($navItems as $key => $item): ?>
            <a href="<?php echo $item['href']; ?>"
               class="sidebar-link <?php echo $activePage === $key ? 'active' : ''; ?>">
                <span class="sidebar-icon"><?php echo $item['icon']; ?></span>
                <span class="sidebar-label"><?php echo htmlspecialchars($item['label']); ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>