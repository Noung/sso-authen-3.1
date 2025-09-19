<?php
// www/userinfo.php (หรือ protected_page.php)
// หน้านี้ทำหน้าที่เป็นตัวอย่างของหน้าที่ต้องการการยืนยันตัวตน

// 1. โหลดการตั้งค่าของแอปพลิเคชัน (เพื่อให้รู้ว่าจะ Redirect ไปที่ไหน)
// require_once __DIR__ . '/index.php'; // เราสามารถ re-use ตัวแปรจาก index.php ได้

if (!session_id()) {
    session_start();
}

// 2. ตรวจสอบ Session ของแอปพลิเคชัน
// ตรรกะนี้ยังคงเหมือนเดิมทุกประการ
if (!isset($_SESSION['user_is_logged_in']) || !$_SESSION['user_is_logged_in']) {

    // --- 👇 ส่วนที่ปรับแก้ ---
    // เปลี่ยนจาก Redirect ไปที่ sso-authen โดยตรง
    // เป็นการส่งกลับไปที่หน้าแรกของแอปเราเอง ซึ่งมีปุ่ม Login ที่ถูกต้องอยู่แล้ว
    header("Location: index.php");
    // --- สิ้นสุดส่วนที่ปรับแก้ ---

    exit;
}

// 3. ดึงข้อมูลผู้ใช้จาก Session มาใช้งาน (เหมือนเดิม)
$currentUser = $_SESSION['user_info'];
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <title>หน้าสำหรับสมาชิก</title>
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
                หน้าสำหรับสมาชิกเท่านั้น
            </div>
            <div class="card-body">
                <h5 class="card-title">ข้อมูลส่วนตัว (ดึงมาจาก Session)</h5>
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
