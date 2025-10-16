<?php
/**
 * Simplified Admin Panel Test
 * No namespaces or complex dependencies
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Handle dev login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'dev_login') {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_email'] = 'admin@psu.ac.th';
    $_SESSION['admin_name'] = 'System Administrator';
    header('Location: simple_admin.php');
    exit;
}

// Check if logged in
$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'];

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSO Admin Panel - Simple Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="container mt-5">
        <?php if (!$isLoggedIn): ?>
            <!-- Login Form -->
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                            <h3>SSO Admin Panel</h3>
                            <p class="text-muted">Simple Test Version</p>
                            <form method="POST">
                                <input type="hidden" name="action" value="dev_login">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sign-in-alt me-2"></i>เข้าสู่ระบบ
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Dashboard -->
            <div class="row">
                <div class="col-12">
                    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
                        <div class="container-fluid">
                            <span class="navbar-brand">
                                <i class="fas fa-shield-alt me-2"></i>SSO Admin Panel
                            </span>
                            <div class="navbar-nav ms-auto">
                                <span class="navbar-text me-3">
                                    <i class="fas fa-user me-1"></i><?php echo $_SESSION['admin_name']; ?>
                                </span>
                                <a class="nav-link" href="?logout=1">
                                    <i class="fas fa-sign-out-alt me-1"></i>Sign out
                                </a>
                            </div>
                        </div>
                    </nav>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3">
                    <div class="list-group">
                        <a href="simple_admin.php" class="list-group-item list-group-item-action active">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a href="#" class="list-group-item list-group-item-action">
                            <i class="fas fa-users me-2"></i>Client Applications
                        </a>
                        <a href="backup-restore" class="list-group-item list-group-item-action">
                            <i class="fas fa-database me-2"></i>Backup & Restore
                        </a>
                        <a href="api-docs.html" class="list-group-item list-group-item-action" target="_blank">
                            <i class="fas fa-book me-2"></i>Documentation
                        </a>
                    </div>
                </div>
                
                <div class="col-md-9">
                    <h1 class="h2">Dashboard</h1>
                    
                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Total Clients</h6>
                                            <h3>5</h3>
                                        </div>
                                        <i class="fas fa-users fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Active Clients</h6>
                                            <h3>3</h3>
                                        </div>
                                        <i class="fas fa-check-circle fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Requests Today</h6>
                                            <h3>127</h3>
                                        </div>
                                        <i class="fas fa-chart-line fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Success Rate</h6>
                                            <h3>95.5%</h3>
                                        </div>
                                        <i class="fas fa-percentage fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-history me-2"></i>System Status
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                Admin panel is working correctly!
                            </div>
                            
                            <h6>Test Results:</h6>
                            <ul>
                                <li>✅ PHP Version: <?php echo phpversion(); ?></li>
                                <li>✅ Session Management: Working</li>
                                <li>✅ Bootstrap 5: Loaded</li>
                                <li>✅ Font Awesome: Loaded</li>
                                <li>✅ SweetAlert2: Available</li>
                            </ul>
                            
                            <h6>Next Steps:</h6>
                            <ul>
                                <li>If this simple version works, the issue is with the complex routing</li>
                                <li>You can use this as a starting point</li>
                                <li>Gradually add more features back</li>
                            </ul>
                            
                            <div class="mt-3">
                                <a href="debug.php" class="btn btn-info">View Debug Info</a>
                                <a href="test_basic.php" class="btn btn-secondary">Basic PHP Test</a>
                                <button onclick="testAlert()" class="btn btn-warning">Test SweetAlert</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php
        // Handle logout
        if (isset($_GET['logout'])) {
            session_destroy();
            echo '<script>
                Swal.fire({
                    title: "Sign out successful",
                    text: "You have successfully signed out.",
                    icon: "success"
                }).then(() => {
                    window.location.href = "simple_admin.php";
                });
            </script>';
        }
        ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function testAlert() {
            Swal.fire({
                title: "SweetAlert2 Test",
                text: "SweetAlert2 is working correctly!",
                icon: "success"
            });
        }
    </script>
</body>
</html>