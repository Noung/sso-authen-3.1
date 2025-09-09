<?php
// www/sso_callback.php

// 1. โหลดไฟล์ที่จำเป็นสำหรับแอปพลิเคชัน
require_once __DIR__ . '/vendor/autoload.php'; // Autoloader ของแอปทดสอบ
require_once __DIR__ . '/app_config.php';   // Config ของแอปทดสอบ

// 2. บอกว่าจะใช้ Class อะไรบ้าง
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// 3. เริ่ม Session ของแอปพลิเคชัน
if (!session_id()) {
    session_start();
}

try {
    // 4. ดึง Token จาก URL
    $jwt = $_GET['token'] ?? null;
    if (!$jwt) {
        throw new \Exception('ไม่พบ Token ในการยืนยันตัวตน');
    }

    // 5. ถอดรหัสและตรวจสอบ Token
    // ใช้ Secret Key ที่แชร์ร่วมกันกับ sso-authen
    $decoded = JWT::decode($jwt, new Key(JWT_SHARED_SECRET_KEY, 'HS256'));

    // แปลงข้อมูลผู้ใช้จาก object เป็น array
    $userInfo = (array) $decoded->data;

    // 6. ถ้า Token ถูกต้อง ให้สร้าง Session ของแอปพลิเคชัน
    if ($userInfo) {
        $_SESSION['user_is_logged_in'] = true;
        $_SESSION['user_info'] = $userInfo;

        // 7. ส่งต่อไปยังหน้าแรก
        header('Location: index.php');
        exit;
    } else {
        throw new \Exception('ข้อมูลใน Token ไม่ถูกต้อง');
    }
} catch (\Exception $e) {
    // หาก Token ไม่ถูกต้อง, หมดอายุ, หรือมีปัญหาอื่นๆ
    // ควรแสดงหน้าข้อผิดพลาดที่ชัดเจน
    http_response_code(401); // Unauthorized
    echo "<h1>การยืนยันตัวตนล้มเหลว</h1>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo '<a href="index.php">กลับสู่หน้าหลัก</a>';
    exit;
}
