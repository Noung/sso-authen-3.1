<?php
/**
 * วิธีแก้ปัญหา Legacy Mode แบบข้ามโดเมน
 * แปลง user_handler.php local เป็น HTTP endpoint
 * 
 * วางไฟล์นี้ในโดเมนของแอป legacy (เช่น http://legacy-app.com/api/user_handler.php)
 */

// รับเฉพาะ POST request เท่านั้น
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

// ตรวจสอบ API secret (แนะนำเพื่อความปลอดภัย)
$expectedSecret = 'YOUR_LEGACY_APP_SECRET_KEY'; // ตั้งค่านี้ใน sso-authen client config
$providedSecret = $_SERVER['HTTP_X_API_SECRET'] ?? '';

if ($providedSecret !== $expectedSecret) {
    http_response_code(401);
    exit('Unauthorized');
}

// รับข้อมูล JSON payload
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['normalizedUser'])) {
    http_response_code(400);
    exit('Invalid payload');
}

$normalizedUser = $data['normalizedUser'];
$ssoUserInfo = $data['ssoUserInfo'];

// โหลดโค้ด user handler เดิมที่มีอยู่
require_once __DIR__ . '/user_handler_original.php';

// เรียกใช้ฟังก์ชันเดิม
$internalUser = findOrCreateUser($normalizedUser, $ssoUserInfo);

// ส่งคืนผลลัพธ์แบบ JSON
header('Content-Type: application/json');
echo json_encode($internalUser);
?>