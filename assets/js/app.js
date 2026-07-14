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
            fetch('api/logout_handler.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        window.location.href = 'index.html';
                    }
                });
        }
    });
}