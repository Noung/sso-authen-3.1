<?php
/**
 * Admin Panel Configuration
 * Compatible with PHP 7.4.33
 */

return [
    'app' => [
        'name' => 'SSO Admin Panel',
        'version' => '1.0.0',
        'environment' => 'development', // development, production
        'debug' => true,
        'timezone' => 'Asia/Bangkok',
    ],
    
    'database' => [
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'sso_authen',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    ],
    
    'auth' => [
        'oidc' => [
            'provider_url' => 'http://localhost:8080/sso-authen-3/public/',
            'client_id' => 'admin-panel',
            'client_secret' => 'admin-panel-secret-key',
            'redirect_uri' => 'http://localhost:8080/sso-authen-3/admin/public/auth/callback',
            'scopes' => ['openid', 'profile', 'email'],
        ],
        'development' => [
            'enabled' => true,
            'admin_email' => 'admin@psu.ac.th',
            'admin_name' => 'System Administrator',
        ],
    ],
    
    'session' => [
        'name' => 'SSO_ADMIN_SESSION',
        'lifetime' => 7200, // 2 hours
        'secure' => false, // Set to true in production with HTTPS
        'httponly' => true,
    ],
    
    'pagination' => [
        'default_per_page' => 10,
        'max_per_page' => 100,
    ],
    
    'logging' => [
        'enabled' => true,
        'level' => 'debug', // debug, info, warning, error
        'file' => __DIR__ . '/../logs/admin.log',
    ],
];