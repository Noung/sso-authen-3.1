<?php
// www/index.php
if (!session_id()) {
    session_start();
}

// --- (V.3) การตั้งค่าสำหรับแอปพลิเคชันทดสอบนี้ ---
// ค่าเหล่านี้ควรจะตรงกับที่ลงทะเบียนไว้ใน config.php ของ sso-authen
$my_client_id = 'legacy_php_app'; // หรือ client_id อื่นๆ ที่คุณตั้งไว้
$my_app_base_uri = 'http://my-php-app.test'; // URL หลักของเว็บทดสอบนี้

// URL ของ sso-authen ที่เราติดตั้งไว้เป็นบริการกลาง
$sso_server_url = 'http://auth.my-organization.com/public';

// สร้าง URL สำหรับ Login/Logout ตามกติกาใหม่
$login_url = "{$sso_server_url}/login.php?client_id={$my_client_id}&redirect_uri=" . urlencode("{$my_app_base_uri}/sso_callback.php");
$logout_url = "{$sso_server_url}/logout.php?post_logout_redirect_uri=" . urlencode("{$my_app_base_uri}/index.php");

?>
<!DOCTYPE html>
<html lang="th">

<head>
    <title>หน้าแรก</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: "Prompt", sans-serif;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                ระบบทดสอบ SSO Authen (PHP Client App)
            </div>
            <div class="card-body text-center">
                <?php if (isset($_SESSION['user_is_logged_in']) && $_SESSION['user_is_logged_in']): ?>
                    <h5 class="card-title" style="color:green">ยินดีต้อนรับ, <?php echo htmlspecialchars($_SESSION['user_info']['name']); ?>!</h5>
                    <p class="card-text">คุณได้เข้าสู่ระบบเรียบร้อยแล้ว (Role: <?php echo htmlspecialchars($_SESSION['user_info']['role']); ?>)</p>
                    <div class="text-center">
                        <a href="userinfo.php" class="btn btn-info">หน้าสมาชิก</a>
                        <a href="<?php echo htmlspecialchars($logout_url); ?>" class="btn btn-danger">Sign out</a>
                    </div>
                <?php else: ?>
                    <h5 class="card-title" style="color:red">คุณยังไม่ได้เข้าสู่ระบบ</h5>
                    <p class="card-text">กรุณาเข้าสู่ระบบเพื่อใช้งาน</p>
                    <div class="text-center">
                        <a href="<?php echo htmlspecialchars($login_url); ?>" class="btn btn-primary">Login with SSO Provider</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>
