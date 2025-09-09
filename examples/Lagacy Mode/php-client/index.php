<?php
// www/index.php
if (!session_id()) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <title>หน้าแรก</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

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
                ระบบทดสอบ SSO Authen Library
            </div>
            <div class="card-body text-center">
                <?php if (isset($_SESSION['user_is_logged_in']) && $_SESSION['user_is_logged_in']): ?>
                    <h5 class="card-title" style="color:green">ยินดีต้อนรับ, <?php echo htmlspecialchars($_SESSION['user_info']['name']); ?>!</h5>
                    <p class="card-text">คุณได้เข้าสู่ระบบเรียบร้อยแล้ว (Role: <?php echo htmlspecialchars($_SESSION['user_info']['role']); ?>)</p>
                    <div class="text-center">
                        <a href="userinfo.php" class="btn btn-info">หน้าสมาชิก</a>
                        <a href="sso-authen/public/logout.php" class="btn btn-danger">ออกจากระบบ</a>
                    </div>
                <?php else: ?>
                    <h5 class="card-title" style="color:red">คุณยังไม่ได้เข้าสู่ระบบ</h5>
                    <p class="card-text">กรุณาเข้าสู่ระบบเพื่อใช้งาน</p>
                    <div class="text-center">
                        <a href="sso-authen/public/login.php" class="btn btn-primary">Login with SSO Provider</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>