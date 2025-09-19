<?php

/**
 * sso-authen/config/config.php
 * * ไฟล์ตั้งค่าหลัก (Main Configuration File) ทำหน้าที่โหลดการตั้งค่าทั้งหมดที่จำเป็นสำหรับ SSO Handler
 */

// 1. โหลด Autoloader ของ Composer
// ทำให้เราสามารถเรียกใช้ Class จาก Library ทั้งหมดได้โดยอัตโนมัติ
require_once __DIR__ . '/../vendor/autoload.php';

// 2. เริ่มการทำงานของ Session มาตรฐาน PHP
// จำเป็นสำหรับเก็บค่า state และ client_id ชั่วคราวระหว่างการ Redirect
if (!session_id()) {
    session_start();
}

/**
 * ----------------------------------------------------------------------
 * การตั้งค่า OIDC Provider (ผู้ให้บริการยืนยันตัวตน)
 * ----------------------------------------------------------------------
 */
// 3. เลือกว่าจะใช้ Provider (มหาวิทยาลัย) ไหน
$activeProvider = 'psu'; // ตัวอย่าง: 'psu', 'cmu', 'ku', 'google', 'auth0', 'okta', 'custom_oidc' เป็นต้น

// 4. โหลดไฟล์ตั้งค่าของ Provider ที่เลือก
$providerConfigFile = __DIR__ . '/providers/' . $activeProvider . '.php';
if (!file_exists($providerConfigFile)) {
    die("Error: Configuration file for provider '{$activeProvider}' not found.");
}
// $providerConfig จะถูกใช้ใน SsoHandler
$providerConfig = require_once $providerConfigFile;

/**
 * ----------------------------------------------------------------------
 * ✨ (V.3) การตั้งค่าแอปพลิเคชันที่ได้รับอนุญาต (Authorized Clients)
 * ----------------------------------------------------------------------
 * นี่คือส่วนที่ใช้ลงทะเบียนเว็บแอปพลิเคชันต่างๆ ที่จะมาเชื่อมต่อกับ SSO กลางแห่งนี้
 * 'key' ของ array คือ 'client_id' ที่แต่ละแอปต้องส่งมาเพื่อแนะนำตัวเอง
 */
$authorized_clients = [
    // ตัวอย่างสำหรับ React/JS App
    'my_react_app' => [
        // URL ที่จะให้ Redirect กลับไปหลัง Login สำเร็จ (ต้องตรงกับที่แอปส่งมา)
        'app_redirect_uri'      => 'http://localhost:3000/callback',
        // URL ปลายทางหลังจาก Logout
        'post_logout_redirect_uri' => 'http://localhost:3000/logout-success',
        // API Endpoint ของแอปนั้นๆ สำหรับจัดการข้อมูลผู้ใช้
        'user_handler_endpoint' => 'http://localhost:8080/api/sso-user-handler',
        // Secret Key สำหรับคุยกับ API ของแอปนั้นๆ
        'api_secret_key'        => 'VERY_SECRET_KEY_FOR_REACT_APP'
    ],

    // ตัวอย่างสำหรับ JavaScript App (ที่ใช้ Live Server)
    'my_js_app' => [
        'app_redirect_uri'      => 'http://localhost:5500/public/callback.html', // Port อาจต่างไป
        'post_logout_redirect_uri' => 'http://localhost:5500/public/index.html',
        'user_handler_endpoint' => 'http://localhost:8080/sso-user-handler',
        'api_secret_key'        => 'VERY_SECRET_KEY_FOR_JS_APP'
    ],

    // ตัวอย่างสำหรับ Legacy PHP App (ที่ต้องการใช้ JWT)
    'legacy_php_app' => [
        'app_redirect_uri'      => 'http://my-php-app.test/sso_callback.php',
        'post_logout_redirect_uri' => 'http://my-php-app.test/',
        'user_handler_endpoint' => 'http://my-php-app.test/api/user_handler.php',
        'api_secret_key'        => 'ANOTHER_SECRET_KEY_FOR_PHP_APP'
    ],

    // ตัวอย่างสำหรับ Legacy PHP App (ที่ยังใช้ Session แบบ V.2)
    // สำหรับโหมดนี้ user_handler_endpoint จะเป็น null
    'very_old_php_app' => [
        'app_redirect_uri'      => 'http://old-app.test/index.php', // Redirect กลับไปหน้าแรก
        'post_logout_redirect_uri' => 'http://old-app.test/',
        'user_handler_endpoint' => null, // ตั้งเป็น null เพื่อให้ SsoHandler ใช้ user_handler.php
        'api_secret_key'        => null // ไม่ได้ใช้ API จึงเป็น null
    ]

    // ... สามารถเพิ่มแอปพลิเคชันอื่นๆ ต่อท้ายที่นี่ได้ ...
];


/**
 * ----------------------------------------------------------------------
 * การตั้งค่า JWT (JSON Web Token)
 * ----------------------------------------------------------------------
 * การตั้งค่าเหล่านี้จะใช้ร่วมกันสำหรับทุกแอปพลิเคชันที่เชื่อมต่อ
 */
// 5. ตั้งค่า Secret Key สำหรับสร้าง JWT (ควรเป็นค่าสุ่มยาวๆ และเก็บเป็นความลับ)
define('JWT_SECRET_KEY', 'YOUR_SUPER_SECRET_KEY_FOR_JWT_GENERATION_THAT_IS_VERY_LONG');

// 6. ตั้งค่าอายุของ Token (เช่น 1 ชั่วโมง = 3600 วินาที)
define('JWT_EXPIRATION', 3600);
