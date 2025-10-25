<?php

/**
 * database/install_simple.php
 * Simple database installation script with manual table creation
 */

// Load admin configuration
$adminConfig = require __DIR__ . '/../admin/config/admin_config.php';

try {
    echo "=== SSO Authentication Database Installer ===\n\n";
    
    // Connect to MySQL server
    $dsn = sprintf(
        'mysql:host=%s;charset=%s',
        $adminConfig['database']['host'],
        $adminConfig['database']['charset']
    );
    
    $pdo = new PDO(
        $dsn,
        $adminConfig['database']['username'],
        $adminConfig['database']['password'],
        $adminConfig['database']['options']
    );
    
    echo "✓ Connected to MySQL server successfully.\n";
    
    // Create database
    $dbName = $adminConfig['database']['dbname'];
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Database '$dbName' created or already exists.\n";
    
    // Switch to database
    $pdo->exec("USE `$dbName`");
    echo "✓ Switched to database '$dbName'.\n";
    
    // Create tables one by one
    createClientsTable($pdo);
    createAdminUsersTable($pdo);
    createAuditLogsTable($pdo);
    
    // Insert initial data
    insertInitialData($pdo);
    
    echo "\n🎉 Database installation completed successfully!\n";
    echo "Admin Panel URL: http://localhost/sso-authen-3/admin/public/\n";
    echo "Default admin login: admin@psu.ac.th (Development mode)\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

function createClientsTable($pdo) {
    echo "Creating 'clients' table...\n";
    
    $sql = "CREATE TABLE IF NOT EXISTS clients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id VARCHAR(255) NOT NULL UNIQUE,
        client_name VARCHAR(255) NOT NULL,
        client_description TEXT,
        app_redirect_uri VARCHAR(500) NOT NULL,
        post_logout_redirect_uri VARCHAR(500) NOT NULL,
        user_handler_endpoint VARCHAR(500) NULL,
        api_secret_key VARCHAR(255) NULL,
        status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_by VARCHAR(255),
        updated_by VARCHAR(255),
        
        INDEX idx_client_id (client_id),
        INDEX idx_status (status),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✓ 'clients' table created successfully.\n";
}

function createAdminUsersTable($pdo) {
    echo "Creating 'admin_users' table...\n";
    
    $sql = "CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        name VARCHAR(255) NOT NULL,
        role ENUM('super_admin', 'admin', 'viewer') DEFAULT 'admin',
        status ENUM('active', 'inactive') DEFAULT 'active',
        last_login_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        INDEX idx_email (email),
        INDEX idx_status (status),
        INDEX idx_role (role)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✓ 'admin_users' table created successfully.\n";
}

function createAuditLogsTable($pdo) {
    echo "Creating 'audit_logs' table...\n";
    
    $sql = "CREATE TABLE IF NOT EXISTS audit_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_email VARCHAR(255) NOT NULL,
        action VARCHAR(100) NOT NULL,
        resource_type VARCHAR(50) NOT NULL,
        resource_id VARCHAR(255) NULL,
        old_values JSON NULL,
        new_values JSON NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        INDEX idx_admin_email (admin_email),
        INDEX idx_action (action),
        INDEX idx_resource_type (resource_type),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✓ 'audit_logs' table created successfully.\n";
}

function insertInitialData($pdo) {
    echo "Inserting initial data...\n";
    
    // Insert default admin user
    $stmt = $pdo->prepare("
        INSERT INTO admin_users (email, name, role, status) VALUES 
        (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP
    ");
    $stmt->execute(['admin@psu.ac.th', 'System Administrator', 'super_admin', 'active']);
    echo "✓ Default admin user inserted.\n";
    
    // Insert sample clients
    $sampleClients = [
        [
            'client_id' => 'my_react_app',
            'client_name' => 'React Application',
            'client_description' => 'Sample React application for testing SSO integration',
            'app_redirect_uri' => 'http://localhost:3000/callback',
            'post_logout_redirect_uri' => 'http://localhost:3000/logout-success',
            'user_handler_endpoint' => 'http://localhost:8080/api/sso-user-handler',
            'api_secret_key' => 'VERY_SECRET_KEY_FOR_REACT_APP',
            'status' => 'active',
            'created_by' => 'installer'
        ],
        [
            'client_id' => 'my_js_app',
            'client_name' => 'JavaScript Application',
            'client_description' => 'Sample JavaScript application using Live Server',
            'app_redirect_uri' => 'http://localhost:5500/public/callback.html',
            'post_logout_redirect_uri' => 'http://localhost:5500/public/index.html',
            'user_handler_endpoint' => 'http://localhost:8080/sso-user-handler',
            'api_secret_key' => 'VERY_SECRET_KEY_FOR_JS_APP',
            'status' => 'active',
            'created_by' => 'installer'
        ],
        [
            'client_id' => 'legacy_php_app',
            'client_name' => 'Legacy PHP Application',
            'client_description' => 'Legacy PHP application using JWT-based authentication',
            'app_redirect_uri' => 'http://my-php-app.test/sso_callback.php',
            'post_logout_redirect_uri' => 'http://my-php-app.test/',
            'user_handler_endpoint' => 'http://my-php-app.test/api/user_handler.php',
            'api_secret_key' => 'ANOTHER_SECRET_KEY_FOR_PHP_APP',
            'status' => 'active',
            'created_by' => 'installer'
        ],
        [
            'client_id' => 'very_old_php_app',
            'client_name' => 'Very Old PHP Application',
            'client_description' => 'Legacy PHP application using session-based authentication (V.2 mode)',
            'app_redirect_uri' => 'http://old-app.test/index.php',
            'post_logout_redirect_uri' => 'http://old-app.test/',
            'user_handler_endpoint' => null,
            'api_secret_key' => null,
            'status' => 'active',
            'created_by' => 'installer'
        ]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO clients (
            client_id, client_name, client_description,
            app_redirect_uri, post_logout_redirect_uri,
            user_handler_endpoint, api_secret_key,
            status, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            client_name = VALUES(client_name),
            client_description = VALUES(client_description),
            app_redirect_uri = VALUES(app_redirect_uri),
            post_logout_redirect_uri = VALUES(post_logout_redirect_uri),
            user_handler_endpoint = VALUES(user_handler_endpoint),
            api_secret_key = VALUES(api_secret_key),
            updated_at = CURRENT_TIMESTAMP
    ");
    
    foreach ($sampleClients as $client) {
        $stmt->execute([
            $client['client_id'],
            $client['client_name'],
            $client['client_description'],
            $client['app_redirect_uri'],
            $client['post_logout_redirect_uri'],
            $client['user_handler_endpoint'],
            $client['api_secret_key'],
            $client['status'],
            $client['created_by']
        ]);
    }
    
    echo "✓ Sample clients inserted successfully.\n";
}