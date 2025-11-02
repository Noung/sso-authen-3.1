<?php
/**
 * Standalone Login Page for Admin Panel
 * This ensures login always works regardless of routing issues
 */

// Start session
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
    header('Location: index.php');
    exit;
}

// Handle login POST request (development mode)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (isset($data['action']) && $data['action'] === 'dev_login') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_email'] = 'admin@psu.ac.th';
        $_SESSION['admin_name'] = 'System Administrator';
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
}

// Get base path
$basePath = dirname($_SERVER['SCRIPT_NAME']);

// Check for error messages
$errorMessage = '';
if (isset($_GET['error'])) {
    $errorMessage = $_GET['message'] ?? 'An unknown error occurred';
    
    // Make the error message more user-friendly
    if ($errorMessage === 'User not authorized to access admin panel') {
        $errorMessage = 'You are not authorized to access the admin panel. Please contact your system administrator.';
    } elseif (strpos($errorMessage, 'API Endpoint returned HTTP status') !== false) {
        $errorMessage = 'Authentication service is temporarily unavailable. Please try again later.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSO-Authen Admin Panel - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bai+Jamjuree:wght@200;300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            font-family: 'Bai Jamjuree', sans-serif;
        }
        body {
            font-family: 'Bai Jamjuree', sans-serif;
        }
        .login-card {
            border-radius: 15px;
        }
        .btn-oidc {
            background-color: #4285f4;
            border-color: #4285f4;
            color: white;
        }
        .btn-oidc:hover {
            background-color: #3367d6;
            border-color: #3367d6;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow mt-5 login-card">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                            <h3>SSO-Authen Admin Panel</h3>
                            <p class="text-muted">Choose your preferred login method</p>
                        </div>
                        
                        <?php if ($errorMessage): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Error:</strong> <?php echo htmlspecialchars($errorMessage); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>
                        
                        <div class="d-grid gap-2">
                            <a href="auth/login.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Login with SSO
                            </a>
                            
                            <button onclick="devLogin()" class="btn btn-secondary btn-lg">
                                <i class="fas fa-user-gear me-2"></i>Login with Dev Mode
                            </button>
                        </div>
                        
                        <div class="text-center mt-4">
                            <hr>
                            <h6>Debug Information:</h6>
                            <small class="text-muted">
                                Current URL: <?php echo $_SERVER['REQUEST_URI']; ?><br>
                                Base Path: <?php echo $basePath; ?><br>
                                Session ID: <?php echo session_id(); ?>
                            </small>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function devLogin() {
            // Show loading
            Swal.fire({
                title: 'Logging in...',
                text: 'Please wait',
                icon: 'info',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch("login.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    action: "dev_login",
                    email: "admin@psu.ac.th",
                    name: "System Administrator"
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Login Successful!',
                        text: 'Redirecting to admin panel',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = "index.php";
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: 'Unable to login',
                        icon: 'error'
                    });
                }
            })
            .catch(error => {
                console.error("Error:", error);
                Swal.fire({
                    title: 'Error',
                    text: 'Unable to connect to server',
                    icon: 'error'
                });
            });
        }
    </script>
</body>
</html>