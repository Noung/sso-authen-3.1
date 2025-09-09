<?php

/** * public/login.php
 * ประตูบานแรกสำหรับเริ่มกระบวนการ Login
 * (เวอร์ชั่น 3 - Multi-Client Support)
 */

// 1. โหลดไฟล์ที่จำเป็น
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/helpers.php';

// 2. บอก PHP ว่าเราจะใช้ Class SsoHandler
use SsoAuthen\SsoHandler;

try {
    // 3. รับพารามิเตอร์จาก URL เพื่อระบุ Client ที่เรียกใช้
    $clientId = $_GET['client_id'] ?? null;
    $redirectUri = $_GET['redirect_uri'] ?? null;

    if (!$clientId || !$redirectUri) {
        throw new Exception('ข้อมูลสำหรับระบุแอปพลิเคชันไม่ครบถ้วน (client_id and redirect_uri are required)');
    }

    // 4. ตรวจสอบว่า Client นี้ได้รับอนุญาตใน config หรือไม่
    $clientConfig = $authorized_clients[$clientId] ?? null;
    if (!$clientConfig) {
        throw new Exception("แอปพลิเคชันนี้ไม่ได้รับอนุญาต (Unauthorized client ID: " . htmlspecialchars($clientId) . ")");
    }

    // 5. ตรวจสอบความปลอดภัย: Redirect URI ที่ส่งมาต้องตรงกับที่ลงทะเบียนไว้เป๊ะๆ
    if ($redirectUri !== $clientConfig['app_redirect_uri']) {
        throw new Exception('Redirect URI ไม่ตรงกับที่ลงทะเบียนไว้ในระบบ');
    }

    // 6. หากทุกอย่างถูกต้อง ให้บันทึกข้อมูล Client ลง Session
    // เพื่อให้ callback.php รู้ว่าต้องทำงานกับแอปพลิเคชันไหน
    $_SESSION['current_client_id'] = $clientId;
    $_SESSION['login_start_uri'] = $redirectUri; // บันทึกไว้เผื่อใช้ในหน้า error

    // 7. สร้าง SsoHandler และเริ่มกระบวนการยืนยันตัวตน
    $handler = new SsoHandler($providerConfig);
    $handler->login();
} catch (Exception $e) {
    // หากเกิด Error จะใช้ SweetAlert แสดงข้อความ
    // และจะพยายามส่งผู้ใช้กลับไปที่หน้าที่เขาเพิ่งจากมา
    $redirect_url_on_error = $_GET['redirect_uri'] ?? '/';

    render_alert_and_redirect(
        'เกิดข้อผิดพลาดในการตั้งค่า',
        $e->getMessage(),
        'error',
        $redirect_url_on_error
    );
}
