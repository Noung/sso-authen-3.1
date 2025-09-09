<?php

/**
 * public/logout.php
 * จัดการการออกจากระบบ และส่งผู้ใช้กลับไปยังแอปต้นทาง
 * (เวอร์ชั่น 3 - Multi-Client Support)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/helpers.php';

use SsoAuthen\SsoHandler;

try {
    // 1. รับ URL ปลายทางจาก Query String
    $postLogoutRedirectUri = $_GET['post_logout_redirect_uri'] ?? null;

    if (!$postLogoutRedirectUri) {
        throw new Exception('ไม่ได้ระบุ URL ปลายทางหลังจากการออกจากระบบ (post_logout_redirect_uri is required)');
    }

    // 2. ตรวจสอบความปลอดภัย: สร้าง Whitelist ของ URL ที่ได้รับอนุญาต
    $allowedUris = [];
    foreach ($authorized_clients as $client) {
        // **ถ้ามีการกำหนด post_logout_redirect_uri ไว้ ให้ใช้ค่านั้น**
        if (!empty($client['post_logout_redirect_uri'])) {
            $allowedUris[] = $client['post_logout_redirect_uri'];
        }
        // **และยังคงอนุญาตให้ใช้ app_redirect_uri ได้ด้วย (เป็นทางเลือก)**
        $allowedUris[] = $client['app_redirect_uri'];
    }

    // ตรวจสอบว่า URL ที่ส่งมา อยู่ใน Whitelist หรือไม่
    if (!in_array($postLogoutRedirectUri, array_unique($allowedUris))) { // ใช้ array_unique กันซ้ำซ้อน
        throw new Exception('URL ปลายทางที่ระบุมาไม่ได้รับอนุญาต');
    }

    // 3. เรียกใช้ Static Method เพื่อทำลาย Session ของ SSO กลาง
    SsoHandler::logout();

    // 4. ส่งผู้ใช้กลับไปยัง URL ที่ตรวจสอบแล้วว่าปลอดภัย
    render_alert_and_redirect(
        'ออกจากระบบสำเร็จ',
        'คุณได้ออกจากระบบเรียบร้อยแล้ว',
        'success',
        $postLogoutRedirectUri
    );
    exit;
} catch (Exception $e) {
    // หากเกิดข้อผิดพลาด ให้แสดงข้อความ แต่ไม่ redirect ไปที่ URL ที่ไม่น่าไว้ใจ
    // การส่งกลับไปหน้าแรกของ sso-authen เองเป็นทางที่ปลอดภัย
    render_alert_and_redirect(
        'เกิดข้อผิดพลาด',
        $e->getMessage(),
        'error',
        '/' // กลับไปหน้าแรกของ sso-authen
    );
}
