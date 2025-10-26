<?php

// Script to check if database and tables exist

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Checking database and tables...\n";

// Load configuration
$config = require __DIR__ . '/../config/admin_config.php';
$dbConfig = $config['database'];

echo "Database config:\n";
print_r($dbConfig);

try {
    // Create a PDO connection to check if database exists
    $dsn = sprintf(
        'mysql:host=%s;port=%d;charset=%s',
        $dbConfig['host'],
        $dbConfig['port'],
        $dbConfig['charset']
    );
    
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $dbConfig['options']);
    echo "Connected to MySQL server\n";
    
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE '{$dbConfig['database']}'");
    $databaseExists = $stmt->fetch();
    
    if ($databaseExists) {
        echo "Database '{$dbConfig['database']}' exists\n";
        
        // Select the database
        $pdo->query("USE {$dbConfig['database']}");
        
        // Check if required tables exist
        $requiredTables = ['clients', 'admin_users', 'audit_logs', 'jwt_secret_history'];
        
        foreach ($requiredTables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            $tableExists = $stmt->fetch();
            
            if ($tableExists) {
                echo "Table '$table' exists\n";
            } else {
                echo "Table '$table' does NOT exist\n";
            }
        }
    } else {
        echo "Database '{$dbConfig['database']}' does NOT exist\n";
    }
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "Database check completed.\n";