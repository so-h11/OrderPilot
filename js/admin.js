document.addEventListener('DOMContentLoaded', () => {

    // ---- Logout confirmation -------------------------------------
    // Session destroy + redirect to index.html happens server-side
    // in logout.php. This just confirms before navigating away.
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', (e) => {
            const confirmed = confirm('Are you sure you want to log out?');
            if (!confirmed) {
                e.preventDefault();
            }
        });
    }

    // Note: "+ Add Staff" / "+ Add New Item" / edit / delete buttons
    // are intentionally not wired up yet, as requested. Hook them up
    // to real endpoints (e.g. add_staff.php, add_menu_item.php) when
    // the backend logic is ready.

});
