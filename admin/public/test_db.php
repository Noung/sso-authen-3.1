<?php
// Test database connection and admin user

require_once __DIR__ . '/../src/Database/Connection.php';

// Load configuration
$config = require __DIR__ . '/../config/admin_config.php';

try {
    // Initialize database connection
    \SsoAdmin\Database\Connection::init($config['database']);
    
    // Test connection
    $pdo = \SsoAdmin\Database\Connection::getPdo();
    echo "Database connection successful!\n";
    
    // Check if admin user exists
    $sql = "SELECT * FROM admin_users WHERE email = ? AND status = 'active'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['admin@psu.ac.th']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "Admin user found:\n";
        print_r($user);
    } else {
        echo "Admin user not found!\n";
    }
    
    // Check all admin users
    echo "\nAll admin users:\n";
    $sql = "SELECT * FROM admin_users";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($users);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}