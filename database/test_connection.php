<?php

/**
 * database/test_connection.php
 * Simple database connection test
 */

echo "=== Database Connection Test ===\n\n";

try {
    // Database configuration
    $host = 'localhost';
    $dbname = 'sso_authen';
    $username = 'root';
    $password = '';
    
    echo "Connecting to MySQL server...\n";
    
    // Connect without database first
    $dsn = "mysql:host=$host;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "✓ Connected to MySQL server successfully.\n";
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Database '$dbname' created or verified.\n";
    
    // Switch to database
    $pdo->exec("USE `$dbname`");
    echo "✓ Using database '$dbname'.\n";
    
    // Create clients table
    echo "Creating clients table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS clients (
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
            updated_by VARCHAR(255)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Clients table created successfully.\n";
    
    // Test insert
    echo "Testing data insertion...\n";
    $stmt = $pdo->prepare("
        INSERT INTO clients (
            client_id, client_name, client_description,
            app_redirect_uri, post_logout_redirect_uri,
            user_handler_endpoint, api_secret_key,
            status, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP
    ");
    
    $result = $stmt->execute([
        'test_client',
        'Test Application',
        'Test application for SSO integration',
        'http://localhost:3000/callback',
        'http://localhost:3000/logout',
        'http://localhost:8080/api/handler',
        'TEST_SECRET_KEY',
        'active',
        'test_installer'
    ]);
    
    if ($result) {
        echo "✓ Test data inserted successfully.\n";
    }
    
    // Test query
    echo "Testing data retrieval...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM clients");
    $count = $stmt->fetchColumn();
    echo "✓ Found $count client(s) in database.\n";
    
    // Show clients
    $stmt = $pdo->query("SELECT client_id, client_name, status FROM clients LIMIT 5");
    $clients = $stmt->fetchAll();
    
    echo "\nExisting clients:\n";
    foreach ($clients as $client) {
        echo "  - {$client['client_id']}: {$client['client_name']} ({$client['status']})\n";
    }
    
    echo "\n🎉 Database setup completed successfully!\n";
    echo "You can now access the admin panel at: http://localhost/sso-authen-3/admin/public/\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}