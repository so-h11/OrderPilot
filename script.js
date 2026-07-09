function simulateDevicePairing() {
    // Step 1: Hide the Bootstrap Modal using jQuery
    $('#pairingModal').modal('hide');

    // Step 2: Show loading indicator using SweetAlert2
    Swal.fire({
        title: 'Authenticating...',
        html: 'Waiting for secondary device to connect.',
        timer: 1500,
        timerProgressBar: true,
        didOpen: () => {
            Swal.showLoading()
        }
    }).then((result) => {
        // Step 3: Show Success Alert using SweetAlert2 after the timer finishes
        Swal.fire({
            icon: 'success',
            title: 'Device Paired Successfully!',
            text: 'Kitchen Display 1 is now synced with the Main Hub.',
            confirmButtonColor: '#28a745'
        }).then(() => {
            // Step 4: Visually update the device count on the dashboard
            document.getElementById('deviceCount').innerText = "3 Active";
        });
    });
}