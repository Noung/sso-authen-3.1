<?php

// Test script to check database connection

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing database connection...\n";

// Load configuration
$config = require __DIR__ . '/../config/admin_config.php';
$dbConfig = $config['database'];

echo "Database config:\n";
print_r($dbConfig);

try {
    // Try to create a PDO connection directly
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $dbConfig['host'],
        $dbConfig['port'],
        $dbConfig['database'],
        $dbConfig['charset']
    );
    
    echo "DSN: $dsn\n";
    
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $dbConfig['options']);
    echo "Database connection successful!\n";
    
    // Try a simple query
    $stmt = $pdo->query('SELECT 1');
    $result = $stmt->fetch();
    echo "Simple query result: ";
    print_r($result);
    
} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "Database connection error: " . $e->getMessage() . "\n";
}

echo "Test completed.\n";