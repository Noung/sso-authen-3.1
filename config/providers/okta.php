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
    // 'redirectUri'  => $absoluteRedirectUri . '/public/callback.php',
    'redirectUri'  => 'http://sso-authen.test/public/callback.php',

    'scopes'       => ['openid', 'profile', 'email'],

    // การแปลงชื่อ Claims จาก Okta ให้เป็นชื่อมาตรฐานที่ Library เราเข้าใจ
    // Okta รองรับ Basic Claims (7 ฟิลด์) + groups, Extended Claims อื่นๆ จะเป็น null
    // Extended Claims เป็นข้อมูลเฉพาะของ PSU SSO เท่านั้น
    'claim_mapping' => [
        // Basic Claims (Required)
        'id'           => 'sub',          // 'sub' (Subject) คือ ID เฉพาะตัวของผู้ใช้ ซึ่งเป็นมาตรฐาน OIDC
        'username'     => 'preferred_username', // preferred_username ชื่อเล่นหรือชื่อผู้ใช้
        'name'         => 'name',         // 'name' คือชื่อเต็ม
        'firstName'    => 'given_name',   // 'given_name' คือชื่อจริง
        'lastName'     => 'family_name',  // 'family_name' คือนามสกุล
        'email'        => 'email',
        'department'   => null,           // Okta ไม่มีข้อมูลแผนกใน basic claims
        
        // Extended Claims (Not available from Okta by default)
        'position'     => null,
        'campus'       => null,
        'officeName'   => null,
        'facultyId'    => null,
        'departmentId' => null,
        'campusId'     => null,
        'groups'       => 'groups'        // Okta อาจมี 'groups' claim
    ]
];

