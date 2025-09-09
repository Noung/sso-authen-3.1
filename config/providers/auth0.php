<?php

/** 
 * sso-authen/config/providers/auth0.php
 * *  
 */

require_once __DIR__ . '/../config.php';

return [
    'clientID'     => 'YOUR_CLIENT_ID_HERE',
    'clientSecret' => 'YOUR_CLIENT_SECRET_HERE',
    'providerURL'  => 'YOUR_PROVIDER_URL_HERE',

    // **สำคัญ:** path ต้องมี /public/ เพิ่มเข้ามาให้ตรงกับโครงสร้างใหม่
    'redirectUri'  => $absoluteRedirectUri . '/public/callback.php',

    'scopes'       => ['openid', 'profile', 'email'],

    // การแปลงชื่อ Claims จาก PSU SSO ให้เป็นชื่อมาตรฐานที่ Library เราเข้าใจ
    'claim_mapping' => [
        'id'        => 'sub',          // 'sub' (Subject) คือ ID เฉพาะตัวของผู้ใช้ ซึ่งเป็นมาตรฐาน OIDC
        'username'  => 'nickname',     // 'nickname' คือชื่อเล่นหรือชื่อผู้ใช้
        'name'      => 'name',         // 'name' คือชื่อเต็ม
        'firstName' => 'given_name',   // 'given_name' คือชื่อจริง
        'lastName'  => 'family_name',  // 'family_name' คือนามสกุล
        'email'     => 'email',
        'picture'   => 'picture',      // URL รูปโปรไฟล์
        'department' => null            // Auth0 ไม่มีข้อมูลแผนกโดยตรง จึงใส่ null
    ]
];
