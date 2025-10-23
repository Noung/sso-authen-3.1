<?php

/** 
 * sso-authen/config/providers/google.php
 * *  
 */

require_once __DIR__ . '/../config.php';

return [
    'clientID'     => 'YOUR_CLIENT_ID_HERE',
    'clientSecret' => 'YOUR_CLIENT_SECRET_HERE',
    'providerURL'  => 'https://accounts.google.com', // นี่คือ Issuer URL ของ Google

    // **สำคัญ:** path ต้องมี /public/ เพิ่มเข้ามาให้ตรงกับโครงสร้างใหม่
    // 'redirectUri'  => $absoluteRedirectUri . '/public/callback.php',
    'redirectUri'  => 'http://sso-authen.test/public/callback.php',

    'scopes'       => ['openid', 'profile', 'email'], // Scopes มาตรฐานของ Google

    // การแปลงชื่อ Claims จาก Google ให้เป็นชื่อมาตรฐานที่ Library เราเข้าใจ
    // Google รองรับเฉพาะ Basic Claims (7 ฟิลด์) Extended Claims จะเป็น null
    // Extended Claims เป็นข้อมูลเฉพาะของ PSU SSO เท่านั้น
    'claim_mapping' => [
        // Basic Claims (Required)
        'id'           => 'sub',        // Google ใช้ 'sub' เป็น User ID
        'username'     => 'email',      // ใช้ email แทน username
        'name'         => 'name',
        'firstName'    => 'given_name',
        'lastName'     => 'family_name',
        'email'        => 'email',
        'department'   => null,         // Google ไม่มีข้อมูลแผนก
        
        // Extended Claims (Not available from Google)
        'position'     => null,
        'campus'       => null,
        'officeName'   => null,
        'facultyId'    => null,
        'departmentId' => null,
        'campusId'     => null,
        'groups'       => null
    ]
];
