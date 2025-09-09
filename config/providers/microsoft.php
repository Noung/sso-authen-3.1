<?php

/** 
 * sso-authen/config/providers/microsoft.php
 * *  
 */

require_once __DIR__ . '/../config.php';

return [
    'clientID'     => 'YOUR_CLIENT_ID_HERE',
    'clientSecret' => 'YOUR_CLIENT_SECRET_HERE',
    'providerURL'  => 'https://login.microsoftonline.com/common/v2.0', // นี่คือ Issuer URL ของ Microsoft

    // **สำคัญ:** path ต้องมี /public/ เพิ่มเข้ามาให้ตรงกับโครงสร้างใหม่
    'redirectUri'  => $absoluteRedirectUri . '/public/callback.php',

    'scopes'       => ['openid', 'profile', 'email'],

    // การแปลงชื่อ Claims จาก PSU SSO ให้เป็นชื่อมาตรฐานที่ Library เราเข้าใจ
    'claim_mapping' => [
        'id'        => 'sub',          // 'sub' (Subject) คือ ID เฉพาะตัวของผู้ใช้ ซึ่งเป็นมาตรฐาน OIDC
        'username'  => 'preferred_username',     // 'nickname' คือชื่อเล่นหรือชื่อผู้ใช้
        'name'      => 'name',         // 'name' คือชื่อเต็ม
        'firstName' =>  null,   // 'given_name' คือชื่อจริง
        'lastName'  =>  null,  // 'family_name' คือนามสกุล
        'email'     => 'email',      // URL รูปโปรไฟล์
        'department' => null            // Auth0 ไม่มีข้อมูลแผนกโดยตรง จึงใส่ null
    ]
];
