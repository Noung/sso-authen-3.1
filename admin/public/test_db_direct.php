<?php
// Direct database test
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Direct Database Test</h1>\n";

// Load admin configuration
$config = require __DIR__ . '/../config/admin_config.php';

echo "<h2>Configuration:</h2>\n";
echo "<p>Host: " . htmlspecialchars($config['database']['host']) . "</p>\n";
echo "<p>Database: " . htmlspecialchars($config['database']['database']) . "</p>\n";
echo "<p>Username: " . htmlspecialchars($config['database']['username']) . "</p>\n";
echo "<p>Password: " . (empty($config['database']['password']) ? '[EMPTY]' : '[SET]') . "</p>\n";

try {
    echo "<h2>Attempting Connection...</h2>\n";
    
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $config['database']['host'],
        $config['database']['port'],
        $config['database']['database'],
        $config['database']['charset']
    );
    
    echo "<p>DSN: " . htmlspecialchars($dsn) . "</p>\n";
    
    $pdo = new PDO(
        $dsn,
        $config['database']['username'],
        $config['database']['password'],
        $config['database']['options']
    );
    
    echo "<p style='color: green;'>✓ Connection successful!</p>\n";
    
    // Check if admin_users table exists
    echo "<h2>Checking Tables...</h2>\n";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'admin_users'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ admin_users table exists</p>\n";
        
        // Count records
        $countStmt = $pdo->query("SELECT COUNT(*) as count FROM admin_users");
        $count = $countStmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Records in admin_users: " . $count['count'] . "</p>\n";
        
        // Show sample data
        if ($count['count'] > 0) {
            $dataStmt = $pdo->query("SELECT id, email, name FROM admin_users LIMIT 5");
            echo "<h3>Sample data:</h3>\n";
            echo "<ul>\n";
            while ($row = $dataStmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<li>ID: " . htmlspecialchars($row['id']) . ", Email: " . htmlspecialchars($row['email']) . ", Name: " . htmlspecialchars($row['name']) . "</li>\n";
            }
            echo "</ul>\n";
        }
    } else {
        echo "<p style='color: red;'>✗ admin_users table missing</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Connection failed: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
} catch (Error $e) {
    echo "<p style='color: red;'>✗ Connection failed with Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}
?>