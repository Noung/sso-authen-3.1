<?php

// Test script to check if Connection class can be loaded

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing Connection class loading...\n";

// Try to manually include the Connection class
$connectionFile = __DIR__ . '/../src/Database/Connection.php';
if (file_exists($connectionFile)) {
    echo "Connection.php file exists\n";
    require_once $connectionFile;
    
    // Check if class exists
    if (class_exists('SsoAdmin\Database\Connection')) {
        echo "SsoAdmin\Database\Connection class exists\n";
        
        // Try to instantiate or call a static method
        try {
            echo "Class can be referenced\n";
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "SsoAdmin\Database\Connection class does NOT exist\n";
        
        // Let's check what classes are available
        $classes = get_declared_classes();
        foreach ($classes as $class) {
            if (strpos($class, 'Connection') !== false) {
                echo "Found class with 'Connection' in name: $class\n";
            }
        }
    }
} else {
    echo "Connection.php file does NOT exist at $connectionFile\n";
}

echo "Test completed.\n";