<?php
// www/protected_page.php หน้าที่ต้องการป้องกันถ้ายังไม่เข้าสู่ระบบ

if (!session_id()) {
    session_start();
}

// ตรวจสอบ Session
if (!isset($_SESSION['user_is_logged_in']) || !$_SESSION['user_is_logged_in']) {
    header("Location: sso-authen/public/login.php");
    exit;
}

// ดึงข้อมูลผู้ใช้จาก Session มาใช้งาน
$currentUser = $_SESSION['user_info'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <title>หน้าสำหรับสมาชิก</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />

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
                หน้าสำหรับสมาชิกเท่านั้น
            </div>
            <div class="card-body">
                <h5 class="card-title">ข้อมูลส่วนตัว</h5>
                <ul>
                    <li><strong>ID:</strong> <?php echo htmlspecialchars($currentUser['id']); ?></li>
                    <li><strong>Name:</strong> <?php echo htmlspecialchars($currentUser['name']); ?></li>
                    <li><strong>Email:</strong> <?php echo htmlspecialchars($currentUser['email']); ?></li>
                    <li><strong>Role:</strong> <?php echo htmlspecialchars($currentUser['role']); ?></li>
                </ul>
                <div class="text-center">
                    <a href="index.php" class="btn btn-secondary">กลับสู่หน้าแรก</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>