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

// Handle login POST request
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
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSO-Authen Admin Panel - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow mt-5">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                            <h3>SSO-Authen Admin Panel</h3>
                            <p class="text-muted">เข้าสู่ระบบด้วย SSO</p>
                        </div>
                        
                        <div class="d-grid">
                            <button onclick="devLogin()" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>เข้าสู่ระบบ
                            </button>
                        </div>
                        
                        <div class="text-center mt-3">
                            <small class="text-muted">Development Mode - Click to login as admin</small>
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
                
                <div class="text-center mt-3">
                    <a href="<?php echo $basePath; ?>/simple_admin.php" class="btn btn-secondary">
                        <i class="fas fa-cog me-1"></i>Simple Admin (Backup)
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function devLogin() {
            // Show loading
            Swal.fire({
                title: 'กำลังเข้าสู่ระบบ...',
                text: 'กรุณารอสักครู่',
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
                        title: 'เข้าสู่ระบบสำเร็จ!',
                        text: 'กำลังนำท่านเข้าสู่ระบบจัดการ',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = "index.php";
                    });
                } else {
                    Swal.fire({
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถเข้าสู่ระบบได้',
                        icon: 'error'
                    });
                }
            })
            .catch(error => {
                console.error("Error:", error);
                Swal.fire({
                    title: 'เกิดข้อผิดพลาด',
                    text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้',
                    icon: 'error'
                });
            });
        }
    </script>
</body>
</html>