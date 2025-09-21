<?php

/** * public/callback.php
 * หน้าสำหรับรับข้อมูลกลับจาก SSO Provider, สร้าง JWT, และส่งกลับไปให้ Web App
 * (เวอร์ชั่น 3 - Multi-Client Support)
 */

// 1. โหลดไฟล์ที่จำเป็น
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/helpers.php';

// 2. บอก PHP ว่าเราจะใช้ Class อะไรบ้าง
use SsoAuthen\SsoHandler;
use Firebase\JWT\JWT;

// เริ่ม session (สำคัญมากสำหรับ V.3)
if (!session_id()) {
    session_start();
}

try {
    // 3. ตรวจสอบและดึงข้อมูล Client จาก Session
    $clientId = $_SESSION['current_client_id'] ?? null;
    if (!$clientId) {
        throw new \Exception('ไม่สามารถระบุแอปพลิเคชันต้นทางได้ (Client ID not found in session)');
    }

    $clientConfig = $authorized_clients[$clientId] ?? null;
    if (!$clientConfig) {
        throw new \Exception("แอปพลิเคชันนี้ไม่ได้รับอนุญาต (Unauthorized client ID: " . htmlspecialchars($clientId) . ")");
    }

    // 4. **สำคัญ:** สร้างค่าคงที่แบบไดนามิกเพื่อให้ SsoHandler ทำงานต่อได้ถูกต้อง
    // สำหรับการเรียก API ไปยัง Web App ที่ถูกต้อง
    define('USER_HANDLER_ENDPOINT', $clientConfig['user_handler_endpoint']);
    define('API_SECRET_KEY', $clientConfig['api_secret_key']);

    // 5. สร้าง Instance ของ SsoHandler และจัดการ Callback
    $handler = new SsoHandler($providerConfig);
    $internalUser = $handler->handleCallback($clientConfig);

    if (!$internalUser || !isset($internalUser['id'])) {
        throw new \Exception("ไม่ได้รับข้อมูลผู้ใช้จาก Web App API หรือข้อมูลไม่ถูกต้อง");
    }

    // 6. สร้าง JWT
    $issuedAt   = time();
    $expire     = $issuedAt + JWT_EXPIRATION;
    $payload = [
        'iss'  => "sso-authen-service",
        'iat'  => $issuedAt,
        'exp'  => $expire,
        'data' => $internalUser
    ];
    $jwt = JWT::encode($payload, JWT_SECRET_KEY, 'HS256');

    // 7. Redirect กลับไปที่ Web App ที่ถูกต้อง พร้อมแนบ Token
    $redirectUrl = $clientConfig['app_redirect_uri'] . '?token=' . $jwt;

    // ล้างค่า session ที่ไม่ใช้แล้ว
    unset($_SESSION['current_client_id']);

    header("Location: " . $redirectUrl);
    exit;
} catch (Exception $e) {
    // หากเกิดข้อผิดพลาด ให้ส่งกลับไปที่หน้าที่ผู้ใช้เริ่มต้นกด Login
    // หรือถ้าไม่มี ก็ส่งไปหน้าหลักของ sso-authen เอง
    $redirect_url_on_error = $_SESSION['login_start_uri'] ?? '/';

    render_alert_and_redirect(
        'เกิดข้อผิดพลาดในการยืนยันตัวตน',
        $e->getMessage(),
        'error',
        $redirect_url_on_error
    );
}
