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
    // 'redirectUri'  => $absoluteRedirectUri . '/public/callback.php',
    'redirectUri'  => 'http://sso-authen.test/public/callback.php',

    'scopes'       => ['openid', 'profile', 'email'],

    // การแปลงชื่อ Claims จาก Microsoft ให้เป็นชื่อมาตรฐานที่ Library เราเข้าใจ
    'claim_mapping' => [
        // Basic Claims (Required)
        'id'           => 'sub',          // 'sub' (Subject) คือ ID เฉพาะตัวของผู้ใช้ ซึ่งเป็นมาตรฐาน OIDC
        'username'     => 'preferred_username',
        'name'         => 'name',         // 'name' คือชื่อเต็ม
        'firstName'    => 'given_name',   // Microsoft อาจมี given_name
        'lastName'     => 'family_name',  // Microsoft อาจมี family_name
        'email'        => 'email',
        'department'   => null,           // Microsoft ไม่มีข้อมูลแผนกใน basic claims
        
        // Extended Claims (Not available from Microsoft by default)
        'position'     => null,
        'campus'       => null,
        'officeName'   => null,
        'facultyId'    => null,
        'departmentId' => null,
        'campusId'     => null,
        'groups'       => null            // Microsoft อาจมี 'groups' ถ้าตั้งค่า Azure AD
    ]
];
