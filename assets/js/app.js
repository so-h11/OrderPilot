document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');

    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            // Prevent the default browser form submission
            e.preventDefault(); 

            // Visual feedback: Change button to a loading spinner
            const loginBtn = document.getElementById('loginBtn');
            const originalBtnText = loginBtn.innerHTML;
            loginBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Authenticating...';
            loginBtn.disabled = true;

            // Automatically gather all form fields with 'name' attributes
            const formData = new FormData(loginForm);

            // Send data to the PHP API
            fetch('api/login_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json()) // Parse the PHP JSON response
            .then(data => {
                if (data.status === 'success') {
                    // Success: Show a brief SweetAlert, then redirect
                    Swal.fire({
                        icon: 'success',
                        title: 'Login Successful',
                        text: 'Routing to your dashboard...',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = data.redirect;
                    });
                } else {
                    // Error: Display the message sent from PHP
                    Swal.fire({
                        icon: 'error',
                        title: 'Authentication Failed',
                        text: data.message, // e.g., "Invalid password."
                        confirmButtonColor: '#0d6efd'
                    });
                    
                    // Reset the login button
                    loginBtn.innerHTML = originalBtnText;
                    loginBtn.disabled = false;
                }
            })
            .catch(error => {
                // Handle network errors or server crashes (500 errors)
                console.error('System Error:', error);
                
                Swal.fire({
                    icon: 'error',
                    title: 'Connection Error',
                    text: 'Could not connect to the database. Check if UniServerZ Apache/MySQL modules are running.',
                    confirmButtonColor: '#0d6efd'
                });
                
                // Reset the login button
                loginBtn.innerHTML = originalBtnText;
                loginBtn.disabled = false;
            });
        });
    }
});

// Handle Logout
function logout() {
    const isAdminPage = window.location.pathname.includes('/admin/');
    const isCashierPage = window.location.pathname.includes('/cashier/');
    const isCustomerPage = window.location.pathname.includes('customer/customer_menu.php');
    const needsParentPrefix = isAdminPage || isCashierPage || isCustomerPage;
    const logoutUrl = needsParentPrefix ? '../api/logout_handler.php' : 'api/logout_handler.php';
    const homeUrl = needsParentPrefix ? '../index.html' : 'index.html';

    if (isCustomerPage) {
        Swal.fire({
            title: 'Enter logout PIN',
            input: 'password',
            inputLabel: '4-digit PIN',
            inputPlaceholder: '1234',
            inputAttributes: {
                maxlength: 4,
                inputmode: 'numeric',
                autocapitalize: 'off',
                autocorrect: 'off'
            },
            showCancelButton: true,
            confirmButtonText: 'Log Out',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            preConfirm: (pin) => {
                if (!pin) {
                    Swal.showValidationMessage('Please enter the 4-digit PIN');
                } else if (pin !== '1234') {
                    Swal.showValidationMessage('Incorrect PIN');
                }
                return pin;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(logoutUrl, { credentials: 'same-origin' })
                    .then(response => response.json().catch(() => ({})))
                    .catch(() => ({}))
                    .finally(() => {
                        window.location.replace(homeUrl);
                    });
            }
        });
        return;
    }

    Swal.fire({
        title: 'Logging out...',
        text: 'Are you sure you want to end this session?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Log Out'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(logoutUrl, { credentials: 'same-origin' })
                .then(response => response.json().catch(() => ({})))
                .catch(() => ({}))
                .finally(() => {
                    window.location.replace(homeUrl);
                });
        }
    });
}