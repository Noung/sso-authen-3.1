<?php
// Test database connection and table existence
$config = require __DIR__ . '/config/admin_config.php';

try {
    // Enable error reporting
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    echo "Testing database connection...\n";
    
    // Connect to database
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $config['database']['host'],
        $config['database']['port'],
        $config['database']['database'],
        $config['database']['charset']
    );
    
    $pdo = new PDO(
        $dsn,
        $config['database']['username'],
        $config['database']['password'],
        $config['database']['options']
    );
    
    echo "✓ Database connection successful\n";
    
    // Check if admin_users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'admin_users'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Table 'admin_users' exists\n";
        
        // Count records
        $countStmt = $pdo->query("SELECT COUNT(*) as count FROM admin_users");
        $count = $countStmt->fetch(PDO::FETCH_ASSOC);
        echo "  - Records in admin_users: " . $count['count'] . "\n";
        
        // Show sample data
        if ($count['count'] > 0) {
            $dataStmt = $pdo->query("SELECT * FROM admin_users LIMIT 5");
            echo "  - Sample data:\n";
            while ($row = $dataStmt->fetch(PDO::FETCH_ASSOC)) {
                echo "    ID: " . $row['id'] . ", Email: " . $row['email'] . ", Name: " . $row['name'] . "\n";
            }
        }
    } else {
        echo "✗ Table 'admin_users' missing\n";
    }
    
    // Check if clients table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'clients'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Table 'clients' exists\n";
    } else {
        echo "✗ Table 'clients' missing\n";
    }
    
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
    echo "Error in file: " . $e->getFile() . " on line " . $e->getLine() . "\n";
}

echo "\nConfiguration:\n";
echo "Host: " . $config['database']['host'] . "\n";
echo "Database: " . $config['database']['database'] . "\n";
echo "Username: " . $config['database']['username'] . "\n";
echo "Password: " . ($config['database']['password'] ? '[HIDDEN]' : '[EMPTY]') . "\n";
?>