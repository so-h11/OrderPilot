<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm mb-4">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="#"><i class="fas fa-utensils"></i> OrderPilot</a>
        
        <?php if(isset($_SESSION['user_id'])): ?>
        <div class="d-flex align-items-center">
            <span class="navbar-text text-light me-3">
                Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?> 
                (<small><?php echo htmlspecialchars($_SESSION['role']); ?></small>)
            </span>
            <!-- Calls an API endpoint to destroy the session -->
            <button class="btn btn-light btn-sm" onclick="logout()">Log Out</button>
        </div>
        <?php endif; ?>
    </div>
</nav>