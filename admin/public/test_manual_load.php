<?php

// Test script to manually load and use Connection class

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing manual loading of Connection class...\n";

// Manually include the Connection class
require_once __DIR__ . '/../src/Database/Connection.php';

echo "Connection.php included\n";

// Check if class exists
if (class_exists('SsoAdmin\Database\Connection')) {
    echo "SsoAdmin\Database\Connection class exists\n";
    
    // Try to call the init method
    try {
        // Load configuration
        $config = require __DIR__ . '/../config/admin_config.php';
        
        echo "Configuration loaded\n";
        print_r($config['database']);
        
        // Call init method
        SsoAdmin\Database\Connection::init($config['database']);
        echo "Connection::init() called successfully\n";
        
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage() . "\n";
        echo "Trace: " . $e->getTraceAsString() . "\n";
    } catch (Error $e) {
        echo "Error: " . $e->getMessage() . "\n";
        echo "Trace: " . $e->getTraceAsString() . "\n";
    }
} else {
    echo "SsoAdmin\Database\Connection class does NOT exist\n";
    
    // Let's see what classes are available
    $classes = get_declared_classes();
    echo "Available classes:\n";
    foreach ($classes as $class) {
        if (strpos($class, 'Connection') !== false) {
            echo "  $class\n";
        }
    }
}

echo "Test completed.\n";