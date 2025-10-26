<?php

// Test script to check database connection through Connection class

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing database connection through Connection class...\n";

// Manually include the Connection class
require_once __DIR__ . '/../src/Database/Connection.php';

echo "Connection.php included\n";

// Load configuration
$config = require __DIR__ . '/../config/admin_config.php';
echo "Configuration loaded\n";

try {
    // Initialize the connection
    SsoAdmin\Database\Connection::init($config['database']);
    echo "Connection initialized\n";
    
    // Test the connection
    $result = SsoAdmin\Database\Connection::testConnection();
    if ($result) {
        echo "Database connection test PASSED\n";
    } else {
        echo "Database connection test FAILED\n";
    }
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "Test completed.\n";