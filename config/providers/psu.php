<?php

/** 
 * sso-authen/config/providers/psu.php
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

    'scopes'       => ['openid', 'profile', 'email', 'psu_profile'],

    // การแปลงชื่อ Claims จาก PSU SSO ให้เป็นชื่อมาตรฐานที่ Library เราเข้าใจ
    // PSU SSO รองรับ Extended Claims ครบ 14 ฟิลด์ (7 basic + 7 extended)
    // Extended Claims ช่วยให้ทำ Authorization และ Personalization ได้ละเอียดยิ่งขึ้น
    'claim_mapping' => [
        // Basic Claims (Required)
        'id'           => 'psu_id',
        'username'     => 'preferred_username',
        'name'         => 'display_name_th',
        'firstName'    => 'first_name_th',
        'lastName'     => 'last_name_th',
        'email'        => 'email',
        'department'   => 'department_th',
        
        // Extended Claims (PSU-specific)
        'position'     => 'position_th',
        'campus'       => 'campus_th',
        'officeName'   => 'office_name_th',
        'facultyId'    => 'faculty_id',
        'departmentId' => 'department_id',
        'campusId'     => 'campus_id',
        'groups'       => 'groups'
    ]
];
