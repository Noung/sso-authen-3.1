<?php

/**
 * api/user-handler.php
 * ตัวอย่าง API Endpoint สำหรับจัดการข้อมูลผู้ใช้ฝั่ง Web Application v.2, v.3
 */

// --- การตั้งค่าที่ต้องทำใน Web Application ของคุณ ---
// 1. Secret Key ที่ตรงกับใน config ของ sso-authen
// สร้าง Key แบบสุ่มความยาว 64 ตัวอักษร
// $secure_key = bin2hex(random_bytes(32));

define('APP_API_SECRET_KEY', 'YOUR_STRONG_SECRET_KEY'); // <-- **สำคัญ:** ต้องตรงกัน

// 2. ข้อมูลเชื่อมต่อฐานข้อมูลของ Web Application
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_app_db');
define('DB_USER', 'your_app_user');
define('DB_PASS', 'your_app_password');
define('DB_CHARSET', 'utf8mb4');
// --- สิ้นสุดการตั้งค่า ---


// --- เริ่มต้นส่วนของ Logic ---
header('Content-Type: application/json');

// 1. ตรวจสอบความปลอดภัยของ Request
$apiKey = $_SERVER['HTTP_X_API_SECRET'] ?? '';
if ($apiKey !== APP_API_SECRET_KEY) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Invalid API Key']);
    exit;
}

// 2. รับข้อมูล JSON จาก Request Body
$json_payload = file_get_contents('php://input');
$data = json_decode($json_payload, true);

// =======================================================
// == จุดที่ใช้ Log ข้อมูล JSON ที่ได้รับมาทั้งหมด ==
// =======================================================
// วิธีที่ 1: Log ไปยัง error log ของ Server (วิธีที่ง่ายและเร็วที่สุด)
// error_log("--- Received SSO Data Payload ---");
// error_log(print_r($data, true)); // print_r(..., true) จะแปลง array เป็น string

// วิธีที่ 2: Log ลงไฟล์ที่คุณกำหนดเอง
// $log_file = __DIR__ . '/sso_requests.log';
// $log_message = "[" . date('Y-m-d H:i:s') . "] " . $json_payload . "\n";
// file_put_contents($log_file, $log_message, FILE_APPEND);

// วิธีที่ 3: การดีบักแบบเร็ว
// echo '<pre>';
// var_dump($data);
// echo '</pre>';
// exit(); // หยุดการทำงานทันที
// =======================================================

if (!$data || !isset($data['normalizedUser']) || !isset($data['ssoUserInfo'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Invalid JSON payload']);
    exit;
}

$normalizedUser = $data['normalizedUser'];
$ssoUserInfo = (object) $data['ssoUserInfo'];

// 3. เรียกใช้ฟังก์ชันจัดการข้อมูลผู้ใช้ (โค้ดส่วนนี้เหมือนกับใน user_handler.php เดิม)
try {
    $internalUser = findOrCreateUserInApp($normalizedUser, $ssoUserInfo);
    http_response_code(200);
    echo json_encode($internalUser);
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => $e->getMessage()]);
}


/**
 * ค้นหาหรือสร้างผู้ใช้ในฐานข้อมูลของแอปพลิเคชัน
 * (ฟังก์ชันนี้เหมือนกับ findOrCreateUser เดิม แต่เปลี่ยนชื่อเพื่อความชัดเจน)
 *
 * @param array $normalizedUser
 * @param object $ssoUserInfo
 * @return array
 * @throws \PDOException
 */
function findOrCreateUserInApp(array $normalizedUser, object $ssoUserInfo): array
{
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$normalizedUser['email']]);
        $user = $stmt->fetch();

        if ($user) {
            // พบผู้ใช้: อัปเดตข้อมูล
            $updateStmt = $pdo->prepare("UPDATE users SET name = ?, user_id = ? WHERE id = ?");
            $updateStmt->execute([$normalizedUser['name'], $normalizedUser['id'], $user['id']]);
            return $user;
        } else {
            // ไม่พบผู้ใช้: สร้างใหม่
            $defaultRole = 'user';
            $insertStmt = $pdo->prepare("INSERT INTO users (user_id, email, name, role) VALUES (?, ?, ?, ?)");
            $insertStmt->execute([$normalizedUser['id'], $normalizedUser['email'], $normalizedUser['name'], $defaultRole]);
            $newUserId = $pdo->lastInsertId();

            return [
                // บังคับ return คีย์เหล่านี้เพื่อให้สอดคล้องกับโครงสร้างที่ SsoHandler คาดหวัง
                'id' => $newUserId,
                'user_id' => $normalizedUser['id'],
                'email' => $normalizedUser['email'],
                'name' => $normalizedUser['name'],
                'role' => $defaultRole // คีย์ role เพื่อนำไปกำหนดเส้นทางหลังจาก login สำเร็จ
            ];
        }
    } catch (\PDOException $e) {
        throw new \PDOException($e->getMessage(), (int)$e->getCode());
    }
}
