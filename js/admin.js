document.addEventListener('DOMContentLoaded', () => {

    // ---- Logout confirmation -------------------------------------
    // Handled entirely by logout() in app.js, wired up via the
    // button's onclick="logout()" in admin_header.php. That function
    // shows its own SweetAlert confirmation before navigating away,
    // so no extra listener is needed here.

    // Note: "+ Add Staff" / "+ Add New Item" / edit / delete buttons
    // are intentionally not wired up yet, as requested. Hook them up
    // to real endpoints (e.g. add_staff.php, add_menu_item.php) when
    // the backend logic is ready.

});