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
    // 'redirectUri'  => 'http://sso-authen.test/public/callback.php',
    'redirectUri'  => $absoluteRedirectUri . '/public/callback.php',

    'scopes'       => ['openid', 'profile', 'email', 'psu_profile'],

    // การแปลงชื่อ Claims จาก PSU SSO ให้เป็นชื่อมาตรฐานที่ Library เราเข้าใจ
    'claim_mapping' => [
        'id'        => 'psu_id',
        'username'  => 'preferred_username',
        'name'      => 'display_name_th',
        'firstName' => 'first_name_th',
        'lastName'  => 'last_name_th',
        'email'     => 'email',
        'department' => 'department_th'
    ]
];
